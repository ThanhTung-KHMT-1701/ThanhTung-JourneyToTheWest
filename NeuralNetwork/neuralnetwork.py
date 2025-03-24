import numpy                as np
import cupy                 as cp

import display_output       as do
import activation_function  as af
import       loss_function  as lf

np.set_printoptions(formatter={'float': '{:+12.6f}'.format})

class UtilityFunction:
    def one_hot(Y):
        '''
        Args:
            Y: là một ma trận cột `R x 1`
        
        Returns:
            Hàm này trả về một ma trận `R x C`, với C là số các giá trị khác nhau trong Y
        '''
        assert(isinstance(Y, np.ndarray) == True and Y.ndim == 2 and Y.shape[1] == 1)

        # Tìm tập hợp các nhãn
        S = set()
        for _ in Y: S.add(_[0])

        # Số cột của ma trận mới sau khi tạo bằng số các giá trị khác nhau của tập nhãn
        N = len(S)
        L = list(sorted(S))

        R = Y.shape[0]

        one_hot = np.zeros((R, N))

        for i in range(R):
            # Tương ứng với mỗi giá trị trong ma trận nhãn
            # Ta sẽ tìm thứ hạng của nó trong tập nhãn
            j = L.index(Y[i][0])

            one_hot[i, j] = 1

        return one_hot

