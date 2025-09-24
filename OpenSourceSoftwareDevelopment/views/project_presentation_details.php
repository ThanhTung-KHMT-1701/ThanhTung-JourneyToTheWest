<?php
require_once __DIR__ . '/../functions/auth.php';
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
$searchPresentationTitle = isset($_GET['search_presentation_title']) ? trim($_GET['search_presentation_title']) : '';
$searchProjectCode = isset($_GET['search_project_code']) ? trim($_GET['search_project_code']) : '';
$searchTeacherName = isset($_GET['search_teacher_name']) ? trim($_GET['search_teacher_name']) : '';
$searchScoreMin = isset($_GET['search_score_min']) ? trim($_GET['search_score_min']) : '';
$searchScoreMax = isset($_GET['search_score_max']) ? trim($_GET['search_score_max']) : '';

// Lấy thông tin sắp xếp từ URL
$sortField = isset($_GET['sort_field']) ? $_GET['sort_field'] : 'id';
$sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'asc';

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
function buildPaginationUrl($page, $sortField, $sortOrder, $limit, $searchPresentationTitle = '', $searchProjectCode = '', $searchTeacherName = '', $searchScoreMin = '', $searchScoreMax = '') {
    $params = [
        'page' => $page,
        'sort_field' => $sortField,
        'sort_order' => $sortOrder,
        'limit' => $limit
    ];
    
    // Thêm tham số tìm kiếm nếu có
    if (!empty($searchPresentationTitle)) {
        $params['search_presentation_title'] = $searchPresentationTitle;
    }
    if (!empty($searchProjectCode)) {
        $params['search_project_code'] = $searchProjectCode;
    }
    if (!empty($searchTeacherName)) {
        $params['search_teacher_name'] = $searchTeacherName;
    }
    if (!empty($searchScoreMin)) {
        $params['search_score_min'] = $searchScoreMin;
    }
    if (!empty($searchScoreMax)) {
        $params['search_score_max'] = $searchScoreMax;
    }
    
    return 'project_presentation_details.php?' . http_build_query($params);
}

// Xây dựng URL cho sắp xếp với các tham số hiện tại
function buildSortUrl($field, $sortField, $sortOrder, $page, $limit, $searchPresentationTitle = '', $searchProjectCode = '', $searchTeacherName = '', $searchScoreMin = '', $searchScoreMax = '') {
    $params = [
        'page' => $page,
        'sort_field' => $field,
        'sort_order' => getNextSortOrder($field, $sortField, $sortOrder),
        'limit' => $limit
    ];
    
    // Thêm tham số tìm kiếm nếu có
    if (!empty($searchPresentationTitle)) {
        $params['search_presentation_title'] = $searchPresentationTitle;
    }
    if (!empty($searchProjectCode)) {
        $params['search_project_code'] = $searchProjectCode;
    }
    if (!empty($searchTeacherName)) {
        $params['search_teacher_name'] = $searchTeacherName;
    }
    if (!empty($searchScoreMin)) {
        $params['search_score_min'] = $searchScoreMin;
    }
    if (!empty($searchScoreMax)) {
        $params['search_score_max'] = $searchScoreMax;
    }
    
    return 'project_presentation_details.php?' . http_build_query($params);
}

// Hàm xác định màu sắc dựa trên điểm số
function getScoreColor($score) {
    if ($score < 7) {
        return 'color: #dc3545;'; // Màu đỏ cho điểm dưới 7
    } elseif ($score >= 7 && $score < 9) {
        return 'color: #198754;'; // Màu xanh lá cây cho điểm từ 7-9
    } elseif ($score >= 9) {
        return 'color: #0d6efd; font-weight: bold;'; // Màu xanh dương và in đậm cho điểm từ 9-10
    }
    return '';
}

// Thiết lập title cho trang
$pageTitle = 'Chi tiết báo cáo đề tài - DNU OpenSource';

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

/* Đồng nhất độ rộng các nhãn trong form tìm kiếm */
.input-group-text {
    min-width: 100px;
}

/* Custom CSS for body > div to display full width with padding 1em */
body > div {
    width: 90% !important;
    max-width: none !important;
    padding-left: 2em !important;
    padding-right: 2em !important;
    margin-left: auto;
    margin-right: auto;
}

/* Tiêu đề bảng với background primary và chữ trắng căn giữa */
.table thead th {
    background-color: #0d6efd !important;
    color: white !important;
    text-align: center !important;
    vertical-align: middle !important;
    font-weight: normal !important;
}
';

include __DIR__ . '/header.php';
?>

