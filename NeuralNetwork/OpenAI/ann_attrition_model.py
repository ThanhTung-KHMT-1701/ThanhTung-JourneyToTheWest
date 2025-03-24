# =====================================================
# pip install pandas scikit-learn tensorflow matplotlib
# =====================================================

# 2.1. Dữ liệu và tiền xử lý dữ liệu
import pandas as pd
import matplotlib.pyplot as plt
from sklearn.model_selection import train_test_split, StratifiedKFold, ParameterGrid
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import classification_report, accuracy_score, precision_score, recall_score, f1_score
import tensorflow as tf
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Dense
from tensorflow.keras.optimizers import Adam
import numpy as np

# print(tf.__version__)
# print(tf.config.list_physical_devices())

# # ==================== Cấu hình GPU ====================
# gpus = tf.config.list_physical_devices('GPU')
# if gpus:
#     try:
#         for gpu in gpus:
#             tf.config.experimental.set_memory_growth(gpu, True)
#         tf.config.set_visible_devices(gpus[0], 'GPU')
#         print(f"Đã phát hiện và cấu hình GPU: {gpus[0].name}")
#     except RuntimeError as e:
#         print("Lỗi khi cấu hình GPU:", e)
# else:
#     raise RuntimeError("Không phát hiện GPU nào! Vui lòng kiểm tra driver và CUDA.")

# ==================== Dữ liệu ====================
df = pd.read_csv("WA_Fn-UseC_-HR-Employee-Attrition.csv")
df = df.drop(columns=['EmployeeCount', 'EmployeeNumber', 'Over18', 'StandardHours'])
df['Attrition'] = df['Attrition'].map({'Yes': 1, 'No': 0})

# Dòng này sẽ thực hiện chức năng one-hot đối với
# các trường dữ liệu không phải là dạng số, ví dụ như Department
df = pd.get_dummies(df, drop_first=True)
# Dummy variable trap xảy ra khi các biến one-hot quá đầy đủ, dẫn đến đa cộng tuyến (multicollinearity)
# tức là một biến có thể dự đoán được từ các biến còn lại.

X = df.drop('Attrition', axis=1)
y = df['Attrition']
scaler = StandardScaler()

# Bước chuẩn hóa dữ liệu đầu vào
X_scaled = scaler.fit_transform(X)

# ==================== Xây dựng mô hình ====================
def build_ann_model(input_dim, learning_rate=0.001, activation='relu',
                    hidden_layers=10, units_per_layer=32):
    
    # Mô hình có kiến trúc các lớp sau tiếp nối các lớp trước
    # Hàm kích hoạt cuối cùng là "Signmoid"
    # Hàm mất mát được sử dụng là "CrossEntropy"
    # Thuật toán tối ưu được dùng là "Adam"

    model = Sequential()
    model.add(Dense(units_per_layer, input_dim=input_dim, activation=activation))
    for _ in range(hidden_layers - 1):
        model.add(Dense(units_per_layer, activation=activation))
    
    # Lớp đầu ra: 1 nút, hàm sigmoid
    model.add(Dense(1, activation='sigmoid'))

    optimizer = Adam(learning_rate=learning_rate) # Thuật toán tối ưu
    model.compile(optimizer=optimizer, loss='binary_crossentropy', metrics=['accuracy'])
    return model

# ==================== Huấn luyện cơ bản ====================
X_train, X_test, y_train, y_test = train_test_split(X_scaled, y, test_size=0.2, stratify=y, random_state=42)

model = build_ann_model(input_dim=X_train.shape[1],
                        learning_rate=0.001,
                        activation='relu',
                        hidden_layers=10,
                        units_per_layer=64)

history = model.fit(X_train, y_train, epochs=1000, batch_size=32, validation_split=0.1, verbose=1)

y_pred = (model.predict(X_test) > 0.5).astype("int32")
print("Accuracy:", accuracy_score(y_test, y_pred))
print(classification_report(y_test, y_pred))

# ==================== Vẽ biểu đồ Loss và Accuracy ====================
plt.figure(figsize=(12, 5)) # Ngang - Dọc

plt.subplot(1, 2, 1) # 1 hàng, 2 cột, sử dụng cột thứ nhất (1)
plt.plot(history.history['loss'], label='Train Loss')
plt.plot(history.history['val_loss'], label='Validation Loss')
plt.title("Loss per Epoch")
plt.xlabel("Epoch")
plt.ylabel("Loss")
plt.legend() # Hiển thị chú thích

