from neuralnetwork import *

NUMBER_TEST = int(1e3)
accuracy = 0

SAMPLE = int(1e3)

XX = np.random.randn(SAMPLE, 4)
YY = np.random.randint(low=1, high=50, size=(SAMPLE, 1))

NN  = NeuralNetwork(0.1, lf.MSE, [[2, af.Sigmoid], [2, af.Sigmoid], [2, af.Sigmoid]], XX, YY, af.Softmax)
NNL = NN.Layer

N = len(NNL.keys())
YY = UtilityFunction.one_hot(YY)

for __ in range(NUMBER_TEST):

    LI = NNL[0]
    LI.A = XX[__].reshape(-1, 1)
    NN.Y = YY[__].reshape(-1, 1)

    FY  = NN.Y
    FYI = np.argmax(NN.Y) 

    A = 1

    for _ in range(int(1e4)):
        # Bước 1: Lan truyền tiến
        for i in range(1, N, 1): 
            L = NNL[i]
            L.forward()

        # print("NN.error: {:.24f} {}".format(A, A > NN.error()))
        A = NN.error()

        # Bước 2: Lan truyền ngược
        for i in range(N - 1, 0, -1):
            L = NNL[i]
            L.backward()
    
    LY  = NNL[N - 1].A
    LYI = np.argmax(NNL[N - 1].A)

    if FYI == LYI: accuracy = accuracy + 1

    print("TEST {:>6d} : {:.24f}".format(__, accuracy / (__ + 1)))
''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
input("milestoneA.py" + " ")