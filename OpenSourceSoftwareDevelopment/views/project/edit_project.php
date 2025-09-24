<?php
require_once __DIR__ . '/../../functions/auth.php';
checkLogin(__DIR__ . '/../../index.php');

// Thiết lập title cho trang
$pageTitle = 'Chỉnh sửa đề tài - DNU OpenSource';
include __DIR__ . '/../header.php';
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Style cho các bảng động */
        .dynamic-table {
            margin-top: 20px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease-out forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Style cho header bảng */
        .table-primary th {
            background-color: #0d6efd !important;
            color: white !important;
            text-align: center !important;
            vertical-align: middle !important;
            font-weight: 600;
        }

        /* Style cho các cột cần căn giữa */
        .text-center-col {
            text-align: center !important;
            vertical-align: middle !important;
        }

        .action-icon {
            cursor: pointer;
            transition: all 0.2s ease;
            color: #0d6efd;
            font-size: 1.1em;
        }

        .action-icon:hover {
            color: #0a58ca;
            transform: scale(1.1);
        }

        /* Style cho sinh viên Leader */
        .leader-row {
            color: #0d6efd !important;
            font-weight: 600 !important;
        }

        .leader-row td {
            color: #0d6efd !important;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            margin: 20px 0;
        }

        /* Thêm hiệu ứng loading */
        .loading-spinner i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Style cho các bảng */
        .table {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 0 !important;
        }

        .table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }

        /* Đồng bộ chiều rộng cột "Họ và tên" cho bảng giảng viên và sinh viên */
        .teacher-table th:nth-child(2),
        .teacher-table td:nth-child(2),
        .student-table th:nth-child(2),
        .student-table td:nth-child(2) {
            width: 35% !important;
            min-width: 200px;
        }

        /* Chiều rộng cho các cột khác trong bảng giảng viên và sinh viên */
        .teacher-table th:nth-child(1),
        .teacher-table td:nth-child(1),
        .student-table th:nth-child(1),
        .student-table td:nth-child(1) {
            width: 20% !important;
        }

        .teacher-table th:nth-child(3),
        .teacher-table td:nth-child(3),
        .student-table th:nth-child(3),
        .student-table td:nth-child(3) {
            width: 20% !important;
        }

        .teacher-table th:nth-child(4),
        .teacher-table td:nth-child(4),
        .student-table th:nth-child(4),
        .student-table td:nth-child(4) {
            width: 25% !important;
        }
    </style>
</head>

