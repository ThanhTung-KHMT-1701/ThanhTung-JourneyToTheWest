import pandas as pd
import matplotlib.pyplot as plt

LossHistory = pd.read_csv("loss_history.csv")["Loss"].tolist()

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