# Cách chạy: Mở CMD và cho đường dẫn đến thư mục chứa file 
# VD: cd "C:\Users\My laptop\OneDrive\Máy tính\AI\BTL"
# Sau đó chạy: python -m streamlit run app.py

# ===== Import thư viện cần thiết =====
import streamlit as st  # Thư viện tạo giao diện web app
st.set_page_config(
    page_title="Dự đoán nghỉ việc",
    layout="centered",
    initial_sidebar_state="expanded"
)

import pandas as pd  # Xử lý dữ liệu dạng bảng
import matplotlib.pyplot as plt  # Vẽ biểu đồ (không dùng trực tiếp trong file này nhưng có thể hỗ trợ)
import tensorflow as tf  # Dùng để load và chạy mô hình học sâu
from sklearn.preprocessing import StandardScaler  # Chuẩn hóa dữ liệu
from sklearn.metrics import classification_report, accuracy_score  # Đánh giá độ chính xác mô hình
import numpy as np  # Xử lý mảng số liệu

# ==================== Load dữ liệu và chuẩn hóa ====================
# Đọc file CSV chứa dữ liệu nhân viên
df = pd.read_csv("OpenAI\WA_Fn-UseC_-HR-Employee-Attrition.csv")
# Xóa các cột không cần thiết
df = df.drop(columns=['EmployeeCount', 'EmployeeNumber', 'Over18', 'StandardHours'])
# Chuyển cột Attrition từ chuỗi (Yes/No) thành số (1/0)
df['Attrition'] = df['Attrition'].map({'Yes': 1, 'No': 0})
# One-hot encoding cho các biến phân loại
df = pd.get_dummies(df, drop_first=True)
# Tách dữ liệu thành biến đầu vào (X) và đầu ra (y)
X = df.drop('Attrition', axis=1)
y = df['Attrition']
# Chuẩn hóa dữ liệu đầu vào để đưa về cùng thang đo
scaler = StandardScaler()
X_scaled = scaler.fit_transform(X)

# ==================== Giao diện Streamlit UI ====================
st.title("Dự đoán Nghỉ Việc Nhân Viên (Attrition Prediction)")

# Tạo 5 tab giao diện
tab1, tab2, tab3, tab4, tab5 = st.tabs([
    "Mô hình cơ bản",
    "Mô hình tốt nhất",
    "Biểu đồ Training",
    "So sánh Grid Search",
    "Dự đoán từ thông số nhập tay"
])

# ========== Tab 1: Hiển thị kết quả từ mô hình cơ bản ==========
with tab1:
    st.header("Dự đoán từ mô hình cơ bản")
    # Load mô hình đã huấn luyện sẵn (mô hình cơ bản)
    model_basic = tf.keras.models.load_model("OpenAI/ann_attrition_model.h5")
    # Dự đoán trên toàn bộ dữ liệu
    y_pred_basic = (model_basic.predict(X_scaled) > 0.5).astype("int32")
    # Tính độ chính xác và hiển thị
    acc_basic = accuracy_score(y, y_pred_basic)
    st.write(f"**Độ chính xác trên toàn bộ dữ liệu:** {acc_basic:.4f}")
    st.text("Báo cáo phân loại:")
    st.code(classification_report(y, y_pred_basic))
    st.write("---")
    st.write("Có thể xem biểu đồ Loss/Accuracy trong Tab 'Biểu đồ Training'.")

# ========== Tab 2: Hiển thị kết quả từ mô hình tốt nhất (Grid Search) ==========
with tab2:
    st.header("Mô hình tốt nhất từ Grid Search")
    # Load mô hình đã được tìm kiếm tham số tốt nhất
    best_model = tf.keras.models.load_model("OpenAI/best_ann_model.h5")
    # Dự đoán và tính toán độ chính xác
    y_pred_best = (best_model.predict(X_scaled) > 0.5).astype("int32")
    acc_best = accuracy_score(y, y_pred_best)
    st.write(f"**Độ chính xác trên toàn bộ dữ liệu:** {acc_best:.4f}")
    st.text("Báo cáo phân loại:")
    st.code(classification_report(y, y_pred_best))
    st.write("---")

# ========== Tab 3: Hiển thị biểu đồ loss/accuracy qua từng epoch ==========
with tab3:
    st.header("Biểu đồ Training Loss và Accuracy")
    # Hiển thị hình ảnh biểu đồ đã lưu sẵn
    st.image("OpenAI/training_plot.png", caption="Training Loss và Accuracy theo Epoch", use_container_width=True)