<body>

    <body>
        <div class="container mt-3">
            <h3 class="mt-3 mb-4 text-center">CHỈNH SỬA ĐỀ TÀI NGHIÊN CỨU</h3>
            <br>
            <?php
            // Kiểm tra có ID không
            if (!isset($_GET['id']) || empty($_GET['id'])) {
                header("Location: ../project.php?error=Không tìm thấy đề tài");
                exit;
            }

            $id = $_GET['id'];

            // Lấy thông tin đề tài
            require_once __DIR__ . '/../../handle/project_process.php';
            $project = handleGetProjectById($id);

            if (!$project) {
                header("Location: ../project.php?error=Không tìm thấy đề tài");
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
                <div class="col-md-10">
                    <div class="">
                        <div class="card-body">
                            <form action="../../handle/project_process.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($project['id']); ?>">

                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="project_code" class="form-label">Mã đề tài</label>
                                        <input type="text" class="form-control" id="project_code" name="project_code"
                                            value="<?php echo htmlspecialchars($project['project_code']); ?>" required>
                                    </div>

                                    <div class="col-md-9">
                                        <label for="project_name" class="form-label">Tên đề tài</label>
                                        <input type="text" class="form-control" id="project_name" name="project_name"
                                            value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="date_start" class="form-label">Ngày bắt đầu</label>
                                        <div class="form-control bg-light">
                                            <?php echo date('d/m/Y', strtotime($project['date_start'])); ?>
                                        </div>
                                        <input type="hidden" name="date_start"
                                            value="<?php echo date('Y-m-d', strtotime($project['date_start'])); ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="date_finish" class="form-label">Ngày kết thúc</label>
                                        <div class="form-control bg-light">
                                            <?php echo date('d/m/Y', strtotime($project['date_finish'])); ?>
                                        </div>
                                        <input type="hidden" name="date_finish"
                                            value="<?php echo date('Y-m-d', strtotime($project['date_finish'])); ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="status" class="form-label">Trạng thái</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="Not started" <?php if ($project['status'] == 'Not started')
                                                echo 'selected'; ?>>Chưa bắt đầu</option>
                                            <option value="In progress" <?php if ($project['status'] == 'In progress')
                                                echo 'selected'; ?>>Đang thực hiện</option>
                                            <option value="Completed" <?php if ($project['status'] == 'Completed')
                                                echo 'selected'; ?>>Hoàn thành</option>
                                            <option value="Canceled" <?php if ($project['status'] == 'Canceled')
                                                echo 'selected'; ?>>Đã hủy</option>
                                            <option value="Pending extension" <?php if ($project['status'] == 'Pending extension')
                                                echo 'selected'; ?>>Đang chờ gia hạn</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="number_extension" class="form-label">Số lần gia hạn còn
                                            lại</label>
                                        <div class="form-control bg-light">
                                            <?php echo htmlspecialchars($project['number_extension']); ?>
                                        </div>
                                    </div>
                                </div>
                                <br>
                                <div class="row mb-3">
                                    <div class="col-md-10">
                                        <label for="project_file" class="form-label">Đăng xin đăng ký nghiên cứu khoa
                                            học</label>
                                        <input type="file" class="form-control" id="project_file" name="project_file"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-2 text-center">
                                        Xem lại bản cũ  
                                        <br>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      
                                        <i class="bi bi-eye action-icon"
                                            onmouseover="this.className='bi bi-eye-fill action-icon'"
                                            onmouseout="this.className='bi bi-eye action-icon'"
                                            onclick="window.open('../project.php?uuid=<?php echo htmlspecialchars($project['uuid']); ?>', '_blank')"
                                            title="Xem chi tiết đề tài"></i>
                                    </div>
                                </div>

                                <div class="gap-2 text-center">
                                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                                    <a href="../project.php" class="btn btn-secondary me-md-2">Hủy</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Khu vực hiển thị danh sách động -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div id="dynamic-content"></div>
                </div>
            </div>

        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function () {
                // Lấy project_id từ URL và tự động load danh sách
                const urlParams = new URLSearchParams(window.location.search);
                const projectId = urlParams.get('id');

                if (projectId) {
                    loadProjectDetails(projectId);
                }
            });

            function showLoading() {
                $('#dynamic-content').html('<div class="loading-spinner"><i class="bi bi-arrow-repeat"></i> Đang tải dữ liệu...</div>');
                $('.loading-spinner').show();
            }

            function loadProjectDetails(projectId) {
                showLoading();
                $.get('../../handle/project_process.php', {
                    ajax: 'true',
                    action: 'get_project_details',
                    project_id: projectId
                }).done(function (response) {
                    if (response.success) {
                        renderProjectDetails(response.teachers, response.students);
                    } else {
                        $('#dynamic-content').html('<div class="alert alert-danger">Lỗi: ' + response.error + '</div>');
                    }
                }).fail(function () {
                    $('#dynamic-content').html('<div class="alert alert-danger">Lỗi kết nối server</div>');
                });
            }

            function renderProjectDetails(teachers, students) {
                let html = '';

                // Bảng giảng viên
                if (teachers && teachers.length > 0) {
                    html += '<div class="row justify-content-center"><div class="col-md-6">';
                    html += '<div class="dynamic-table">';
                    html += '<div class="table-responsive"><table class="table table-striped table-bordered teacher-table">';
                    html += '<thead class="table-primary"><tr>';
                    html += '<th>Mã giảng viên</th><th>Họ và tên</th><th class="text-center-col">Tổng số đề tài</th><th class="text-center-col">Thao tác</th>';
                    html += '</tr></thead><tbody>';

                    teachers.forEach(function (teacher) {
                        html += '<tr>';
                        html += '<td>' + teacher.teacher_code + '</td>';
                        html += '<td>' + teacher.teacher_name + '</td>';
                        html += '<td class="text-center-col">' + teacher.total_projects + '</td>';
                        html += '<td class="text-center-col"><i class="bi bi-eye action-icon" onmouseover="this.className=\'bi bi-eye-fill action-icon\'" onmouseout="this.className=\'bi bi-eye action-icon\'" onclick="window.open(\'http://localhost/ThanhTung-FIT4111-OpenSourceSoftwareDevelopment/views/teacher/edit_teacher.php?id=' + teacher.id + '\', \'_blank\')"></i></td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table></div></div>';
                    html += '</div></div>';
                }

                // Bảng sinh viên
                if (students && students.length > 0) {
                    html += '<div class="row justify-content-center mt-4"><div class="col-md-6">';
                    html += '<div class="dynamic-table">';
                    html += '<div class="table-responsive"><table class="table table-striped table-bordered student-table">';
                    html += '<thead class="table-primary"><tr>';
                    html += '<th>Mã sinh viên</th><th>Họ và tên</th><th class="text-center-col">Tổng số đề tài</th><th class="text-center-col">Thao tác</th>';
                    html += '</tr></thead><tbody>';

                    students.forEach(function (student) {
                        // Kiểm tra nếu sinh viên có vai trò Leader
                        const rowClass = student.student_role === 'Leader' ? 'leader-row' : '';

                        html += '<tr class="' + rowClass + '">';
                        html += '<td>' + student.student_code + '</td>';
                        html += '<td>' + student.student_name + '</td>';
                        html += '<td class="text-center-col">' + student.total_projects + '</td>';
                        html += '<td class="text-center-col">';
                        // Icon xem thông tin sinh viên
                        html += '<i class="bi bi-eye action-icon" onmouseover="this.className=\'bi bi-eye-fill action-icon\'" onmouseout="this.className=\'bi bi-eye action-icon\'" onclick="window.open(\'http://localhost/ThanhTung-FIT4111-OpenSourceSoftwareDevelopment/views/student/edit_student.php?id=' + student.id + '\', \'_blank\')"></i>';
                        html += '</td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table></div></div>';
                    html += '</div></div>';
                }

                if (html === '') {
                    html = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
                }

                $('#dynamic-content').html(html);
            }
        </script>
    </body>

    </html>