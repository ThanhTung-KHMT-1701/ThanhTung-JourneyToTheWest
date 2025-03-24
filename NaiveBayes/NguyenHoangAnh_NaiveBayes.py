import numpy as np

def create_train_data():
    """
    Tạo dữ liệu huấn luyện mẫu.
    Dữ liệu bao gồm các thuộc tính (Outlook, Temperature, Humidity, Wind) và nhãn (PlayTennis).
    """
    data = [['Sunny','Hot', 'High', 'Weak', 'no'],
            ['Sunny','Hot', 'High', 'Strong', 'no'],
            ['Overcast','Hot', 'High', 'Weak', 'yes'],
            ['Rain','Mild', 'High', 'Weak', 'yes'],
            ['Rain','Cool', 'Normal', 'Weak', 'yes'],
            ['Rain','Cool', 'Normal', 'Strong', 'no'],
            ['Overcast','Cool', 'Normal', 'Strong', 'yes'],
            ['Overcast','Mild', 'High', 'Weak', 'no'],
            ['Sunny','Cool', 'Normal', 'Weak', 'yes'],
            ['Rain','Mild', 'Normal', 'Weak', 'yes']
           ]
    return np.array(data)

def compute_prior_probability(train_data):
    """
    Tính xác suất tiên nghiệm của mỗi lớp (P(PlayTennis = 'yes') và P(PlayTennis = 'no')).
    """
    y_unique = ['no', 'yes']  # Các lớp có thể có
    prior_probability = np.zeros(len(y_unique))  # Khởi tạo mảng lưu xác suất tiên nghiệm

    total_samples = len(train_data)  # Tổng số mẫu dữ liệu
    for i, label in enumerate(y_unique):  # Duyệt qua từng lớp
        count = np.sum(train_data[:, -1] == label)  # Đếm số mẫu thuộc lớp label
        prior_probability[i] = count / total_samples  # Tính xác suất tiên nghiệm

    return prior_probability

def compute_conditional_probability(train_data):
    """
    Tính xác suất có điều kiện của từng thuộc tính dựa trên từng lớp 
    (ví dụ: P(Outlook = 'Sunny' | PlayTennis = 'yes')).
    """
    y_unique = ['no', 'yes']  # Các lớp có thể có
    conditional_probability = []  # List lưu xác suất có điều kiện của từng thuộc tính
    list_x_name = []  # List lưu các giá trị duy nhất của từng thuộc tính

    for i in range(train_data.shape[1] - 1):  # Duyệt qua từng thuộc tính (trừ cột nhãn)
        x_unique = np.unique(train_data[:, i])  # Lấy các giá trị duy nhất của thuộc tính thứ i
        list_x_name.append(x_unique)  # Lưu các giá trị duy nhất vào list

        feature_prob = []  # List lưu xác suất có điều kiện của thuộc tính thứ i cho từng lớp
        for label in y_unique:  # Duyệt qua từng lớp
            label_data = train_data[train_data[:, -1] == label]  # Lọc ra các mẫu thuộc lớp label
            prob_values = []  # List lưu xác suất có điều kiện của từng giá trị của thuộc tính thứ i khi biết lớp label
            for x_val in x_unique:  # Duyệt qua từng giá trị của thuộc tính thứ i
                count = np.sum(label_data[:, i] == x_val)  # Đếm số mẫu thuộc lớp label và có giá trị thuộc tính thứ i là x_val
                prob = count / len(label_data) if len(label_data) > 0 else 0  # Tính xác suất có điều kiện (tránh chia cho 0)
                prob_values.append(prob)  # Lưu xác suất vào list
            feature_prob.append(prob_values)  # Lưu list xác suất của thuộc tính thứ i cho lớp label vào list
        conditional_probability.append(feature_prob)  # Lưu xác suất có điều kiện của từng thuộc tính vào list

    return conditional_probability, list_x_name

