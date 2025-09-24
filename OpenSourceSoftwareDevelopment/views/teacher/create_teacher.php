<?php
require_once __DIR__ . '/../../functions/auth.php';
checkLogin(__DIR__ . '/../../index.php');

// Thiết lập title cho trang
$pageTitle = 'Thêm giảng viên mới - DNU OpenSource';
include __DIR__ . '/../header.php';
?>

<body>
    <div class="container mt-3">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h3 class="mt-3 mb-4 text-center">THÊM GIẢNG VIÊN MỚI</h3>
                
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
                
                <form action="../../handle/teacher_process.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="teacher_code" class="form-label">Mã giảng viên</label>
                        <input type="text" class="form-control" id="teacher_code" name="teacher_code" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="teacher_name" class="form-label">Họ và tên</label>
                        <input type="text" class="form-control" id="teacher_name" name="teacher_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="teacher_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="teacher_email" name="teacher_email" required>
                    </div>
                    
                    <div class="gap-2 text-center">
                        <button type="submit" class="btn btn-primary">Thêm giảng viên</button>
                        <a href="../teacher.php" class="btn btn-secondary">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
