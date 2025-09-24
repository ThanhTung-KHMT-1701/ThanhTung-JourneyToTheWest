<?php
require_once __DIR__ . '/../../functions/auth.php';
checkLogin(__DIR__ . '/../../index.php');

// Thiết lập title cho trang
$pageTitle = 'Chỉnh sửa sinh viên - DNU OpenSource';
include __DIR__ . '/../header.php';
?>

<style>
/* CSS cho các hiệu ứng và bảng động */
.dynamic-table {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    overflow: hidden;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.dynamic-table .table {
    margin-bottom: 0 !important;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    max-width: 100%;
    box-sizing: border-box;
    width: 100% !important;
}

/* Force table nằm trong giới hạn container - loại bỏ để tránh conflict */

/* Đảm bảo container tuân thủ Bootstrap grid */
.container {
    overflow-x: hidden;
}

/* Loại bỏ hoàn toàn scroll ngang */
html, body {
    overflow-x: hidden !important;
    max-width: 100vw !important;
    box-sizing: border-box;
}

/* Tạo wrapper để control kích thước table theo Bootstrap grid */
.table-container-wrapper {
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    box-sizing: border-box;
}

/* Force table container tuân thủ Bootstrap grid */
.col-md-10 .table-container-wrapper {
    max-width: 100%;
    width: 100%;
}

/* Đảm bảo tất cả elements không vượt quá viewport */
* {
    max-width: 100%;
    box-sizing: border-box;
}

.table tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}

.loading-spinner {
    display: block;
    text-align: center;
    margin: 20px 0;
}

.text-center-custom {
    text-align: center !important;
}

.text-right-custom {
    text-align: right !important;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6 !important;
}

.table thead th {
    border-bottom: 2px solid #dee2e6 !important;
}

/* CSS cho tiêu đề bảng với background primary và màu trắng */
.table-primary,
.table-primary > th,
.table-primary > td {
    background-color: #0d6efd !important;
    color: white !important;
    text-align: center !important;
    font-weight: 500 !important;
}

.table thead.table-primary th {
    background-color: #0d6efd !important;
    color: white !important;
    text-align: center !important;
    border-color: #0a58ca !important;
}

/* Đảm bảo table responsive và tuân thủ Bootstrap grid */
.student-table {
    width: 100% !important;
    max-width: 100% !important;
    table-layout: fixed !important;
    box-sizing: border-box;
    word-wrap: break-word;
    border-collapse: collapse;
}

/* Điều chỉnh padding để tiết kiệm không gian */
.student-table th,
.student-table td {
    padding: 6px 4px !important;
    word-wrap: break-word;
    overflow-wrap: break-word;
    box-sizing: border-box;
    vertical-align: middle;
}

/* Chiều rộng các cột theo tỷ lệ phần trăm - điều chỉnh để fit tốt hơn */
.student-table th:nth-child(1),
.student-table td:nth-child(1) {
    width: 18% !important;
    max-width: 18% !important;
    font-size: 1em;
}

.student-table th:nth-child(2),
.student-table td:nth-child(2) {
    width: 45% !important;
    max-width: 45% !important;
    white-space: normal;
    line-height: 1.3;
    font-size: 1em;
}

.student-table th:nth-child(3),
.student-table td:nth-child(3),
.student-table th:nth-child(4),
.student-table td:nth-child(4) {
    width: 13% !important;
    max-width: 13% !important;
    text-align: center;
    font-size: 1em;
}

.student-table th:nth-child(5),
.student-table td:nth-child(5) {
    width: 11% !important;
    max-width: 11% !important;
    text-align: center;
    font-size: 1em;
}

/* Căn giữa cho các cột có class text-center-col */
.text-center-col {
    text-align: center !important;
    vertical-align: middle !important;
}

/* Styling cho icon actions */
.action-icon {
    color: #0d6efd;
    cursor: pointer;
    font-size: 1.1em;
    transition: all 0.2s ease;
}

.action-icon:hover {
    color: #0a58ca;
    transform: scale(1.1);
}