class NeuralNetwork:
    """
    Attributes:
        **C** là hàm tính toán độ mất mát (Cost Function)
        **Layer**
        + Layer có cấu trúc là 1 từ điển
        + Layer dùng để C.R.U.D dữ liệu của một lớp bất kỳ có trong mạng
    """
    def __init__(self, LearningRate, LossFunction, HiddenLayer, XX, YY, OutputActivationFunction):
        assert(isinstance(LearningRate, float))
        assert(LossFunction is not None)
        assert(isinstance(HiddenLayer, list))
        assert(isinstance(XX, np.ndarray) and XX.ndim == 2)
        assert(isinstance(YY, np.ndarray) and YY.ndim == 2 and YY.shape[1] == 1)
        assert(XX.shape[0] == YY.shape[0])
        assert(OutputActivationFunction is not None)

        self.n = LearningRate
        self.C = LossFunction
        
        # self.X  = None
        # Không cần thiết phải tạo thêm trường dữ liệu self.X
        # Bởi vì, self.X chính là dữ liệu cho lớp 0 (lớp đầu vào)
        self.Y  = None

        self.Layer = dict()

        I = 0       # I ~  InputLayer
        O = None    # O ~ OutputLayer

        Feature = XX.shape[1]
        Label   = UtilityFunction.one_hot(YY).shape[1]

        # Input-Layer-Structure
        Layer(self, I, Feature, None)
        
        i = 1
        for _ in HiddenLayer: 
            Layer(self, i, _[0], _[1])
            i = i + 1
        
        # Output-Layer-Structure
        O = i
        Layer(self, O, Label, OutputActivationFunction)

    def __str__(self):
        layer_structure = "-".join("[{}]".format(_.s) for _ in self.Layer.values())

        return "\
        \n\
        \r{}\
        \rC    : {}\n\
        \rLayer: ...\n\
        \r{}\n\
        \r{}\n\
        "\
        .format\
        (
            do.print_title("Neural Network"),
            self.C.NAME,
            do.print_title(layer_structure),
            "\n".join([str(_) for _ in self.Layer.values()])
        )
    
    def error(self):
        '''
        Returns:
            Hàm này trả về 1 số đại diện cho độ mất mát của mạng tính đến thời điểm hiện tại
        '''   
        # Lấy chỉ số của lớp cuối cùng (lớp đầu ra)
        i = list(self.Layer.keys())[-1]

        # Lớp đầu ra
        O = self.Layer[i]
        A = O.A

        return self.C.f(A, self.Y)

    def train(self, XX, YY, epoch, batch_size):
        # T là số bản ghi trong khối dữ liệu huấn luyện
        T = XX.shape[0]

        # LayerKey là danh sách các chỉ số của các lớp trong mạng
        LayerKey = list(self.Layer.keys())
        LayerKey_Reverse = list(reversed(LayerKey))

        # Trong quá trình huấn luyện, cấu trúc mạng không thay đổi
        # Sự thay đổi là giá trị của các trường dữ liệu trong từng lớp
        FirstLayer = self.Layer[0]
        FinalLayer = self.Layer[LayerKey[-1]]
        
        # Khi chạy hết 1 lô, ta cần tính giá trị trung bình của các ma trận Gradient-Descent (tại lớp cuối)
        AG = np.zeros(FinalLayer.B.shape)

        # Xử lý dữ liệu đầu vào
        YY = UtilityFunction.one_hot(YY)

        # Biến CER dùng để kiểm tra xem liệu độ mất mát có giảm sau mỗi lần lặp
        # khi xử lý hết các lô hay không?
        CER = +float('inf')

        for _ in range(epoch):
            # Theo dõi sự biến thiên của hàm lỗi qua mỗi lần lặp
            if _ == epoch:
            # if _ != 0:
                print("Epoch: {:>6d} | NeuralNetwork.error() : {:.24f} | {}".format(_, self.error(), CER > self.error()))
                CER = self.error()

            L = 0
            R = 0
            while True:
                if L > T - 1: break
                if R < T - 1 and (R - L + 1) < batch_size: 
                    R = R + 1
                    continue
                
                # Trường hợp con trỏ R đã đến giới hạn hoặc kích thước lô đã đúng như yêu cầu
                # print("{:2d} {:2d}".format(L, R))

                AG.fill(0)

                for i in range(L, R + 1, 1):
                    # Chuyển dữ liệu từ bản ghi hiện tại đến thẳng luôn trường dữ liệu A của lớp đầu vào
                    FirstLayer.A = XX[i].reshape(-1, 1)

                    # Đồng thời, gán lại nhãn cho mạng
                    self.Y = YY[i].reshape(-1, 1)

                    # Bước 1: Lan truyền tiến
                    for K in LayerKey:
                        if K == 0: continue
                        
                        # Lc đại diện cho lớp đang xét (duyệt theo thứ tự khóa (chỉ số của lớp))
                        Lc = self.Layer[K]
                        Lc.forward()

                    AG = AG + self.C.d(FinalLayer.A, self.Y) * FinalLayer.F.d(FinalLayer.Z)
                
                # Tính giá trị trung bình của ma trận Gradient-Descent (tại lớp cuối)
                AG = AG / batch_size

                # Riêng đối với lớp cuối, ta sẽ cập nhật lại thủ công các giá trị
                # Bởi vì, ta không phải phụ thuộc vào cặp giá trị (A, Y) nữa
                FinalLayer.G = AG
                FinalLayer.W = FinalLayer.W - self.n * np.outer(FinalLayer.G, self.Layer[FinalLayer.l - 1].A.T)
                FinalLayer.B = FinalLayer.B - self.n * FinalLayer.G

                # Bước 2: Lan truyền ngược
                for K in LayerKey_Reverse:
                    if K == 0 or K == LayerKey_Reverse[0]: continue
                    
                    # Lc đại diện cho lớp đang xét (duyệt theo thứ tự khóa (chỉ số của lớp))
                    Lc = self.Layer[K]
                    Lc.backward()

                L = R + 1
                R = L
    
    def test(self, XX, YY):
        # T là số bản ghi trong khối dữ liệu kiểm tra
        T = XX.shape[0]

        # LayerKey là danh sách các chỉ số của các lớp trong mạng
        LayerKey = list(self.Layer.keys())

        FirstLayer = self.Layer[0]
        FinalLayer = self.Layer[LayerKey[-1]]

        YY = UtilityFunction.one_hot(YY)

        CorrectAnswer = 0

        for i in range(T):
            # Chuyển dữ liệu từ bản ghi hiện tại đến thẳng luôn trường dữ liệu A của lớp đầu vào
            FirstLayer.A = XX[i].reshape(-1, 1)

            # Đồng thời, gán lại nhãn cho mạng
            self.Y = YY[i].reshape(-1, 1)

            # Khi kiểm tra chỉ có quá trình lan truyền tiến
            for K in LayerKey:
                if K == 0: continue
                
                # Lc đại diện cho lớp đang xét (duyệt theo thứ tự khóa (chỉ số của lớp))
                Lc = self.Layer[K]
                Lc.forward()

            Label = np.argmax(self.Y)
            PredictAnswer = np.argmax(FinalLayer.A)

            CorrectAnswer = CorrectAnswer + (1 if Label == PredictAnswer else 0)   

        print("Accuracy: {:.24f}".format(CorrectAnswer / T))

    def save_model(): pass
    def load_model(): pass

