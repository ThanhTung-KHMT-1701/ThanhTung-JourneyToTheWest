<?php
require_once __DIR__ . '/../../functions/auth.php';
checkLogin(__DIR__ . '/../../index.php');

// Thiết lập title và custom CSS cho trang
$pageTitle = 'Thêm gia hạn đề tài - DNU OpenSource';
$customCSS = ['https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'];
include __DIR__ . '/../header.php';
?>
<link rel="stylesheet" href="../../css/project_extensions.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    .select2-container--default .select2-selection--single {
        border-color: #ced4da;
        height: 50px; /* Tăng chiều cao của selection box */
        line-height: 50px; /* Căn giữa text theo chiều dọc */
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 48px; /* Căn giữa text bên trong */
        padding-left: 12px;
        padding-right: 12px;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 48px; /* Căn giữa arrow theo chiều dọc */
    }
    
    .select2-dropdown {
        border-color: #ced4da;
    }
    
    .select2-results__option {
        padding: 12px 15px; /* Tăng padding cho các option */
        line-height: 1.4; /* Tăng line-height để text hiển thị thoải mái hơn */
        white-space: normal; /* Cho phép text wrap xuống dòng */
        word-wrap: break-word; /* Ngắt từ khi cần thiết */
    }
    
    .select2-results__option--disabled {
        opacity: 0.5;
    }

    /* Timeline Styles */
    .timeline {
        position: relative;
        padding: 20px 0;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 30px;
        top: 0;
        bottom: 0;
        width: 3px;
        background: linear-gradient(to bottom, #0d6efd, #6c757d);
        border-radius: 2px;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 30px;
        padding-left: 80px;
    }

    .timeline-item-last {
        margin-bottom: 0;
    }

    .timeline-marker {
        position: absolute;
        left: 15px;
        top: 8px;
        width: 32px;
        height: 32px;
        background: #fff;
        border: 3px solid #0d6efd;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
        transition: all 0.3s ease;
    }

    .timeline-marker-current {
        background: #0d6efd;
        color: white;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.2);
        animation: pulse 2s infinite;
    }

    .timeline-marker-completed {
        background: #198754;
        border-color: #198754;
        color: white;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
        }
    }

    .timeline-content {
        margin-top: -8px;
    }

    .timeline-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        position: relative;
    }

    .timeline-card:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }

    .timeline-card::before {
        content: '';
        position: absolute;
        left: -12px;
        top: 20px;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 10px 12px 10px 0;
        border-color: transparent #e9ecef transparent transparent;
    }

    .timeline-card::after {
        content: '';
        position: absolute;
        left: -11px;
        top: 20px;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 10px 12px 10px 0;
        border-color: transparent #fff transparent transparent;
    }

    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e9ecef;
    }

    .timeline-title {
        font-weight: 600;
        font-size: 1.1rem;
        color: #0d6efd;
    }

    .timeline-date {
        color: #6c757d;
        font-size: 0.9rem;
        background: #f8f9fa;
        padding: 4px 8px;
        border-radius: 6px;
    }

    .timeline-body {
        color: #495057;
    }

    .timeline-info {
        display: flex;
        flex-direction: column;
        margin-bottom: 8px;
    }

    .timeline-label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 2px;
    }

    .timeline-value {
        font-weight: 500;
        font-size: 0.95rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .timeline::before {
            left: 20px;
        }

        .timeline-marker {
            left: 5px;
            width: 28px;
            height: 28px;
        }

        .timeline-item {
            padding-left: 60px;
        }

        .timeline-card::before,
        .timeline-card::after {
            left: -8px;
            border-width: 8px 10px 8px 0;
        }

        .timeline-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
    }
</style>

