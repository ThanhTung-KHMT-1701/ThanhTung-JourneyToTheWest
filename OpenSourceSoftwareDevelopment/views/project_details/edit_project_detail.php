<?php
require_once __DIR__ . '/../../functions/auth.php';
checkLogin(__DIR__ . '/../../index.php');

// Thiết lập title cho trang
$pageTitle = 'Chỉnh sửa chi tiết đề tài - DNU OpenSource';
include __DIR__ . '/../header.php';
?>

<body>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Cài đặt style cho Select2 */
        .select2-container--default .select2-selection--single {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            height: 38px;
            padding: 0.375rem 0.75rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px;
            padding-left: 0;
        }
        .select2-dropdown {
            border: 1px solid #ced4da;
        }
        .select2-container--open .select2-dropdown {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
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
        
        /* Style cho các bảng */
        .table {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }
        
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6 !important;
        }
        
        .table thead th {
            border-bottom: 2px solid #dee2e6 !important;
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
        
        /* Style cho sinh viên Leader */
        .leader-row {
            color: #0d6efd !important;
            font-weight: 600 !important;
        }
        
        .leader-row td {
            color: #0d6efd !important;
        }
    </style>
</head>

<body>
    <div class="container mt-3">
        <h3 class="mt-3 mb-4 text-center">CHỈNH SỬA CHI TIẾT ĐỀ TÀI</h3>
        <br>
        <?php
            // Kiểm tra có ID không
            if (!isset($_GET['id']) || empty($_GET['id'])) {
                header("Location: ../project_details.php?error=Không tìm thấy chi tiết đề tài");
                exit;
            }
            
            $id = $_GET['id'];
            
            // Lấy thông tin chi tiết đề tài
            require_once __DIR__ . '/../../handle/project_details_process.php';
            $project_detail = handleGetProjectDetailById($id);

            if (!$project_detail) {
                header("Location: ../project_details.php?error=Không tìm thấy chi tiết đề tài");
                exit;
            }

            // Lấy danh sách đề tài, giảng viên, sinh viên
            $projects = handleGetAllProjects();
            $teachers = handleGetAllTeachers();
            $students = handleGetAllStudents();
            
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
                <div class="col-md-8">
                    <div class="">
                        <div class="card-body">
                            <form action="../../handle/project_details_process.php" method="POST">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($project_detail['id']); ?>">

                                <div class="mb-3">
                                    <label for="project_id" class="form-label">Đề tài</label>
                                    <select class="form-select select2" id="project_id" name="project_id" required>
                                        <option value="">-- Chọn đề tài --</option>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?= $project['id'] ?>" <?= ($project['id'] == $project_detail['project_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($project['project_code']) ?> - <?= htmlspecialchars($project['project_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="teacher_id" class="form-label">Giảng viên</label>
                                    <select class="form-select select2" id="teacher_id" name="teacher_id" required>
                                        <option value="">-- Chọn giảng viên --</option>
                                        <?php foreach ($teachers as $teacher): ?>
                                            <option value="<?= $teacher['id'] ?>" <?= ($teacher['id'] == $project_detail['teacher_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($teacher['teacher_code']) ?> - <?= htmlspecialchars($teacher['teacher_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">Sinh viên</label>
                                    <select class="form-select select2" id="student_id" name="student_id" required>
                                        <option value="">-- Chọn sinh viên --</option>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?= $student['id'] ?>" <?= ($student['id'] == $project_detail['student_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($student['student_code']) ?> - <?= htmlspecialchars($student['student_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="student_role" class="form-label">Vai trò</label>
                                    <select class="form-select" id="student_role" name="student_role" required>
                                        <option value="Member" <?= ($project_detail['student_role'] == 'Member') ? 'selected' : '' ?>>Member</option>
                                        <option value="Leader" <?= ($project_detail['student_role'] == 'Leader') ? 'selected' : '' ?>>Leader</option>
                                    </select>
                                </div>

                                <div class="gap-2 text-center">
                                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                                    <a href="../project_details.php" class="btn btn-secondary me-md-2">Hủy</a>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Khởi tạo Select2 cho các dropdown
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('body')
            });
            
            // Thêm border cho Select2 container khi focus
            $('.select2').on('select2:open', function (e) {
                $(this).closest('.mb-3').find('.select2-container--default .select2-selection--single').css('border-color', '#86b7fe');
                $(this).closest('.mb-3').find('.select2-container--default .select2-selection--single').css('box-shadow', '0 0 0 0.25rem rgba(13, 110, 253, 0.25)');
            });
            
            // Trở lại border bình thường khi mất focus
            $('.select2').on('select2:close', function (e) {
                $(this).closest('.mb-3').find('.select2-container--default .select2-selection--single').css('border-color', '#ced4da');
                $(this).closest('.mb-3').find('.select2-container--default .select2-selection--single').css('box-shadow', 'none');
            });
            
            // Load danh sách sinh viên và giảng viên khi trang load
            const projectId = $('#project_id').val();
            if (projectId) {
                loadProjectDetails(projectId);
            }
        });
        
        function loadProjectDetails(projectId) {
            $.get('../../handle/project_process.php', {
                ajax: 'true',
                action: 'get_project_details',
                project_id: projectId
            }).done(function(response) {
                if (response.success) {
                    renderProjectDetails(response.teachers, response.students);
                } else {
                    $('#dynamic-content').html('<div class="alert alert-danger">Lỗi: ' + response.error + '</div>');
                }
            }).fail(function() {
                $('#dynamic-content').html('<div class="alert alert-danger">Lỗi kết nối server</div>');
            });
        }
        
        function renderProjectDetails(teachers, students) {
            let html = '';
            
            // Bảng giảng viên
            if (teachers && teachers.length > 0) {
                html += '<div class="row justify-content-center"><div class="col-md-8">';
                html += '<div class="dynamic-table">';
                html += '<div class="table-responsive"><table class="table table-striped table-bordered teacher-table">';
                html += '<thead class="table-primary"><tr>';
                html += '<th>Mã giảng viên</th><th>Họ và tên</th><th class="text-center-col">Tổng số đề tài</th><th class="text-center-col">Thao tác</th>';
                html += '</tr></thead><tbody>';
                
                teachers.forEach(function(teacher) {
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
                html += '<div class="row justify-content-center"><div class="col-md-8">';
                html += '<div class="dynamic-table">';
                html += '<div class="table-responsive"><table class="table table-striped table-bordered student-table">';
                html += '<thead class="table-primary"><tr>';
                html += '<th>Mã sinh viên</th><th>Họ và tên</th><th class="text-center-col">Tổng số đề tài</th><th class="text-center-col">Thao tác</th>';
                html += '</tr></thead><tbody>';
                
                students.forEach(function(student) {
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
