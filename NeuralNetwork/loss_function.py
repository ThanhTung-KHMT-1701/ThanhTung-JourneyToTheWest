import numpy as np

# Gỡ lỗi
np.set_printoptions(formatter={'float': '{:+12.6f}'.format})

class MSE:
    NAME = "Mean Squared Error"

    @classmethod
    def f(cls, A, Y):
        N = Y.shape[0]
        
        return 1/N * np.sum((A - Y)**2)

    @classmethod
    def d(cls, A, Y):
        N = Y.shape[0]

        return 2/N * (A - Y)

class CrossEntropy:
    NAME = "Cross Entropy"
    EPSILON = 1e-12

    @classmethod
    def f(cls, A, Y):
        A = np.clip(A, cls.EPSILON, 1. - cls.EPSILON) # Xử lý lỗi đối với việc sử dụng Log()
        N = Y.shape[0]

        return 1/N * np.sum(-Y * np.log(A))

    @classmethod
    def d(cls, A, Y):
        A = np.clip(A, cls.EPSILON, 1. - cls.EPSILON) # Xử lý lỗi đối với việc sử dụng Log()
        N = Y.shape[0]

        return 1/N * (-Y / A)
    
    @classmethod
    def d_softmax(cls, A, Y):
        N = Y.shape[0]

        return 1/N * (A - Y)
