from neuralnetwork import *
from sklearn.preprocessing import StandardScaler
import numpy as np

# Đặt hạt giống ngẫu nhiên để kết quả tái lập
np.random.seed(42)

# Quá trình huấn luyện
SAMPLE_TRAIN = 1000

# Dữ liệu huấn luyện
XX = np.random.randn(SAMPLE_TRAIN, 4)
YY = (np.sum(XX, axis=1, keepdims=True) > 0).astype(int)  # Quy luật rõ ràng

scaler = StandardScaler()
XX = scaler.fit_transform(XX)

NN = NeuralNetwork(0.05, lf.CrossEntropy, [[6, af.Sigmoid], [4, af.Sigmoid]], XX, YY, af.Softmax)
NN.train(XX, YY, epoch=3000, batch_size=32)

# In loss cuối cùng
print("Final loss:", NN.error())

# Quá trình kiểm tra
SAMPLE_TEST = 200
TX = np.random.randn(SAMPLE_TEST, 4)
TY = (np.sum(TX, axis=1, keepdims=True) > 0).astype(int)
TX = scaler.transform(TX)

NN.test(TX, TY)
''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
input("milestoneB.py" + " ")