<body>
    <div class="container mt-3">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h3 class="mt-3 mb-4 text-center">THÊM GIA HẠN ĐỀ TÀI MỚI</h3>
                
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
                
                <?php
                require '../../handle/project_extensions_process.php';
                $projects = handleGetAllProjects();
                ?>
                
                <form action="../../handle/project_extensions_process.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="project_id" class="form-label">Đề tài</label>
                        <select class="form-select select2" id="project_id" name="project_id" required>
                            <option value="">-- Chọn đề tài --</option>
                            <?php foreach ($projects as $project): ?>
                                <?php 
                                    $projectCode = htmlspecialchars($project['project_code']);
                                    $projectName = htmlspecialchars($project['project_name']);
                                    $numberExtension = (int)$project['number_extension'];
                                    
                                    if ($numberExtension > 0) {
                                        $displayText = $projectCode . ' - ' . $projectName . ' (còn ' . $numberExtension . ' lần gia hạn)';
                                        $disabled = '';
                                        $className = '';
                                    } else {
                                        $displayText = $projectCode . ' - ' . $projectName . ' (hết lượt gia hạn)';
                                        $disabled = 'disabled';
                                        $className = 'text-muted';
                                    }
                                ?>
                                <option value="<?= $project['id'] ?>" <?= $disabled ?> class="<?= $className ?>">
                                    <?= $displayText ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="datefinish_before" class="form-label">Ngày kết thúc trước gia hạn</label>
                        <input type="date" class="form-control" id="datefinish_before" name="datefinish_before" readonly required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="datefinish_after" class="form-label">Ngày kết thúc sau gia hạn</label>
                        <input type="date" class="form-control" id="datefinish_after" name="datefinish_after" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="extension_file" class="form-label">Đơn xin gia hạn đề tài</label>
                        <input type="file" class="form-control" id="extension_file" name="extension_file" 
                               accept=".pdf" required>
                    </div>
                    
                    <div class="gap-2 text-center">
                        <button type="submit" class="btn btn-primary">Thêm gia hạn</button>
                        <a href="../project_extensions.php" class="btn btn-secondary">Hủy</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Phần lịch sử gia hạn đề tài -->
        <div class="row justify-content-center mt-4" id="extension-history-section" style="display: none;">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            Lịch sử gia hạn đề tài
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline" id="extension-timeline">
                            <!-- Nội dung timeline sẽ được load bằng AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Khởi tạo Select2
            $('.select2').select2({
                placeholder: "Chọn đề tài",
                allowClear: true
            });
            
            // Lấy ngày kết thúc của đề tài khi chọn đề tài
            $('#project_id').change(function() {
                const projectId = $(this).val();
                if (projectId) {
                    // Sử dụng AJAX để lấy ngày kết thúc hiện tại của đề tài
                    $.ajax({
                        url: '../../handle/project_extensions_process.php',
                        type: 'GET',
                        data: {
                            action: 'get_finish_date',
                            project_id: projectId
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data && data.date_finish) {
                                $('#datefinish_before').val(data.date_finish);
                            } else {
                                $('#datefinish_before').val('');
                            }
                        },
                        error: function() {
                            $('#datefinish_before').val('');
                            alert('Có lỗi xảy ra khi lấy thông tin đề tài');
                        }
                    });

                    // Load lịch sử gia hạn đề tài
                    loadExtensionHistory(projectId);
                } else {
                    $('#datefinish_before').val('');
                    $('#extension-history-section').hide();
                }
            });

            // Function để load lịch sử gia hạn
            function loadExtensionHistory(projectId) {
                $.ajax({
                    url: '../../handle/project_extensions_process.php',
                    type: 'GET',
                    data: {
                        action: 'get_extension_history',
                        project_id: projectId
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.length > 0) {
                            renderExtensionHistory(data);
                            $('#extension-history-section').show();
                        } else {
                            $('#extension-history-section').hide();
                        }
                    },
                    error: function() {
                        $('#extension-history-section').hide();
                    }
                });
            }

            // Function để render lịch sử gia hạn
            function renderExtensionHistory(extensionHistory) {
                let html = '';
                const total = extensionHistory.length;
                
                extensionHistory.forEach(function(history, index) {
                    const isLast = (index === total - 1);
                    const isFirst = (index === 0);
                    const timelineDate = new Date(history.time);
                    const beforeDate = new Date(history.datefinish_before);
                    const afterDate = new Date(history.datefinish_after);
                    
                    // Tính số ngày gia hạn
                    const timeDiff = afterDate.getTime() - beforeDate.getTime();
                    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
                    
                    html += `
                        <div class="timeline-item ${isLast ? 'timeline-item-last' : ''}">
                            <div class="timeline-marker ${isFirst ? 'timeline-marker-current' : 'timeline-marker-completed'}">
                                <i class="bi ${isFirst ? 'bi-clock' : 'bi-check-circle-fill'}"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-card">
                                    <div class="timeline-header">
                                        <div class="timeline-title">
                                            ${isFirst ? 'Gia hạn gần nhất' : 'Gia hạn lần ' + (total - index)}
                                        </div>
                                        <div class="timeline-date">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            ${timelineDate.toLocaleDateString('vi-VN')} ${timelineDate.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'})}
                                        </div>
                                    </div>
                                    <div class="timeline-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="timeline-info">
                                                    <span class="timeline-label">Ngày kết thúc cũ:</span>
                                                    <span class="timeline-value text-danger">
                                                        <i class="bi bi-calendar-x me-1"></i>
                                                        ${beforeDate.toLocaleDateString('vi-VN')}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="timeline-info">
                                                    <span class="timeline-label">Ngày kết thúc mới:</span>
                                                    <span class="timeline-value text-success">
                                                        <i class="bi bi-calendar-check me-1"></i>
                                                        ${afterDate.toLocaleDateString('vi-VN')}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <div class="timeline-info">
                                                    <span class="timeline-label">Thời gian gia hạn:</span>
                                                    <span class="timeline-value text-primary">
                                                        <i class="bi bi-hourglass-split me-1"></i>
                                                        ${daysDiff} ngày
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                $('#extension-timeline').html(html);
            }
        });
    </script>
</body>

</html>
