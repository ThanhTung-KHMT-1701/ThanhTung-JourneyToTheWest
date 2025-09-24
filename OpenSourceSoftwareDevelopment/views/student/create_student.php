<?php
require_once __DIR__ . '/../../functions/auth.php';
checkLogin(__DIR__ . '/../../index.php');

// Thiết lập title cho trang
$pageTitle = 'Thêm sinh viên mới - DNU OpenSource';
include __DIR__ . '/../header.php';
?>

<body>
    <div class="container mt-3">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h3 class="mt-3 mb-4 text-center">THÊM SINH VIÊN MỚI</h3>
                
                <?php
                // Hiển thị thông báo lỗi
                if (isset($_GET['error'])) {
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        ' . htmlspecialchars($_GET['error']) . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                }
                ?>
                <script>
                // Sau 3 giây sẽ tự động ẩn alert
                setTimeout(() => {
                    let alertNode = document.querySelector('.alert');
                    if (alertNode) {
                        let bsAlert = bootstrap.Alert.getOrCreateInstance(alertNode);
                        bsAlert.close();
                    }
                }, 3000);
                </script>
                
                <form action="../../handle/student_process.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="student_code" class="form-label">Mã sinh viên</label>
                        <input type="text" class="form-control" id="student_code" name="student_code" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_name" class="form-label">Họ và tên</label>
                        <input type="text" class="form-control" id="student_name" name="student_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="student_email" name="student_email" required>
                    </div>
                    
                    <div class="gap-2 text-center">
                        <button type="submit" class="btn btn-primary">Thêm sinh viên</button>
                        <a href="../student.php" class="btn btn-secondary">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
