import os
import io
import sys

def print_matrix (M, pre = ""):
    # Trường hợp ma trận chưa có dữ liệu
    if isinstance(M, list) == False or len(M) == 0: return "None"

    res = io.StringIO()

    # Đánh dấu địa chỉ của luồng đầu ra để trả lại khi kết thúc hàm
    standard_output = sys.stdout

    sys.stdout = res
    res.write("\n")

    R = len(M)

    if isinstance(M[0], list):
        # Trường hợp M là ma trận 2D
        C = len(M[0])
        for r in range(R):
            print("{}".format(pre), end="")
            for c in range(C): print(" {:+12.6f} ".format(M[r][c]), end="")
            print()
    else:
        # Trường hợp M là ma trận 1D
        C = 1
        print("{}".format(pre), end="")
        for r in range(R): print(" {:+12.6f} \n".format(M[r]), end="")
        print()

    # Trả lại luồng đầu ra cho hệ thống
    sys.stdout = standard_output

    return res.getvalue()

def print_title(X):
    res = io.StringIO()

    # Đánh dấu địa chỉ của luồng đầu ra để trả lại khi kết thúc hàm
    standard_output = sys.stdout

    sys.stdout = res

    print("".center(os.get_terminal_size().columns, "-"), end="")
    print(f"{X}".center(os.get_terminal_size().columns, " "), end="")
    print("".center(os.get_terminal_size().columns, "-"), end="")

    sys.stdout = standard_output

    return res.getvalue()