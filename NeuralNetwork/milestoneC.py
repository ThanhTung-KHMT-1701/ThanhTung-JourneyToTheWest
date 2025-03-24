import pandas as pd
import numpy as np
import matplotlib.pyplot as plt

from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler

from neuralnetwork import *
import activation_function as af
import loss_function as lf

# Đọc dữ liệu từ file CSV
df = pd.read_csv("WA_Fn-UseC_-HR-Employee-Attrition.csv")

# Tiền xử lý
df.drop(columns=["EmployeeCount", "EmployeeNumber", "Over18", "StandardHours"], inplace=True)
df["Attrition"] = df["Attrition"].map({"Yes": 1, "No": 0})
df = pd.get_dummies(df, drop_first=True)

# Tách X và y
X = df.drop(columns=["Attrition"]).values
y = df["Attrition"].values.reshape(-1, 1)

# Chuẩn hóa dữ liệu
scaler = StandardScaler()
X = scaler.fit_transform(X)

# Chia train/test
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, stratify=y, random_state=42)

# Tạo và huấn luyện mạng nơ-ron
NN = NeuralNetwork(
    LearningRate=0.01,
    LossFunction=lf.CrossEntropy,
    HiddenLayer=[[16, af.ReLU], [8, af.ReLU]],
    XX=X_train,
    YY=y_train,
    OutputActivationFunction=af.Softmax
)

LossHistory = NN.train(X_train, y_train, epoch=300, batch_size=32)
pd.DataFrame(LossHistory).to_csv("loss_history.csv", index=False, header=["Loss"])

# Đánh giá mô hình
print("Final loss:", NN.error())
NN.test(X_test, y_test)

# Vẽ biểu đồ hiển thị sự biến thiên của hàm mất mát
plt.plot(LossHistory, label="Loss")
plt.xlabel("Epoch (log step)")
plt.ylabel("Loss")
plt.title("Training Loss Over Epochs")
plt.legend()
plt.grid(True)

plt.ylim(0, 1.0)
plt.tight_layout()
plt.show()