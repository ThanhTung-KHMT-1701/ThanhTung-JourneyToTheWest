from neuralnetwork import *

# Quá trình huấn luyện
SAMPLE_TRAIN = int(50)
XX = np.random.randn(SAMPLE_TRAIN, 4)
YY = np.random.randint(low=1, high=3, size=(SAMPLE_TRAIN, 1))

NN = NeuralNetwork(0.1, lf.CrossEntropy, [[3, af.Sigmoid], [3, af.Sigmoid]], XX, YY, af.Softmax)
NN.train(XX, YY, 10000, 10)

# Quá trình kiểm tra
SAMPLE_TEST  = int(1e1) 
TX = np.random.randn(SAMPLE_TEST, 4)
TY = np.random.randint(low=1, high=3, size=(SAMPLE_TEST, 1))
NN.test(TX, TY)
''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
input("milestoneB.py" + " ")