plt.subplot(1, 2, 2) # 1 hàng, 2 cột, sử dụng cột thứ nhất (2)
plt.plot(history.history['accuracy'], label='Train Accuracy')
plt.plot(history.history['val_accuracy'], label='Validation Accuracy')
plt.title("Accuracy per Epoch")
plt.xlabel("Epoch")
plt.ylabel("Accuracy")
plt.legend() # Hiển thị chú thích

plt.tight_layout()
plt.savefig("OpenAI/training_plot.png")
plt.show()

# ==================== Lưu mô hình ====================
model.save("OpenAI/ann_attrition_model.h5")
print("Mô hình cơ bản đã được lưu vào ann_attrition_model.h5")

# ==================== Cross-validation ====================
print("\n=== Cross-Validation với 5 Fold ===")
kf = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)
# ở mỗi fold, StratifiedKFold đảm bảo số lượng 
# nhân viên nghỉ việc / không nghỉ gần như tỷ lệ gốc của toàn bộ dataset.

fold = 1
for train_index, val_index in kf.split(X_scaled, y):
    X_tr, X_val = X_scaled[train_index], X_scaled[val_index]
    y_tr, y_val = y.iloc[train_index], y.iloc[val_index]
    
    temp_model = build_ann_model(input_dim=X_tr.shape[1],
                                 learning_rate=0.001,
                                 activation='relu',
                                 hidden_layers=2,
                                 units_per_layer=32)
    
    temp_model.fit(X_tr, y_tr, epochs=1000, batch_size=32, verbose=0)
    y_val_pred = (temp_model.predict(X_val) > 0.5).astype("int32")
    acc = accuracy_score(y_val, y_val_pred)
    print(f"Fold {fold} Accuracy: {acc:.4f}")
    fold += 1

# ==================== Grid Search ====================
param_grid = {
    'learning_rate': [0.001, 0.005],
    'activation': ['relu', 'tanh'],
    'hidden_layers': [2, 3],
    'units_per_layer': [32, 64]
}

grid = list(ParameterGrid(param_grid))
print(f"\n=== Grid Search: {len(grid)} combinations ===")

best_acc = 0
best_params = None
best_model = None

accuracies = []
param_descriptions = []
precisions = []
recalls = []
f1s = []

for i, params in enumerate(grid):
    print(f"\n[{i+1}/{len(grid)}] Đang thử: {params}")
    
    model = build_ann_model(
        input_dim=X_train.shape[1],
        learning_rate=params['learning_rate'],
        activation=params['activation'],
        hidden_layers=params['hidden_layers'],
        units_per_layer=params['units_per_layer']
    )
    
    model.fit(X_train, y_train, epochs=1000, batch_size=32, verbose=0, validation_split=0.1)
    y_pred = (model.predict(X_test) > 0.5).astype("int32")
    
    # Tiêu chí
    acc = accuracy_score(y_test, y_pred)
    prec = precision_score(y_test, y_pred)
    rec = recall_score(y_test, y_pred)
    f1score = f1_score(y_test, y_pred)
    
    desc = f"lr={params['learning_rate']}, act={params['activation']}, hl={params['hidden_layers']}, units={params['units_per_layer']}"
    param_descriptions.append(desc)
    accuracies.append(acc)
    precisions.append(prec)
    recalls.append(rec)
    f1s.append(f1score)
    
    if acc > best_acc:
        best_acc = acc
        best_params = params
        best_model = model

# ==================== Lưu mô hình tốt nhất ====================
best_model.save("OpenAI/best_ann_model.h5")
print("\nMô hình tốt nhất đã được lưu vào: best_ann_model.h5")
# Khi cần đọc lại, ta có thể sử dụng câu lệnh sau
# ----------------model = load_model("OpenAI/best_ann_model.h5")
# Loại này nó không tự động lưu lại lịch sử huấn luyện
# Ta cần thực hiện như sau nếu muốn lưu lại
# pd.DataFrame(history.history).to_csv("OpenAI/history.csv", index=False)

# ==================== Vẽ biểu đồ Grid Search ====================
plt.figure(figsize=(12, 6))
plt.barh(param_descriptions, accuracies, color='skyblue')
plt.xlabel("Accuracy")
plt.title("So sánh các cấu hình Grid Search")
plt.tight_layout()
plt.savefig("OpenAI/gridsearch_comparison.png")
plt.show()

# ==================== Xuất kết quả ra CSV ====================
results_df = pd.DataFrame({
    'Params': param_descriptions,
    'Accuracy': accuracies,
    'Precision': precisions,
    'Recall': recalls,
    'F1-Score': f1s
})

results_df.to_csv("OpenAI/gridsearch_results.csv", index=False)
print("Đã lưu kết quả Grid Search chi tiết vào: gridsearch_results.csv")