import os

SIZE_KEY = 20
SIZE_VALUE = 15
SIZE_COMMA = 9

# data_init: ({}, {}, {}, {}, {})
def print_data (adata_init):

    for i in range(len(adata_init)):
        
        print(f"Trường dữ liệu số {i:02d}".center(os.get_terminal_size().columns), end="")
        print("-".center(os.get_terminal_size().columns, "-"), end="")

        for (key, value) in adata_init[i].items(): print(f"{str(key):<{SIZE_KEY}s} : {value:>{SIZE_VALUE}.{SIZE_COMMA}f}")

        print()

# data: {... : ..., ... : ...}
def print_data_dictionary (data):
    for (key, value) in data.items(): print(f"{str(key):<{SIZE_KEY}s} : {value:>{SIZE_VALUE}.{SIZE_COMMA}f}")

# aData: mảng 2 chiều
# c1, c2: chỉ số cột
# v1, v2: chỉ số hàng
def calculate (aData, c1, v1, c2, v2):
    t1 = 0
    t2 = 0
    for aR in aData:
        if aR[c1] == v1 and aR[c2] == v2: t1 = t1 + 1
        if aR[c2] == v2: t2 = t2 + 1
        
    return t1 / t2

# Mô tả bài toánz
# -----------------------------------------------------------------------------------------
# Dữ liệu đầu vào: một bảng dữ liệu (mảng 2 chiều).
# Truy vấn       : một bản ghi, trong đó, bị khuyết đi một trường dữ liệu (để là None).
# Dữ liệu đầu ra : một từ điển, trong đó, khóa: biến cố, value: xác suất xảy ra biến cố đó.

# data = \
# [
#     ['Sunny',       'Hot',      'High',     'Weak',     'no'],
#     ['Sunny',       'Hot',      'High',     'Strong',   'no'],
#     ['Overcast',    'Hot',      'High',     'Weak',     'yes'],
#     ['Rain',        'Mild',     'High',     'Weak',     'yes'],
#     ['Rain',        'Cool',     'Normal',   'Weak',     'yes'],
#     ['Rain',        'Cool',     'Normal',   'Strong',   'no'],
#     ['Overcast',    'Cool',     'Normal',   'Strong',   'yes'],
#     ['Overcast',    'Mild',     'High',     'Weak',     'no'],
#     ['Sunny',       'Cool',     'Normal',   'Weak',     'yes'],
#     ['Rain',        'Mild',     'Normal',   'Weak',     'yes']
# ]

def train_data (data):
    # row   : đại diện cho số lượng bản ghi
    # column: đại diện cho số trường dữ liệu
    row = len(data)
    column = len(data[0])

    # print(f"Số trường dữ liệu   : {column}")
    # print(f"Kích dữ liệu đầu vào: {row} (bản ghi)", end="\n\n")

    # data_init: ({}, {}, {}, {}, {})
    data_init = tuple(dict() for i in range(column)) 

    # A.Tính xác suất xảy ra của từng "biến cố", tương ứng với mỗi "trường dữ liệu"
    # A.1: đếm số lượng xuất hiện
    for aR in data:
        # i: thứ tự trường dữ liệu
        for i in range(column):
            # Lấy dữ liệu "từ điển", tương ứng với vị trí trường dữ liệu
            aDict = data_init[i]

            # Kiểm tra xem trong "từ điển" đó đã có khóa (key) hay chưa, nếu chưa, thì tăng số lượng lên 1
            key = aR[i]

            if key in aDict.keys():
                aDict[key] = aDict[key] + 1
            else:
                aDict[key] = 1
    # A.2: tính tỉ lệ (xác suất)
    for aDict in data_init: 
        for aKey in aDict.keys(): aDict[aKey] = aDict[aKey] / row

    # # Hiển thị
    print()
    print_data(data_init)

    return data_init

def NaiveBayes (data, data_input):
    # row   : đại diện cho số lượng bản ghi
    # column: đại diện cho số trường dữ liệu
    row = len(data)
    column = len(data[0])

    data_init = train_data(data)
    # data_input = ['Sunny','Cool', 'High', 'Strong', None]
    if not (isinstance(data_input, list) and len(data_input) == column): raise Exception("Định dạng đầu vào không hợp lệ.")

    # Thứ tự của trường dữ liệu cần truy vấn (chứa "None")
    ithC = data_input.index(None)

    data_temporary = {}

    for i in range(column):
        # Xét các trường dữ liệu khác với trường dữ liệu chứa "None"
        if i == ithC: continue

        aDict = data_init[i]

        # Với mỗi khóa trong trường dữ liệu truy vấn (Y)
        # Cập nhật xác suất P(X_k | Y) tương ứng với các trường dữ liệu khác
        for aKey_Y in data_init[ithC].keys():
            for aKey_X in aDict.keys(): 

                data_temporary[(aKey_X, aKey_Y)] = calculate(data, i, aKey_X ,ithC, aKey_Y)

    # Hiển thị dữ liệu tính toán tạm thời
    print()
    print_data_dictionary(data_temporary)

    data_output = {}
    for aKey_Y in data_init[ithC].keys():

        r = 1
        for x_o in data_input:
            if (x_o == None): continue

            # print(f"{x_o} {aKey_Y} {data_temporary.get((x_o, aKey_Y))}")

            r = r * data_temporary.get((x_o, aKey_Y), 0)

        r = r * data_init[ithC][aKey_Y]

        # print(f"r: {r}")

        data_output[aKey_Y] = r

    # Hiển thị kết quả đầu ra
    print()
    print_data_dictionary(data_output)

    # print()
    return data_output

# Hiển thị kết quả đầu ra cho tất cả các khả năng
# print_data_dictionary(NaiveBayes(data, data_input=['Sunny','Cool', 'High', 'Strong', None]))

# Dự đoán: chọn khả năng có kết quả cao nhất
def NaiveBayes_predict(data, data_input):

    max = -1
    option = None

    oo = NaiveBayes(data, data_input)
    print_data_dictionary(oo)

    for key, value in oo.items():
        if value > max:
            max = value
            option = key

    return option

# print(f"NaiveBayes_predict: {NaiveBayes_predict(data, data_input=['Sunny','Cool', 'High', 'Strong', None])}")

# Dừng màn hình "terminal" khi chạy trên Linux
# input()