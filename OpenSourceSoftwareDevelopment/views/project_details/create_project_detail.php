<?php
require_once __DIR__ . '/../../functions/auth.php';
checkLogin(__DIR__ . '/../../index.php');
require_once __DIR__ . '/../../handle/project_details_process.php';

// Lấy danh sách đề tài, giảng viên, sinh viên
$projects = handleGetAllProjects();
$teachers = handleGetAllTeachers();
$students = handleGetAllStudents();

// Thiết lập title cho trang
$pageTitle = 'Thêm chi tiết đề tài mới - DNU OpenSource';
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
        
        .table-title {
            background: #0d6efd !important;
            color: white !important;
            text-align: center !important;
            padding: 12px !important;
            margin-bottom: 0 !important;
            border-radius: 0.375rem 0.375rem 0 0 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .table-title img {
            width: 24px;
            height: 24px;
            filter: brightness(0) invert(1);
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
        
        /* Icon quay liên tục khi hover */
        .rotate-icon {
            cursor: pointer !important;
            color: #0d6efd !important;
            font-size: 1.1em !important;
            display: inline-block !important;
        }
        
        @keyframes spin-continuous {
            0% { 
                transform: rotate(0deg); 
            }
            100% { 
                transform: rotate(360deg); 
            }
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
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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
        
        .loading-spinner {
            display: none;
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
    <div class="container mt-3">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h3 class="mt-3 mb-4 text-center">THÊM CHI TIẾT ĐỀ TÀI MỚI</h3>
                
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
                        <form action="../../handle/project_details_process.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="project_id" class="form-label">Đề tài</label>
                        <select class="form-select select2" id="project_id" name="project_id" required>
                            <option value="">-- Chọn đề tài --</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['project_code']) ?> - <?= htmlspecialchars($project['project_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="teacher_id" class="form-label">Giảng viên</label>
                        <select class="form-select select2" id="teacher_id" name="teacher_id" required>
                            <option value="">-- Chọn giảng viên --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['teacher_code']) ?> - <?= htmlspecialchars($teacher['teacher_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Sinh viên</label>
                        <select class="form-select select2" id="student_id" name="student_id" required>
                            <option value="">-- Chọn sinh viên --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['student_code']) ?> - <?= htmlspecialchars($student['student_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_role" class="form-label">Vai trò</label>
                        <select class="form-select" id="student_role" name="student_role" required>
                            <option value="Member">Member</option>
                            <option value="Leader">Leader</option>
                        </select>
                    </div>
                    
                    <div class="gap-2 text-center">
                        <button type="submit" class="btn btn-primary">Thêm chi tiết đề tài</button>
                        <a href="../project_details.php" class="btn btn-secondary">Hủy</a>
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
            
            // Xử lý sự kiện thay đổi các dropdown
            $('#project_id, #teacher_id, #student_id').on('change', function() {
                handleSelectionChange();
            });
            
            function handleSelectionChange() {
                const projectId = $('#project_id').val();
                const teacherId = $('#teacher_id').val();
                const studentId = $('#student_id').val();
                
                // Xóa nội dung cũ và reset trạng thái
                $('#dynamic-content').html('');
                resetFormState();
                
                // Kiểm tra trùng lặp nếu cả 3 trường đều được chọn
                if (projectId && teacherId && studentId) {
                    checkDuplicate(projectId, teacherId, studentId);
                    return;
                }
                
                // Kiểm tra các trường hợp khác
                if (projectId && !teacherId && !studentId) {
                    // Trường hợp 1: Chỉ chọn đề tài
                    loadProjectDetails(projectId);
                } else if (!projectId && teacherId && !studentId) {
                    // Trường hợp 2: Chỉ chọn giảng viên
                    loadTeacherProjects(teacherId);
                } else if (!projectId && !teacherId && studentId) {
                    // Trường hợp 3: Chỉ chọn sinh viên
                    loadStudentProjects(studentId);
                } else if (projectId && teacherId && !studentId) {
                    // Trường hợp 4: Chọn đề tài và giảng viên
                    loadProjectAndTeacherDetails(projectId, teacherId);
                } else if (projectId && !teacherId && studentId) {
                    // Trường hợp 5: Chọn đề tài và sinh viên
                    loadProjectAndStudentDetails(projectId, studentId);
                } else if (!projectId && teacherId && studentId) {
                    // Trường hợp 6: Chọn giảng viên và sinh viên
                    loadTeacherStudentProjects(teacherId, studentId);
                }
            }
            
            function showLoading() {
                $('#dynamic-content').html('<div class="loading-spinner"><i class="bi bi-arrow-repeat"></i> Đang tải dữ liệu...</div>');
                $('.loading-spinner').show();
            }
            
            function resetFormState() {
                // Reset trạng thái nút submit và ẩn cảnh báo
                $('button[type="submit"]').prop('disabled', false).removeClass('btn-secondary').addClass('btn-primary');
                $('.duplicate-warning').remove();
            }
            
            function checkDuplicate(projectId, teacherId, studentId) {
                showLoading();
                $.get('../../handle/project_process.php', {
                    ajax: 'true',
                    action: 'check_duplicate',
                    project_id: projectId,
                    teacher_id: teacherId,
                    student_id: studentId
                }).done(function(response) {
                    if (response.success) {
                        if (response.exists) {
                            // Hiển thị cảnh báo và vô hiệu hóa nút submit
                            showDuplicateWarning();
                            disableSubmitButton();
                        } else {
                            // Không trùng lặp, tiếp tục hiển thị danh sách
                            loadProjectAndTeacherDetails(projectId, teacherId);
                        }
                    } else {
                        $('#dynamic-content').html('<div class="alert alert-danger">Lỗi: ' + response.error + '</div>');
                    }
                }).fail(function() {
                    $('#dynamic-content').html('<div class="alert alert-danger">Lỗi kết nối server</div>');
                });
            }
            
            function showDuplicateWarning() {
                const warningHtml = '<div class="row justify-content-center"><div class="col-md-6">' +
                    '<div class="alert alert-warning duplicate-warning mt-3">' +
                    '<i class="bi bi-exclamation-triangle-fill"></i> ' +
                    '<strong>Cảnh báo:</strong> Bộ (mã đề tài, mã giảng viên, mã sinh viên) đã tồn tại trong cơ sở dữ liệu.' +
                    '</div></div></div>';
                $('#dynamic-content').html(warningHtml);
            }
            
            function disableSubmitButton() {
                $('button[type="submit"]').prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');
            }
            
            function loadProjectDetails(projectId) {
                showLoading();
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
            
            function loadTeacherProjects(teacherId) {
                showLoading();
                $.get('../../handle/project_process.php', {
                    ajax: 'true',
                    action: 'get_teacher_projects',
                    teacher_id: teacherId
                }).done(function(response) {
                    if (response.success) {
                        renderProjectsList(response.projects, 'Danh sách đề tài mà giảng viên đang hướng dẫn');
                    } else {
                        $('#dynamic-content').html('<div class="alert alert-danger">Lỗi: ' + response.error + '</div>');
                    }
                }).fail(function() {
                    $('#dynamic-content').html('<div class="alert alert-danger">Lỗi kết nối server</div>');
                });
            }
            
            function loadStudentProjects(studentId) {
                showLoading();
                $.get('../../handle/project_process.php', {
                    ajax: 'true',
                    action: 'get_student_projects',
                    student_id: studentId
                }).done(function(response) {
                    if (response.success) {
                        renderProjectsList(response.projects, 'Danh sách đề tài mà sinh viên đang tham gia');
                    } else {
                        $('#dynamic-content').html('<div class="alert alert-danger">Lỗi: ' + response.error + '</div>');
                    }
                }).fail(function() {
                    $('#dynamic-content').html('<div class="alert alert-danger">Lỗi kết nối server</div>');
                });
            }
            
            function loadProjectAndTeacherDetails(projectId, teacherId) {
                showLoading();
                $.get('../../handle/project_process.php', {
                    ajax: 'true',
                    action: 'get_project_and_teacher_details',
                    project_id: projectId,
                    teacher_id: teacherId
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
            
            function loadProjectAndStudentDetails(projectId, studentId) {
                showLoading();
                $.get('../../handle/project_process.php', {
                    ajax: 'true',
                    action: 'get_project_and_student_details',
                    project_id: projectId,
                    student_id: studentId
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
            
            function loadTeacherStudentProjects(teacherId, studentId) {
                showLoading();
                $.get('../../handle/project_process.php', {
                    ajax: 'true',
                    action: 'get_teacher_student_projects',
                    teacher_id: teacherId,
                    student_id: studentId
                }).done(function(response) {
                    if (response.success) {
                        renderProjectsList(response.projects, 'Danh sách đề tài mà giảng viên và sinh viên đã cùng tham gia');
                    } else {
                        $('#dynamic-content').html('<div class="alert alert-danger">Lỗi: ' + response.error + '</div>');
                    }
                }).fail(function() {
                    $('#dynamic-content').html('<div class="alert alert-danger">Lỗi kết nối server</div>');
                });
            }
            
            function renderProjectDetails(teachers, students) {
                let html = '';
                
                // Lấy project_id hiện tại để sử dụng cho link edit
                const currentProjectId = $('#project_id').val();
                
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
                        html += '<i class="bi bi-eye action-icon me-2" onmouseover="this.className=\'bi bi-eye-fill action-icon me-2\'" onmouseout="this.className=\'bi bi-eye action-icon me-2\'" onclick="window.open(\'http://localhost/ThanhTung-FIT4111-OpenSourceSoftwareDevelopment/views/student/edit_student.php?id=' + student.id + '\', \'_blank\')"></i>';
                        // Icon edit project detail (sử dụng project_detail_id)
                        if (student.project_detail_id) {
                            html += '<i class="bi bi-arrow-repeat rotate-icon" title="Chỉnh sửa chi tiết đề tài" onmouseover="startSpin(this)" onmouseout="stopSpin(this)" onclick="window.open(\'http://localhost/ThanhTung-FIT4111-OpenSourceSoftwareDevelopment/views/project_details/edit_project_detail.php?id=' + student.project_detail_id + '\', \'_blank\')"></i>';
                        }
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
            
            function renderProjectsList(projects, title) {
                let html = '';
                
                if (projects && projects.length > 0) {
                    html += '<div class="dynamic-table">';
                    html += '<div class="table-responsive"><table class="table table-striped table-bordered">';
                    html += '<thead class="table-primary"><tr>';
                    html += '<th>Mã đề tài</th><th>Tên đề tài</th><th class="text-center-col">Tổng số giảng viên</th><th class="text-center-col">Tổng số sinh viên</th><th class="text-center-col">Thao tác</th>';
                    html += '</tr></thead><tbody>';
                    
                    projects.forEach(function(project) {
                        html += '<tr>';
                        html += '<td>' + project.project_code + '</td>';
                        html += '<td>' + project.project_name + '</td>';
                        html += '<td class="text-center-col">' + project.total_teachers + '</td>';
                        html += '<td class="text-center-col">' + project.total_students + '</td>';
                        html += '<td class="text-center-col"><i class="bi bi-eye action-icon" onmouseover="this.className=\'bi bi-eye-fill action-icon\'" onmouseout="this.className=\'bi bi-eye action-icon\'" onclick="window.open(\'http://localhost/ThanhTung-FIT4111-OpenSourceSoftwareDevelopment/views/project/edit_project.php?id=' + project.id + '\', \'_blank\')"></i></td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table></div></div>';
                } else {
                    html = '<div class="alert alert-info">Không có dữ liệu để hiển thị</div>';
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
