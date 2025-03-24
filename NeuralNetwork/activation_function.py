import numpy as np

# Gỡ lỗi
np.set_printoptions(formatter={'float': '{:+12.6f}'.format})

class ReLU:
    NAME = "ReLU"
    EPSILON = 1e-12

    @classmethod
    def f(cls, X): return np.where(X > 0, X, ReLU.EPSILON)
    
    @classmethod
    def d(cls, X): return np.where(X > 0, 1, ReLU.EPSILON)

class Sigmoid:
    NAME = "Signmoid"

    @classmethod
    def f(cls, X): return 1/(1 + np.exp(-X))
    
    @classmethod
    def d(cls, X):
        fX = Sigmoid.f(X)

        return fX * (1 - fX)

class Softmax:
    NAME = "Softmax"

    @classmethod
    def f(cls, X):
        Ex = np.exp(X - np.max(X))
        
        return Ex / np.sum(Ex)
    
    @classmethod
    def d(cls, X):
        S = Softmax.f(X)
        J = np.diag(S.flatten()) - np.dot(S, S.T)
        J = J.diagonal().reshape(-1, 1)

        return J