# ========== Tab 4: So sánh các mô hình trong Grid Search ==========
with tab4:
    st.header("So sánh các cấu hình từ Grid Search")
    # Hiển thị hình ảnh so sánh kết quả các cấu hình
    st.image("OpenAI/gridsearch_comparison.png", caption="So sánh Accuracy giữa các cấu hình", use_container_width=True)
    st.write("Kết quả chi tiết từ Grid Search:")
    # Hiển thị bảng kết quả chi tiết từ file CSV
    results = pd.read_csv("OpenAI/gridsearch_results.csv")
    st.dataframe(results)
    st.write("---")

# ========== Tab 5: Dự đoán từ dữ liệu nhập tay hoặc chọn từ danh sách nhân viên ==========
with tab5:
    st.header("Nhập thông số hoặc chọn nhân viên từ dữ liệu CSV")
    # Load lại dữ liệu gốc để cho phép người dùng chọn nhân viên
    raw_df = pd.read_csv("WA_Fn-UseC_-HR-Employee-Attrition.csv")
    # Loại bỏ các cột không cần hiển thị
    raw_df_show = raw_df.drop(columns=['EmployeeCount', 'Over18', 'StandardHours'])
    # Tạo danh sách nhân viên từ file CSV để người dùng lựa chọn
    emp_choices = ["Để trống / Nhập mới"] + raw_df['EmployeeNumber'].astype(str).tolist()
    selected_emp = st.selectbox("Chọn nhân viên:", emp_choices)

    # Nếu người dùng chọn một nhân viên từ danh sách:
    if selected_emp != "Để trống / Nhập mới":
        emp_data = raw_df_show[raw_df['EmployeeNumber'] == int(selected_emp)].iloc[0]
        st.write("Dữ liệu nhân viên đã chọn:")
        st.dataframe(emp_data.to_frame().T)

        # Chuẩn bị dữ liệu nhân viên đã chọn để đưa vào mô hình
        emp_data_dict = emp_data.drop(labels=['EmployeeNumber', 'Attrition']).to_dict()
        emp_data_encoded = pd.DataFrame([emp_data_dict])
        emp_data_encoded = pd.get_dummies(emp_data_encoded)
        emp_data_encoded = emp_data_encoded.reindex(columns=X.columns, fill_value=0)
    else:
        # Nếu người dùng không chọn, tạo dataframe trống để nhập tay
        emp_data_encoded = pd.DataFrame([[0.0]*len(X.columns)], columns=X.columns)

    st.write("---")
    st.subheader("Nhập hoặc chỉnh sửa các thông số:")

    user_input = {}

    # Tạo giao diện nhập thông số cho người dùng
    for i in range(0, len(X.columns), 2):
        col1, col2 = st.columns(2)

        # Tạo ô nhập liệu cho từng cột dữ liệu
        with col1:
            col_name = X.columns[i]
            default_val = emp_data_encoded[col_name].values[0]
            if X[col_name].dtype in [np.float64, np.int64]:
                val = st.text_input(f"{col_name}", value="" if selected_emp == "Để trống / Nhập mới" else str(default_val))
                user_input[col_name] = float(val) if val else 0.0
            else:
                val = st.selectbox(f"{col_name}", options=["No", "Yes"], index=int(default_val))
                user_input[col_name] = 1.0 if val == "Yes" else 0.0
        if i + 1 < len(X.columns):
            with col2:
                col_name = X.columns[i + 1]
                default_val = emp_data_encoded[col_name].values[0]
                if X[col_name].dtype in [np.float64, np.int64]:
                    val = st.text_input(f"{col_name}", value="" if selected_emp == "Để trống / Nhập mới" else str(default_val))
                    user_input[col_name] = float(val) if val else 0.0
                else:
                    val = st.selectbox(f"{col_name}", options=["No", "Yes"], index=int(default_val))
                    user_input[col_name] = 1.0 if val == "Yes" else 0.0

    # Khi bấm nút dự đoán:
    if st.button("Dự đoán từ dữ liệu đã chọn/nhập"):
        input_df = pd.DataFrame([user_input])
        input_scaled = scaler.transform(input_df)
        # Load mô hình tốt nhất
        model = tf.keras.models.load_model("OpenAI/best_ann_model.h5")
        # Thực hiện dự đoán
        prediction = (model.predict(input_scaled) > 0.5).astype("int32")[0][0]
        st.write("**Kết quả dự đoán:**")
        if prediction == 1:
            st.error("Nhân viên có khả năng nghỉ việc.")
        else:
            st.success("Nhân viên có khả năng ở lại.")
        st.write("---")