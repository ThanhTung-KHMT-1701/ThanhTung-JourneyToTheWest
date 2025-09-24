<?php
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/project_presentations_functions.php';
checkLogin(__DIR__ . '/../index.php');

// Khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cấu hình phân trang
$limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
if ($limit === 'all') {
    $limit = 'all';
} else {
    $limit = (int)$limit;
    if (!in_array($limit, [10, 20, 25, 50, 100, 200])) {
        $limit = 10; // Mặc định nếu giá trị không hợp lệ
    }
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Lấy tham số tìm kiếm từ URL
$searchProjectName = isset($_GET['search_project_name']) ? trim($_GET['search_project_name']) : '';
$searchProjectCode = isset($_GET['search_project_code']) ? trim($_GET['search_project_code']) : '';
$searchTitle = isset($_GET['search_title']) ? trim($_GET['search_title']) : '';
$searchTimeStart = isset($_GET['search_time_start']) ? trim($_GET['search_time_start']) : '';
$searchTimeEnd = isset($_GET['search_time_end']) ? trim($_GET['search_time_end']) : '';
$searchScoreMin = isset($_GET['search_score_min']) ? trim($_GET['search_score_min']) : '';
$searchScoreMax = isset($_GET['search_score_max']) ? trim($_GET['search_score_max']) : '';

// Lấy thông tin sắp xếp từ session
$sortField = isset($_SESSION['project_presentations_sort_field']) ? $_SESSION['project_presentations_sort_field'] : 'id';
$sortOrder = isset($_SESSION['project_presentations_sort_order']) ? $_SESSION['project_presentations_sort_order'] : 'asc';

// Xác định icon sắp xếp cho mỗi cột
function getSortIcon($field, $currentSortField, $currentSortOrder) {
    if ($field === $currentSortField) {
        return $currentSortOrder === 'asc' 
            ? '<i class="fas fa-sort-up"></i>' 
            : '<i class="fas fa-sort-down"></i>';
    }
    return '<i class="fas fa-sort"></i>';
}

// Xác định hướng sắp xếp mới khi người dùng nhấp vào tiêu đề
function getNextSortOrder($field, $currentSortField, $currentSortOrder) {
    if ($field === $currentSortField) {
        return $currentSortOrder === 'asc' ? 'desc' : 'asc';
    }
    return 'asc';
}

// Xây dựng URL cho phân trang với các tham số hiện tại
function buildPaginationUrl($page, $sortField, $sortOrder, $limit, $searchProjectName = '', $searchProjectCode = '', $searchTitle = '', $searchTimeStart = '', $searchTimeEnd = '', $searchScoreMin = '', $searchScoreMax = '') {
    $params = [
        'page' => $page,
        'sort_field' => $sortField,
        'sort_order' => $sortOrder,
        'limit' => $limit
    ];
    
    // Thêm tham số tìm kiếm nếu có
    if (!empty($searchProjectName)) {
        $params['search_project_name'] = $searchProjectName;
    }
    if (!empty($searchProjectCode)) {
        $params['search_project_code'] = $searchProjectCode;
    }
    if (!empty($searchTitle)) {
        $params['search_title'] = $searchTitle;
    }
    if (!empty($searchTimeStart)) {
        $params['search_time_start'] = $searchTimeStart;
    }
    if (!empty($searchTimeEnd)) {
        $params['search_time_end'] = $searchTimeEnd;
    }
    if (!empty($searchScoreMin)) {
        $params['search_score_min'] = $searchScoreMin;
    }
    if (!empty($searchScoreMax)) {
        $params['search_score_max'] = $searchScoreMax;
    }
    
    return 'project_presentations.php?' . http_build_query($params);
}

// Xây dựng URL cho sắp xếp với các tham số hiện tại
function buildSortUrl($field, $sortField, $sortOrder, $page, $limit, $searchProjectName = '', $searchProjectCode = '', $searchTitle = '', $searchTimeStart = '', $searchTimeEnd = '', $searchScoreMin = '', $searchScoreMax = '') {
    $params = [
        'page' => $page,
        'sort_field' => $field,
        'sort_order' => getNextSortOrder($field, $sortField, $sortOrder),
        'limit' => $limit
    ];
    
    // Thêm tham số tìm kiếm nếu có
    if (!empty($searchProjectName)) {
        $params['search_project_name'] = $searchProjectName;
    }
    if (!empty($searchProjectCode)) {
        $params['search_project_code'] = $searchProjectCode;
    }
    if (!empty($searchTitle)) {
        $params['search_title'] = $searchTitle;
    }
    if (!empty($searchTimeStart)) {
        $params['search_time_start'] = $searchTimeStart;
    }
    if (!empty($searchTimeEnd)) {
        $params['search_time_end'] = $searchTimeEnd;
    }
    if (!empty($searchScoreMin)) {
        $params['search_score_min'] = $searchScoreMin;
    }
    if (!empty($searchScoreMax)) {
        $params['search_score_max'] = $searchScoreMax;
    }
    
    return 'project_presentations.php?' . http_build_query($params);
}

// Thiết lập title cho trang
$pageTitle = 'Báo cáo đề tài - DNU OpenSource';

// CSS tùy chỉnh cho hiệu ứng hover
$customInlineCSS = '
/* Custom hover effects for action buttons */
.btn-warning {
    background-color: #fff !important;
    border-color: #ffc107 !important;
    color: #ffc107 !important;
}

.btn-warning:hover {
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
    color: #fff !important;
    transform: scale(1.05);
}

.btn-danger {
    background-color: #fff !important;
    border-color: #dc3545 !important;
    color: #dc3545 !important;
}

.btn-danger:hover {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
    color: #fff !important;
    transform: scale(1.05);
}

/* Smooth transition for all buttons */
.btn {
    transition: all 0.2s ease-in-out;
}

/* Style cho điểm số */
.score-text {
    font-size: 0.9rem;
    color: #000; /* Màu đen mặc định */
}

.score-excellent {
    color: #0d6efd !important; /* Xanh dương */
    font-weight: bold !important;
}

.score-good {
    color: #198754 !important; /* Xanh lá cây */
}

.score-poor {
    color: #dc3545 !important; /* Đỏ */
}

.score-none {
    color: #ff0000ff;
}

/* Style cho header của bảng */
.table thead th {
    background-color: #0d6efd !important; /* Primary color */
    color: white !important;
    border: 1px solid #0d6efd !important;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
    position: relative;
}

.table thead th a {
    color: white !important;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    gap: 0.5rem;
}

.table thead th a:hover {
    color: #f8f9fa !important;
}

.table thead th i.fas {
    font-size: 0.8rem;
    margin-left: 0.25rem;
}
';

include __DIR__ . '/header.php';
?>

<body>
    <?php include './menu.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-11 mx-auto mt-3">
        
                <h3 class="mt-3 mb-3 text-center">DANH SÁCH BÁO CÁO ĐỀ TÀI</h3>
                <br>
                <br>
        
        <?php
        // Hiển thị thông báo thành công
        if (isset($_GET['success'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($_GET['success']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
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
                
        <!-- Nút thêm báo cáo và các controls -->
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <!-- Nút thêm báo cáo bên trái -->
                <div>
                    <a href="project_presentations/create_project_presentations.php" class="btn btn-primary"> 
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                
                <!-- Phần phân trang và hủy bỏ bộ lọc bên phải -->
                <div class="d-flex align-items-center">
                    <!-- Phần phân trang -->
                    <div class="input-group me-3" style="width: 200px;">
                        <span class="input-group-text bg-primary text-white">Phân trang</span>
                        <select class="form-select" id="records_per_page" onchange="changeRecordsPerPage()">
                            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                            <option value="200" <?= $limit == 200 ? 'selected' : '' ?>>200</option>
                            <option value="all" <?= $limit == 'all' ? 'selected' : '' ?>>All</option>
                        </select>
                    </div>
                    <!-- Nút hủy bỏ bộ lọc -->
                    <div style="width: 200px;">
                        <button type="button" class="btn btn-secondary w-100" onclick="clearSearch()">
                            <i class="fas fa-times"></i> Hủy bỏ bộ lọc
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form tìm kiếm báo cáo đề tài -->
        <form method="GET" action="project_presentations.php" id="searchForm">
            <!-- Giữ nguyên các tham số hiện tại -->
            <input type="hidden" name="limit" value="<?= htmlspecialchars($limit) ?>">
            <input type="hidden" name="sort_field" value="<?= htmlspecialchars($sortField) ?>">
            <input type="hidden" name="sort_order" value="<?= htmlspecialchars($sortOrder) ?>">
            <input type="hidden" name="page" value="1"> <!-- Reset về trang 1 khi tìm kiếm -->
            
            <div class="row mb-3 mt-3">
                <div class="col-md-12">
                    <!-- Hàng đầu: Tiêu đề báo cáo -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Tiêu đề báo cáo</span>
                                <input type="text" class="form-control" name="search_title" 
                                       value="<?= htmlspecialchars($searchTitle) ?>"
                                       placeholder="Nhập tiêu đề báo cáo để tìm kiếm"
                                       onblur="document.getElementById('searchForm').submit()"
                                       onkeypress="if(event.key === 'Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hàng thứ hai: Tên đề tài -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Tên đề tài</span>
                                <input type="text" class="form-control" name="search_project_name" 
                                       value="<?= htmlspecialchars($searchProjectName) ?>"
                                       placeholder="Nhập tên đề tài để tìm kiếm"
                                       onblur="document.getElementById('searchForm').submit()"
                                       onkeypress="if(event.key === 'Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hàng thứ ba: Mã đề tài, Khoảng thời gian và Khoảng điểm -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Mã đề tài</span>
                                <input type="text" class="form-control" name="search_project_code" 
                                       value="<?= htmlspecialchars($searchProjectCode) ?>"
                                       placeholder="Nhập mã đề tài"
                                       onblur="document.getElementById('searchForm').submit()"
                                       onkeypress="if(event.key === 'Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Từ ngày</span>
                                <input type="date" class="form-control" name="search_time_start" 
                                       value="<?= htmlspecialchars($searchTimeStart) ?>"
                                       onblur="document.getElementById('searchForm').submit()"
                                       onkeypress="if(event.key === 'Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Đến ngày</span>
                                <input type="date" class="form-control" name="search_time_end" 
                                       value="<?= htmlspecialchars($searchTimeEnd) ?>"
                                       onblur="document.getElementById('searchForm').submit()"
                                       onkeypress="if(event.key === 'Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Điểm thấp nhất</span>
                                <input type="number" class="form-control" name="search_score_min" 
                                       value="<?= htmlspecialchars($searchScoreMin) ?>"
                                       placeholder="0"
                                       min="0" max="10" step="0.1"
                                       onblur="document.getElementById('searchForm').submit()"
                                       onkeypress="if(event.key === 'Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Điểm cao nhất</span>
                                <input type="number" class="form-control" name="search_score_max" 
                                       value="<?= htmlspecialchars($searchScoreMax) ?>"
                                       placeholder="10"
                                       min="0" max="10" step="0.1"
                                       onblur="document.getElementById('searchForm').submit()"
                                       onkeypress="if(event.key === 'Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('id', $sortField, $sortOrder, $page, $limit, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax) ?>">
                            ID <?= getSortIcon('id', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('title', $sortField, $sortOrder, $page, $limit, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax) ?>">
                            Tiêu đề báo cáo <?= getSortIcon('title', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('project_code', $sortField, $sortOrder, $page, $limit, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax) ?>">
                            Mã đề tài <?= getSortIcon('project_code', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('project_name', $sortField, $sortOrder, $page, $limit, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax) ?>">
                            Tên đề tài <?= getSortIcon('project_name', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('time', $sortField, $sortOrder, $page, $limit, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax) ?>">
                            Thời gian <?= getSortIcon('time', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('score', $sortField, $sortOrder, $page, $limit, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax) ?>">
                            Điểm số <?= getSortIcon('score', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require '../handle/project_presentations_process.php';
                
                // Cập nhật thông tin sắp xếp từ URL nếu có
                if (isset($_GET['sort_field']) && isset($_GET['sort_order'])) {
                    $sortField = $_GET['sort_field'];
                    $sortOrder = $_GET['sort_order'];
                    $_SESSION['project_presentations_sort_field'] = $sortField;
                    $_SESSION['project_presentations_sort_order'] = $sortOrder;
                }
                
                // Kiểm tra có tìm kiếm hay không
                $hasSearch = !empty($searchProjectName) || !empty($searchProjectCode) || !empty($searchTitle) || !empty($searchTimeStart) || !empty($searchTimeEnd) || ($searchScoreMin !== '' && $searchScoreMin !== null) || ($searchScoreMax !== '' && $searchScoreMax !== null);
                
                if ($hasSearch) {
                    // Lấy danh sách báo cáo với tìm kiếm và phân trang
                    $paginationData = searchProjectPresentationsWithPagination($page, $limit, $sortField, $sortOrder, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax);
                } else {
                    // Lấy danh sách báo cáo thông thường với phân trang
                    $paginationData = getProjectPresentationsWithPagination($page, $limit, $sortField, $sortOrder);
                }
                
                $presentations = $paginationData['presentations'];
                $totalPresentations = $paginationData['totalPresentations'];
                $totalPages = $paginationData['totalPages'];
                $currentPage = $paginationData['currentPage'];

                // Function để xác định màu chữ điểm số
                function getScoreTextClass($score) {
                    if ($score === null || $score === '') {
                        return 'score-none';
                    }
                    $score = floatval($score);
                    if ($score >= 9.0 && $score <= 10.0) {
                        return 'score-excellent'; // Xanh dương và in đậm (9-10)
                    } elseif ($score >= 7.0 && $score < 9.0) {
                        return 'score-good'; // Xanh lá cây (7-9)
                    } elseif ($score < 7.0) {
                        return 'score-poor'; // Đỏ (< 7)
                    } else {
                        return 'score-text'; // Màu đen mặc định
                    }
                }

                foreach($presentations as $index => $presentation){
                ?>
                    <tr>
                        <td class="text-center"><?= $presentation["id"] ?></td>
                        <td><?= htmlspecialchars($presentation["title"]) ?></td>
                        <td class="text-center"><?= htmlspecialchars($presentation["project_code"]) ?></td>
                        <td><?= htmlspecialchars($presentation["project_name"]) ?></td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($presentation["time"])) ?></td>
                        <td class="text-center">
                            <?php if ($presentation["score"] !== null && $presentation["score"] !== ''): ?>
                                <span class="score-text <?= getScoreTextClass($presentation["score"]) ?>">
                                    <?= number_format($presentation["score"], 1) ?>
                                </span>
                            <?php else: ?>
                                <span class="score-text score-none ">
                     </span></span>               ...
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="project_presentations/edit_project_presentations.php?id=<?= $presentation["id"] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="../handle/project_presentations_process.php?action=delete&id=<?= $presentation["id"] ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa báo cáo này?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <!-- Phân trang -->
        <?php if ($limit !== 'all'): ?>
        <nav aria-label="Phân trang báo cáo đề tài">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <?php if ($totalPages > 1): ?>
                <ul class="pagination pagination-sm mb-0">
                    <!-- Nút Previous -->
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPaginationUrl($currentPage - 1, $sortField, $sortOrder, $limit, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax) ?>">
                                <i class="fas fa-chevron-left"></i> Trước
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link"><i class="fas fa-chevron-left"></i> Trước</span>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Các số trang -->
                    <?php
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    
                    // Hiển thị trang đầu nếu cần
                    if ($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl(1, $sortField, $sortOrder, $limit, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax) . '">1</a></li>';
                        if ($startPage > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Hiển thị các trang xung quanh trang hiện tại
                    for ($i = $startPage; $i <= $endPage; $i++):
                        if ($i == $currentPage): ?>
                            <li class="page-item active">
                                <span class="page-link"><?= $i ?></span>
                            </li>
                        <?php else: ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildPaginationUrl($i, $sortField, $sortOrder, $limit, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax) ?>"><?= $i ?></a>
                            </li>
                        <?php endif;
                    endfor;
                    
                    // Hiển thị trang cuối nếu cần
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl($totalPages, $sortField, $sortOrder, $limit, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax) . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Nút Next -->
                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPaginationUrl($currentPage + 1, $sortField, $sortOrder, $limit, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax) ?>">
                                Sau <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link">Sau <i class="fas fa-chevron-right"></i></span>
                        </li>
                    <?php endif; ?>
                </ul>
                <?php endif; ?>
            </div>
        </nav>
        <?php elseif ($limit === 'all'): ?>
        <div class="d-flex justify-content-center mb-3">
            <small class="text-muted">
                Hiển thị tất cả <?= $totalPresentations ?> báo cáo đề tài
            </small>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Function để xóa bộ lọc tìm kiếm
        function clearSearch() {
            // Chuyển hướng về trang chính không có tham số tìm kiếm, giữ nguyên limit và sort
            const urlParams = new URLSearchParams(window.location.search);
            const limit = urlParams.get('limit') || '10';
            const sortField = urlParams.get('sort_field') || 'id';
            const sortOrder = urlParams.get('sort_order') || 'asc';
            
            window.location.href = `project_presentations.php?limit=${limit}&sort_field=${sortField}&sort_order=${sortOrder}&page=1`;
        }
        
        // Function để thay đổi số lượng bản ghi trên trang
        function changeRecordsPerPage() {
            const limit = document.getElementById('records_per_page').value;
            const urlParams = new URLSearchParams(window.location.search);
            
            // Cập nhật tham số limit
            urlParams.set('limit', limit);
            
            // Reset về trang 1 khi thay đổi số lượng bản ghi
            urlParams.set('page', '1');
            
            // Giữ nguyên các tham số sắp xếp nếu có
            const sortField = urlParams.get('sort_field') || 'id';
            const sortOrder = urlParams.get('sort_order') || 'asc';
            urlParams.set('sort_field', sortField);
            urlParams.set('sort_order', sortOrder);
            
            // Chuyển hướng với tham số mới
            window.location.href = 'project_presentations.php?' + urlParams.toString();
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            </div>
        </div>
    </div>
</body>

</html>