class Layer:
    """
    Attributes:
        **l** là vị trí của lớp
        **s** là số lượng các nút trong lớp
        **W** 
        + W là ma trận trọng số
        + W là ma trận có kích thước `s_l x s_ls1`
        **B** 
        + B là ma trận hệ số điều chỉnh
        + B là ma trận có kích thước `s_l x 1`
        **Z** 
        + Z là ma trận có kích thước `s_l x 1`
        + Z là ma trận được tính bằng `Zl = Wl * Als1 + Bl`
        **A** 
        + A là ma trận được tính bằng `Al = F(Zl)`
        + A là ma trận có kích thước `s_l x 1`
        **G** 
        + G là ma trận được tính bằng `Gl = dC/d(Zl)` 
        + G là ma trận có kích thước `s_l x 1`
    """
    def __init__(self, NeuralNetwork, l, s, ActivationFunction):        
        # Kiểm tra điều kiện
        assert(NeuralNetwork is not None)
        assert(l >= 0)
        assert(s >= 0)

        # Đầu vào cần đảm bảo rằng
        # lớp đầu tiên không nên có hàm kích hoạt
        # lớp ẩn và lớp đầu ra cần phải có hàm kích hoạt
        if l == 0: 
            assert(ActivationFunction is None)
        else: 
            assert(ActivationFunction is not None)
        
        self.l = l
        self.s = s

        self.W = None
        self.B = None

        self.Z = None
        self.A = None
        self.F = ActivationFunction
        self.G = None

        self.NeuralNetwork = NeuralNetwork 
        self.NeuralNetwork.Layer[self.l] = self

        # Trường hợp lớp đầu vào
        if (l == 0): return

        # Trường hợp lớp ẩn và lớp đầu ra
        L = self
        Ls1 = NeuralNetwork.Layer.get(self.l - 1, None)

        self.W = np.random.normal(0, 1, (L.s, Ls1.s))
        self.B = np.random.normal(0, 1, (L.s, 1))

    def __str__(self):
        return "\
        \n\
        \rLớp    {:02}\n\
        \rSố nút {:02}\n\
        \r---------\n\
        \rLayer.W: {}\n\
        \rLayer.B: {}\n\
        \rLayer.Z: {}\n\
        \rLayer.A: {}\n\
        \rLayer.F: {}\n\
        \rLayer.G: {}\n\
        "\
        .format\
        (
            self.l, 
            self.s, 
            do.print_matrix(None if self.W is None else self.W.tolist(), len("Layer.W: ") * " "), 
            do.print_matrix(None if self.B is None else self.B.tolist(), len("Layer.B: ") * " "), 
            do.print_matrix(None if self.Z is None else self.Z.tolist(), len("Layer.Z: ") * " "), 
            do.print_matrix(None if self.A is None else self.A.tolist(), len("Layer.A: ") * " "), 
            self.F.NAME if self.F != None else None,
            do.print_matrix(None if self.G is None else self.G.tolist(), len("Layer.G: ") * " ") 
        )
    
    def forward(self):
        assert(self.l >= 1 and self.l in self.NeuralNetwork.Layer.keys())

        NNL = self.NeuralNetwork.Layer

        L   = self
        Ls1 = NNL[self.l - 1]

        L.Z = np.dot(L.W, Ls1.A) + L.B
        L.A = L.F.f(L.Z)

    def backward(self):
        NN  = self.NeuralNetwork
        NNL = self.NeuralNetwork.Layer

        # Chỉ số của lớp cuối cùng (lớp đầu ra)
        N = list(NNL.keys())[-1]

        assert(self.l >= 1)
        
        # This is the most important step in the whole algorithm
        # Gradient-Descent
        if (self.l == N):
            # Trường hợp đây là lớp cuối (lớp đầu ra)
            self.G = NN.C.d(self.A, NN.Y) * self.F.d(self.Z)
        else: 
            # Trường hợp đây là lớp ẩn
            self.G = np.dot(NNL[self.l + 1].W.T, NNL[self.l + 1].G) * self.F.d(self.Z)

        # Cập nhật lại ma trận trọng số và ma trận hệ số điều chỉnh
        self.W = self.W - NN.n * np.outer(self.G, NNL[self.l - 1].A.T)
        self.B = self.B - NN.n * self.G

''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
input("neuralnetwork.py" + " ")