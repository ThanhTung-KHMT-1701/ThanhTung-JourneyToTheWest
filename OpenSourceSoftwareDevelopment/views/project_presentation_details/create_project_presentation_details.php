<?php
require_once __DIR__ . '/../../functions/auth.php';
checkLogin(__DIR__ . '/../../index.php');
require_once __DIR__ . '/../../handle/project_presentation_details_process.php';

// Lấy danh sách báo cáo đề tài và giảng viên
$presentations = handleGetAllProjectPresentations();
$teachers = handleGetAllTeachers();

// Thiết lập title cho trang
$pageTitle = 'Thêm chi tiết báo cáo đề tài mới - DNU OpenSource';
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
        
        /* Đồng bộ chiều rộng cột "Họ và tên" cho bảng giảng viên */
        .teacher-table th:nth-child(2),
        .teacher-table td:nth-child(2) {
            width: 35% !important;
            min-width: 200px;
        }
        
        /* Chiều rộng cho các cột khác trong bảng giảng viên */
        .teacher-table th:nth-child(1),
        .teacher-table td:nth-child(1) {
            width: 20% !important;
        }
        
        .teacher-table th:nth-child(3),
        .teacher-table td:nth-child(3) {
            width: 20% !important;
        }
        
        .teacher-table th:nth-child(4),
        .teacher-table td:nth-child(4) {
            width: 25% !important;
        }
    </style>
</head>

<body>
    <div class="container mt-3">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h3 class="mt-3 mb-4 text-center">THÊM CHI TIẾT BÁO CÁO ĐỀ TÀI MỚI</h3>
                
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
                        <form action="../../handle/project_presentation_details_process.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="project_presentation_id" class="form-label">Báo cáo đề tài</label>
                        <select class="form-select select2" id="project_presentation_id" name="project_presentation_id" required>
                            <option value="">-- Chọn báo cáo đề tài --</option>
                            <?php foreach ($presentations as $presentation): ?>
                                <option value="<?= $presentation['id'] ?>"><?= $presentation['id'] ?> - <?= htmlspecialchars($presentation['title']) ?></option>
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
                        <label for="score" class="form-label">Điểm</label>
                        <input type="number" class="form-control" id="score" name="score" 
                               min="0" max="10" step="0.1" required 
                               placeholder="">
                    </div>
                    
                    <div class="gap-2 text-center">
                        <button type="submit" class="btn btn-primary">Thêm</button>
                        <a href="../project_presentation_details.php" class="btn btn-secondary">Hủy</a>
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
    
    <!-- Include Select2 JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Khởi tạo Select2 cho tất cả các select
            $('.select2').select2({
                placeholder: function() {
                    return $(this).find('option:first').text();
                },
                allowClear: false,
                width: '100%'
            });
            
            // Tối ưu hóa hiệu suất cho Select2
            $('.select2').on('select2:opening', function(e) {
                // Thêm loading spinner nếu cần
            });
            
            $('.select2').on('select2:closing', function(e) {
                // Xóa loading spinner nếu có
            });
        });
        
        // JavaScript để tạo hiệu ứng khi người dùng chọn các option
        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('select[id$="_id"]');
            
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    // Thêm hiệu ứng nhẹ khi chọn
                    this.style.transition = 'all 0.3s ease';
                    this.style.borderColor = '#0d6efd';
                    setTimeout(() => {
                        this.style.borderColor = '#ced4da';
                    }, 1000);
                });
            });
        });
    </script>
</body>

</html>