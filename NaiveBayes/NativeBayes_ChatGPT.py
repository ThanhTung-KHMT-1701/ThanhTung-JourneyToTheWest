import numpy as np

# Hàm create_train_data: tạo dữ liệu huấn luyện dưới dạng một mảng numpy.
def create_train_data():
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

# Hàm compute_prior_probablity: tính xác suất tiên nghiệm của các lớp (yes và no).
def compute_prior_probablity(train_data):
    y_unique = ['no', 'yes']
    prior_probability = np.zeros(len(y_unique))

    for i, y in enumerate(y_unique):
        prior_probability[i] = np.sum(train_data[:, -1] == y) / len(train_data)
    return prior_probability

# Hàm compute_conditional_probability: tính xác suất có điều kiện của các đặc trưng dựa trên lớp.
def compute_conditional_probability(train_data):
    y_unique = ['no', 'yes']
    conditional_probability = []
    list_x_name = []

    N = train_data.shape[1]-1 
    for i in range(0,N):
        x_unique = np.unique(train_data[:,i])
        list_x_name.append(x_unique)
        x_conditional_probability = []

        for y in y_unique:
            pro_y_x = []

            for x in x_unique:
                count_y_x = np.sum((train_data[:,-1] == y) & (train_data[:,i] == x))
                count_y   = np.sum(train_data[:,-1] == y)

                pro_y_x.append(count_y_x / count_y if count_y > 0 else 0)

            x_conditional_probability.append(pro_y_x)

        conditional_probability.append(x_conditional_probability)
    return conditional_probability, list_x_name

# Hàm get_index_from_value: trả về chỉ số của một số giá trị trong danh sách các giá trị duy nhất của một đặc trưng.
def get_index_from_value(feature_name, list_features):
    return np.where(list_features == feature_name)[0][0]


# Hàm train_naive_bayes: huấn luyện mô hình Naive Bayes bằng cách tính xác suất tiên nghiệm và xác suất có điều kiện.
def train_naive_bayes(train_data):
    # Step 1: Calculate Prior Probability
    prior_probability = compute_prior_probablity(train_data)

    # Step 2: Calculate Conditional Probability
    conditional_probability, list_x_name = compute_conditional_probability(train_data)

    return prior_probability, conditional_probability, list_x_name


# Hàm prediction_play_tennis: dự đoán lớp (yes hoặc no) cho một mẫu dữ liệu mới dựa trên mô hình Naive Bayes đã huấn luyện.
def prediction_play_tennis(new_data, prior_probability, conditional_probability, list_x_name):
    y_unique = ['no', 'yes']
    predictions = []

    for data_point in new_data:
        posterior_probabilities = []
        for y_index, y in enumerate(y_unique):
            posterior = prior_probability[y_index]
            for i, feature_value in enumerate(data_point):
                feature_index = get_index_from_value(feature_value, list_x_name[i])
                posterior *= conditional_probability[i][y_index][feature_index]
            posterior_probabilities.append(posterior)

        print(posterior_probabilities)

        predicted_class = y_unique[np.argmax(posterior_probabilities)]
        predictions.append(predicted_class)
    return predictions


data = create_train_data()
prior_probability, conditional_probability, list_x_name = train_naive_bayes(data)

# Example usage:
new_data = [['Sunny','Cool', 'High', 'VietNam']]
predictions = prediction_play_tennis(new_data, prior_probability, conditional_probability, list_x_name)
print(f"Predictions for {new_data}: {predictions}")

input()