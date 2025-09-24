<?php
require_once __DIR__ . '/../../functions/auth.php';
checkLogin(__DIR__ . '/../../index.php');

// Thiết lập title cho trang
$pageTitle = 'Thêm đề tài mới - DNU OpenSource';
include __DIR__ . '/../header.php';
?>

<body>
    <div class="container mt-3">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h3 class="mt-3 mb-4 text-center">THÊM ĐỀ TÀI NGHIÊN CỨU MỚI</h3>
                <br>
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
                
                <div class="">
                    <div class="card-body">
                        <form action="../../handle/project_process.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="create">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="project_code" class="form-label">Mã đề tài</label>
                                    <input type="text" class="form-control" id="project_code" name="project_code" required>
                                </div>
                                
                                <div class="col-md-9">
                                    <label for="project_name" class="form-label">Tên đề tài</label>
                                    <input type="text" class="form-control" id="project_name" name="project_name" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="date_start" class="form-label">Ngày bắt đầu</label>
                                    <input type="date" class="form-control" id="date_start" name="date_start" required>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="date_finish" class="form-label">Ngày kết thúc</label>
                                    <input type="date" class="form-control" id="date_finish" name="date_finish" required>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Trạng thái</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="Not started">Chưa bắt đầu</option>
                                        <option value="In progress">Đang thực hiện</option>
                                        <option value="Completed">Hoàn thành</option>
                                        <option value="Canceled">Đã hủy</option>
                                        <option value="Pending extension">Đang chờ gia hạn</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="number_extension" class="form-label">Số lần gia hạn còn lại</label>
                                    <div class="form-control bg-light">2</div>
                                    <div class="form-text">Mặc định mỗi đề tài có 2 lần gia hạn</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="project_file" class="form-label">Đơn đăng ký đề tài nghiên cứu khoa học</label>
                                    <input type="file" class="form-control" id="project_file" name="project_file" 
                                           accept=".pdf" required>
                                </div>
                            </div>
                            
                            <div class="gap-2 text-center">
                                <button type="submit" class="btn btn-primary">Thêm đề tài</button>
                                <a href="../project.php" class="btn btn-secondary me-md-2">Hủy</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