def get_index_from_value(feature_name, list_features):
    """
    Tìm chỉ số của một giá trị (feature_name) trong một list các giá trị (list_features).
    """
    return np.where(list_features == feature_name)[0][0]

def train_naive_bayes(train_data):
    """
    Huấn luyện mô hình Naive Bayes bằng cách tính xác suất tiên nghiệm và xác suất có điều kiện.
    """
    prior_probability = compute_prior_probability(train_data)  # Tính xác suất tiên nghiệm
    conditional_probability, list_x_name = compute_conditional_probability(train_data)  # Tính xác suất có điều kiện
    return prior_probability, conditional_probability, list_x_name

def prediction_play_tennis(X, list_x_name, prior_probability, conditional_probability):
    """ 
    Dự đoán nhãn của một mẫu dữ liệu mới X.
    """
    x1 = get_index_from_value(X[0], list_x_name[0])  # Chỉ số của giá trị thuộc tính thứ nhất trong X
    x2 = get_index_from_value(X[1], list_x_name[1])  # Chỉ số của giá trị thuộc tính thứ hai trong X
    x3 = get_index_from_value(X[2], list_x_name[2])  # Chỉ số của giá trị thuộc tính thứ ba trong X
    x4 = get_index_from_value(X[3], list_x_name[3])  # Chỉ số của giá trị thuộc tính thứ tư trong X

    # Tính xác suất hậu nghiệm của từng lớp
    p0 = prior_probability[0] * conditional_probability[0][0][x1] * conditional_probability[1][0][x2] * \
         conditional_probability[2][0][x3] * conditional_probability[3][0][x4]  # Lớp 'no'
    p1 = prior_probability[1] * conditional_probability[0][1][x1] * conditional_probability[1][1][x2] * \
         conditional_probability[2][1][x3] * conditional_probability[3][1][x4]  # Lớp 'yes'

    return 0 if p0 > p1 else 1  # Trả về 0 nếu p0 > p1 (dự đoán là 'no'), ngược lại trả về 1 (dự đoán là 'yes')

train_data = create_train_data()
# In dữ liệu huấn luyện
print("Dữ liệu huấn luyện:")
for row in train_data:
    print(row)

_, list_x_name = compute_conditional_probability(train_data)
print("x1 =", list_x_name[0])
print("x2 =", list_x_name[1])
print("x3 =", list_x_name[2])
print("x4 =", list_x_name[3])

# Tính và in xác suất tiên nghiệm
prior_probability = compute_prior_probability(train_data)
print("\nXác suất tiên nghiệm:")
for i, label in enumerate(['no', 'yes']):
    print(f"P(PlayTennis = '{label}') = {prior_probability[i]}")

# Tính và in xác suất có điều kiện của TẤT CẢ các giá trị thuộc tính
conditional_probability, list_x_name = compute_conditional_probability(train_data)
print("\nXác suất có điều kiện:")
for i, name in enumerate(['Outlook', 'Temperature', 'Humidity', 'VietNam']):
    print(f"  {name}:")
    for j, label in enumerate(['no', 'yes']):
        print(f"    P({name} | PlayTennis = '{['no', 'yes'][j]}'):")
        for k, value in enumerate(list_x_name[i]):
            print(f"      P({name} = '{value}' | PlayTennis = '{['no', 'yes'][j]}') = {conditional_probability[i][j][k]}")

X = ['Sunny', 'Cool', 'High', 'Strong']
prior_probability, conditional_probability, list_x_name = train_naive_bayes(train_data)
pred = prediction_play_tennis(X, list_x_name, prior_probability, conditional_probability)
if pred:
    print("\nA nên đi chơi!")
else:
    print("\nA không nên đi chơi!")

y_true = train_data[:, -1]
y_pred = []
for x in train_data[:, :-1]:
    y_pred.append(['no', 'yes'][prediction_play_tennis(x, list_x_name, prior_probability, conditional_probability)])

accuracy = np.mean(y_true == y_pred)
print("Độ chính xác:", accuracy)