/* Animation cho icon xoay */
@keyframes spin-continuous {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.rotate-icon {
    color: #0d6efd;
    cursor: pointer;
    font-size: 1.1em;
    transition: all 0.2s ease;
}

.rotate-icon:hover {
    color: #0a58ca;
    transform: scale(1.1);
}

/* Styling cho dòng Leader */
.leader-row {
    background-color: rgba(13, 110, 253, 0.1) !important;
    border-left: 4px solid #0d6efd !important;
}

.leader-row:hover {
    background-color: rgba(13, 110, 253, 0.15) !important;
}

/* Responsive cho mobile và tablet - giữ nguyên tỷ lệ nhưng có thể ẩn cột */
@media (max-width: 992px) {
    .student-table th:nth-child(3),
    .student-table td:nth-child(3),
    .student-table th:nth-child(4),
    .student-table td:nth-child(4) {
        font-size: 1em;
    }
}

@media (max-width: 768px) {
    /* Ẩn cột ít quan trọng trên mobile */
    .student-table th:nth-child(3),
    .student-table td:nth-child(3),
    .student-table th:nth-child(4),
    .student-table td:nth-child(4) {
        display: none;
    }
    
    /* Điều chỉnh lại tỷ lệ cột khi chỉ còn 3 cột */
    .student-table th:nth-child(1),
    .student-table td:nth-child(1) {
        width: 25%;
    }
    
    .student-table th:nth-child(2),
    .student-table td:nth-child(2) {
        width: 60%;
        font-size: 1em;
    }
    
    .student-table th:nth-child(5),
    .student-table td:nth-child(5) {
        width: 15%;
    }
}

@media (max-width: 576px) {
    .student-table {
        font-size: 1em;
    }
    
    .student-table th:nth-child(2),
    .student-table td:nth-child(2) {
        font-size: 1em;
        line-height: 1.2;
    }
}

/* Hiệu ứng cho alert duplicate */
.duplicate-warning {
    border-left: 4px solid #f0ad4e;
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Transition mượt cho table */
.dynamic-table table {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from { 
        opacity: 0; 
        transform: translateY(20px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}
</style>

<body>
    <div class="container mt-3">
        <h3 class="mt-3 mb-4 text-center">CHỈNH SỬA SINH VIÊN</h3>
        <br>
        <?php
            // Kiểm tra có ID không
            if (!isset($_GET['id']) || empty($_GET['id'])) {
                header("Location: ../student.php?error=Không tìm thấy sinh viên");
                exit;
            }
            
            $id = $_GET['id'];
            
            // Lấy thông tin sinh viên
            require_once __DIR__ . '/../../handle/student_process.php';
            $student = handleGetStudentById($id);

            if (!$student) {
                header("Location: ../student.php?error=Không tìm thấy sinh viên");
                exit;
            }
            
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
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="">
                        <div class="card-body">
                            <form action="../../handle/student_process.php" method="POST">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($student['id']); ?>">

                                <div class="mb-3">
                                    <label for="student_code" class="form-label">Mã sinh viên</label>
                                    <input type="text" class="form-control" id="student_code" name="student_code"
                                        value="<?php echo htmlspecialchars($student['student_code']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="student_name" class="form-label">Họ và tên</label>
                                    <input type="text" class="form-control" id="student_name" name="student_name"
                                        value="<?php echo htmlspecialchars($student['student_name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="student_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="student_email" name="student_email"
                                        value="<?php echo htmlspecialchars($student['student_email']); ?>" required>
                                </div>

                                <div class="gap-2 text-center">
                                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                                    <a href="../student.php" class="btn btn-secondary me-md-2">Hủy</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <!-- Khu vực hiển thị danh sách đề tài đã tham gia -->
        <div class="row mt-4 justify-content-center">
            <div class="col-md-8">
                <div id="dynamic-content">
                    <div class="loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                        <p class="mt-2">Đang tải danh sách đề tài...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Lấy ID sinh viên từ URL
            const studentId = '<?php echo $student['id']; ?>';
            
            // Tự động tải danh sách đề tài khi trang được load
            if (studentId) {
                loadStudentProjects(studentId);
            }
            
            function showLoading() {
                $('#dynamic-content .loading-spinner').show();
            }
            
            function hideLoading() {
                $('#dynamic-content .loading-spinner').hide();
            }
            
            function loadStudentProjects(studentId) {
                showLoading();
                $.get('../../handle/project_process.php', {
                    ajax: 'true',
                    action: 'get_student_projects',
                    student_id: studentId
                }).done(function(response) {
                    hideLoading();
                    if (response.success) {
                        renderProjectsList(response.projects);
                    } else {
                        $('#dynamic-content').html('<div class="alert alert-danger">Lỗi: ' + response.error + '</div>');
                    }
                }).fail(function() {
                    hideLoading();
                    $('#dynamic-content').html('<div class="alert alert-danger">Lỗi kết nối server</div>');
                });
            }
            
            function renderProjectsList(projects) {
                let html = '';
                
                if (projects && projects.length > 0) {
                    // Sử dụng wrapper để kiểm soát kích thước theo Bootstrap grid
                    html += '<div class="table-container-wrapper">';
                    html += '<div class="dynamic-table">';
                    html += '<div class="table-responsive"><table class="table table-striped table-bordered student-table">';
                    html += '<thead class="table-primary"><tr>';
                    html += '<th>Mã đề tài</th><th>Tên đề tài</th>';
                    html += '<th class="text-center-col d-none d-md-table-cell">Tổng số GV</th>';
                    html += '<th class="text-center-col d-none d-md-table-cell">Tổng số SV</th>';
                    html += '<th class="text-center-col">Thao tác</th>';
                    html += '</tr></thead><tbody>';
                    
                    projects.forEach(function(project) {
                        html += '<tr>';
                        html += '<td>' + project.project_code + '</td>';
                        html += '<td>' + project.project_name + '</td>';
                        html += '<td class="text-center-col d-none d-md-table-cell">' + project.total_teachers + '</td>';
                        html += '<td class="text-center-col d-none d-md-table-cell">' + project.total_students + '</td>';
                        html += '<td class="text-center-col">';
                        // Icon xem thông tin đề tài
                        html += '<i class="bi bi-eye action-icon" onmouseover="this.className=\'bi bi-eye-fill action-icon\'" onmouseout="this.className=\'bi bi-eye action-icon\'" onclick="window.open(\'http://localhost/ThanhTung-FIT4111-OpenSourceSoftwareDevelopment/views/project/edit_project.php?id=' + project.id + '\', \'_blank\')" title="Xem thông tin đề tài"></i>';
                        html += '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table></div></div></div>';
                } else {
                    html = '<div class="alert alert-info text-center">';
                    html += '<i class="bi bi-info-circle"></i> Sinh viên này chưa tham gia đề tài nào.';
                    html += '</div>';
                }
                
                $('#dynamic-content').html(html);
            }
        });
        
        // Function để bắt đầu hiệu ứng quay
        function startSpin(element) {
            element.style.animation = 'spin-continuous 1s linear infinite';
            element.style.color = '#0a58ca';
        }
        
        // Function để dừng hiệu ứng quay
        function stopSpin(element) {
            element.style.animation = '';
            element.style.color = '#0d6efd';
        }
    </script>
</body>

</html>