<body>
    <?php include './menu.php'; ?>
    <div class="container mt-3">
        
        <h3 class="mt-3 mb-3 text-center">DANH SÁCH CHI TIẾT BÁO CÁO ĐỀ TÀI</h3>
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
                
        <!-- Nút thêm chi tiết báo cáo đề tài và các controls -->
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <!-- Nút thêm chi tiết báo cáo đề tài bên trái -->
                <div>
                    <a href="project_presentation_details/create_project_presentation_details.php" class="btn btn-primary"> 
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
        
        <!-- Form tìm kiếm chi tiết báo cáo đề tài -->
        <form method="GET" action="project_presentation_details.php" id="searchForm">
            <!-- Giữ nguyên các tham số hiện tại -->
            <input type="hidden" name="limit" value="<?= htmlspecialchars($limit) ?>">
            <input type="hidden" name="sort_field" value="<?= htmlspecialchars($sortField) ?>">
            <input type="hidden" name="sort_order" value="<?= htmlspecialchars($sortOrder) ?>">
            <input type="hidden" name="page" value="1"> <!-- Reset về trang 1 khi tìm kiếm -->
            
            <div class="row mb-3 mt-3">
                <div class="col-md-12">
                    <!-- Dòng 1: Tiêu đề báo cáo -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Tiêu đề báo cáo</span>
                                <input type="text" class="form-control" name="search_presentation_title" 
                                       value="<?= htmlspecialchars($searchPresentationTitle) ?>"
                                       placeholder="Nhập tiêu đề báo cáo để tìm kiếm"
                                       onchange="document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                    </div>
                    <!-- Dòng 2: Giảng viên, Mã đề tài, Điểm thấp nhất, Điểm cao nhất (tỉ lệ 4:4:2:2) -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Giảng viên</span>
                                <input type="text" class="form-control" name="search_teacher_name" 
                                       value="<?= htmlspecialchars($searchTeacherName) ?>"
                                       placeholder="Nhập tên giảng viên để tìm kiếm"
                                       onchange="document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Mã đề tài</span>
                                <input type="text" class="form-control" name="search_project_code" 
                                       value="<?= htmlspecialchars($searchProjectCode) ?>"
                                       placeholder="Nhập mã đề tài để tìm kiếm"
                                       onchange="document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Điểm thấp nhất</span>
                                <input type="number" class="form-control" name="search_score_min" 
                                       value="<?= htmlspecialchars($searchScoreMin) ?>"
                                       placeholder="0" min="0" max="10" step="0.1"
                                       onchange="document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Điểm cao nhất</span>
                                <input type="number" class="form-control" name="search_score_max" 
                                       value="<?= htmlspecialchars($searchScoreMax) ?>"
                                       placeholder="10" min="0" max="10" step="0.1"
                                       onchange="document.getElementById('searchForm').submit()">
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
                        <a href="<?= buildSortUrl('id', $sortField, $sortOrder, $page, $limit, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax) ?>" class="text-decoration-none text-white">
                            ID <?= getSortIcon('id', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('project_code', $sortField, $sortOrder, $page, $limit, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax) ?>" class="text-decoration-none text-white">
                            Mã đề tài <?= getSortIcon('project_code', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('presentation_title', $sortField, $sortOrder, $page, $limit, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax) ?>" class="text-decoration-none text-white">
                            Tiêu đề báo cáo <?= getSortIcon('presentation_title', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('teacher_name', $sortField, $sortOrder, $page, $limit, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax) ?>" class="text-decoration-none text-white">
                            Giảng viên <?= getSortIcon('teacher_name', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('score', $sortField, $sortOrder, $page, $limit, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax) ?>" class="text-decoration-none text-white">
                            Điểm <?= getSortIcon('score', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require '../handle/project_presentation_details_process.php';
                
                // Kiểm tra có tìm kiếm hay không
                $hasSearch = !empty($searchPresentationTitle) || !empty($searchProjectCode) || !empty($searchTeacherName) || !empty($searchScoreMin) || !empty($searchScoreMax);
                
                if ($hasSearch) {
                    // Lấy danh sách chi tiết báo cáo đề tài với tìm kiếm và phân trang
                    $paginationData = handleSearchProjectPresentationDetailsWithPagination($page, $limit, $sortField, $sortOrder, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax);
                } else {
                    // Lấy danh sách chi tiết báo cáo đề tài thông thường với phân trang
                    $paginationData = handleGetProjectPresentationDetailsWithPagination($page, $limit, $sortField, $sortOrder);
                }
                
                $presentation_details = $paginationData['presentation_details'];
                $totalPresentationDetails = $paginationData['totalPresentationDetails'];
                $totalPages = $paginationData['totalPages'];
                $currentPage = $paginationData['currentPage'];

                foreach($presentation_details as $index => $detail){
                ?>
                    <tr>
                        <td class="text-center"><?= $detail["id"] ?></td>
                        <td><?= htmlspecialchars($detail["project_code"]) ?></td>
                        <td><?= htmlspecialchars($detail["presentation_title"]) ?></td>
                        <td><?= htmlspecialchars($detail["teacher_name"]) ?></td>
                        <td class="text-center" style="<?= getScoreColor($detail["score"]) ?>">
                            <?= number_format($detail["score"], 1) ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="project_presentation_details/edit_project_presentation_details.php?id=<?= $detail["id"] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="../handle/project_presentation_details_process.php?action=delete&id=<?= $detail["id"] ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Bạn có chắc chắn muốn xóa chi tiết báo cáo đề tài này?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <!-- Phân trang -->
        <?php if ($limit !== 'all'): ?>
        <nav aria-label="Phân trang chi tiết báo cáo đề tài">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <?php if ($totalPages > 1): ?>
                <ul class="pagination pagination-sm mb-0">
                    <!-- Nút Previous -->
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPaginationUrl($currentPage - 1, $sortField, $sortOrder, $limit, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax) ?>">
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
                        echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl(1, $sortField, $sortOrder, $limit, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax) . '">1</a></li>';
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
                                <a class="page-link" href="<?= buildPaginationUrl($i, $sortField, $sortOrder, $limit, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax) ?>"><?= $i ?></a>
                            </li>
                        <?php endif;
                    endfor;
                    
                    // Hiển thị trang cuối nếu cần
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl($totalPages, $sortField, $sortOrder, $limit, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax) . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Nút Next -->
                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPaginationUrl($currentPage + 1, $sortField, $sortOrder, $limit, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax) ?>">
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
                Hiển thị tất cả <?= $totalPresentationDetails ?> chi tiết báo cáo đề tài
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
            
            window.location.href = `project_presentation_details.php?limit=${limit}&sort_field=${sortField}&sort_order=${sortOrder}&page=1`;
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
            window.location.href = 'project_presentation_details.php?' + urlParams.toString();
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>