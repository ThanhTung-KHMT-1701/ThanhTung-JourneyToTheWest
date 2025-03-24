file = open("./iris_small.csv", "r", encoding="utf-8")
data = []

for aLine in file.readlines(): 
    # Bỏ cột đầu tiên (ID)
    data.append(str(aLine).replace("\n", "").split(",")[1:])

# Bỏ đi dòng đầu tiên chứa tên các trường dữ liệu
data = data[1:]

# Hiển thị
# print(data)

file.close()

data_input=[5, 4, 1.11, 1.2, None]