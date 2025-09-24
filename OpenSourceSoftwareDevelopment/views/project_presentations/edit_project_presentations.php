<?php
require_once __DIR__ . '/../../functions/auth.php';
checkLogin(__DIR__ . '/../../index.php');

// Thiết lập title cho trang
$pageTitle = 'Chỉnh sửa báo cáo đề tài - DNU OpenSource';
include __DIR__ . '/../header.php';
?>

<body>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .select2-container--default .select2-selection--single {
            border-color: #ced4da;
            min-height: calc(2rem);
            display: flex;
            align-items: center;
        }

        /* Timeline Styles */
        .timeline {
            position: relative;
            padding: 20px 0;
        }

        /* Custom styling for readonly score input */
        #score {
            background-color: #e9ecef !important;
            color: #6c757d !important;
            cursor: not-allowed !important;
            border-color: #ced4da !important;
        }

        #score:hover {
            background-color: #e9ecef !important;
            border-color: #ced4da !important;
            transform: none !important;
            box-shadow: none !important;
        }

        #score:focus {
            background-color: #e9ecef !important;
            border-color: #ced4da !important;
            box-shadow: none !important;
            outline: none !important;
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
    </head>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    </head>

    <body>
        <div class="container mt-3">
            <h3 class="mt-3 mb-4 text-center">CHỈNH SỬA BÁO CÁO ĐỀ TÀI</h3>
            <br>
            <?php
            // Kiểm tra có ID không
            if (!isset($_GET['id']) || empty($_GET['id'])) {
                header("Location: ../project_presentations.php?error=Không tìm thấy báo cáo đề tài");
                exit;
            }

            $id = $_GET['id'];

            // Lấy thông tin báo cáo đề tài
            require_once __DIR__ . '/../../handle/project_presentations_process.php';
            $presentation = handleGetProjectPresentationById($id);

            if (!$presentation) {
                header("Location: ../project_presentations.php?error=Không tìm thấy báo cáo đề tài");
                exit;
            }

            // Lấy danh sách đề tài
            $projects = handleGetAllProjects();

            // Lấy lịch sử báo cáo của đề tài hiện tại
            require_once __DIR__ . '/../../functions/project_presentations_functions.php';
            $presentationHistory = getProjectPresentationHistory($presentation['project_id']);

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
                            <form action="../../handle/project_presentations_process.php" method="POST">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id"
                                    value="<?php echo htmlspecialchars($presentation['id']); ?>">

                                <div class="mb-3">
                                    <label for="title" class="form-label">Tiêu đề báo cáo</label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           value="<?php echo htmlspecialchars($presentation['title']); ?>"
                                           placeholder="Nhập tiêu đề báo cáo">
                                </div>

                                <div class="mb-3">
                                    <label for="project_id" class="form-label">Đề tài</label>
                                    <select class="form-select select2" id="project_id" name="project_id" required>
                                        <option value="">-- Chọn đề tài --</option>
                                        <?php foreach ($projects as $project): ?>
                                            <?php
                                            $projectCode = htmlspecialchars($project['project_code']);
                                            $projectName = htmlspecialchars($project['project_name']);
                                            $displayText = $projectCode . ' - ' . $projectName;
                                            ?>
                                            <option value="<?= $project['id'] ?>"
                                                <?= ($project['id'] == $presentation['project_id']) ? 'selected' : '' ?>>
                                                <?= $displayText ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="score" class="form-label">Điểm số</label>
                                    <input type="number" class="form-control" id="score" name="score" 
                                           step="0.1" min="0" max="10" 
                                           value="<?php echo htmlspecialchars($presentation['score']); ?>"
                                           placeholder="Chỉ được chấm điểm sau khi tạo báo cáo" 
                                           readonly>
                                </div>

                                <div class="gap-2 text-center">
                                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                                    <a href="../project_presentations.php" class="btn btn-secondary me-md-2">Hủy</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Phần lịch sử báo cáo đề tài -->
            <?php if (!empty($presentationHistory)): ?>
                <div class="row justify-content-center mt-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-clock-history me-2"></i>
                                    Lịch sử báo cáo đề tài
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php
                                    $total = count($presentationHistory);
                                    foreach ($presentationHistory as $index => $history):
                                        $isLast = ($index === $total - 1);
                                        $isFirst = ($index === 0);
                                        ?>
                                        <div class="timeline-item <?= $isLast ? 'timeline-item-last' : '' ?>">
                                            <div
                                                class="timeline-marker <?= $isFirst ? 'timeline-marker-current' : 'timeline-marker-completed' ?>">
                                                <i class="bi <?= $isFirst ? 'bi-presentation' : 'bi-check-circle-fill' ?>"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="timeline-card">
                                                    <div class="timeline-header">
                                                        <div class="timeline-title">
                                                            <?= $isFirst ? 'Báo cáo hiện tại' : 'Báo cáo lần ' . ($total - $index) ?>
                                                        </div>
                                                        <div class="timeline-date">
                                                            <i class="bi bi-calendar-event me-1"></i>
                                                            <?= date('d/m/Y H:i', strtotime($history['time'])) ?>
                                                        </div>
                                                    </div>
                                                    <div class="timeline-body">
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <div class="timeline-info">
                                                                    <span class="timeline-label">Tiêu đề báo cáo</span>
                                                                    <span class="timeline-value">
                                                                        <i class="bi bi-file-text me-1"></i>
                                                                        <?= htmlspecialchars($history['title']) ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="timeline-info">
                                                                    <span class="timeline-label">Điểm số</span>
                                                                    <span class="timeline-value">
                                                                        <i class="bi bi-award me-1"></i>
                                                                        <?php if ($history['score'] !== null && $history['score'] !== ''): ?>
                                                                            <?php
                                                                            $score = floatval($history['score']);
                                                                            $scoreClass = '';
                                                                            if ($score >= 9.0 && $score <= 10.0) {
                                                                                $scoreClass = 'text-primary fw-bold'; // Xanh dương và in đậm (9-10)
                                                                            } elseif ($score >= 7.0 && $score < 9.0) {
                                                                                $scoreClass = 'text-success'; // Xanh lá cây (7-9)
                                                                            } elseif ($score < 7.0) {
                                                                                $scoreClass = 'text-danger'; // Đỏ (< 7)
                                                                            } else {
                                                                                $scoreClass = 'text-dark'; // Màu đen mặc định
                                                                            }
                                                                            ?>
                                                                            <span class="<?= $scoreClass ?>">
                                                                                <?= number_format($score, 1) ?> điểm
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">Chưa có điểm</span>
                                                                        <?php endif; ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function () {
                // Khởi tạo Select2
                $('.select2').select2({
                    placeholder: "Chọn đề tài",
                    allowClear: true
                });
            });
        </script>
    </body>

    </html>