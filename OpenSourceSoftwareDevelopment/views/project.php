<?php
require_once __DIR__ . '/../functions/auth.php';
checkLogin(__DIR__ . '/../index.php');

// Khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra nếu có tham số uuid để hiển thị PDF
if (isset($_GET['uuid']) && !empty($_GET['uuid'])) {
    require_once __DIR__ . '/../functions/db_connection.php';
    
    try {
        $uuid = trim($_GET['uuid']);
        $conn = getDbConnection();
        
        // Truy vấn để lấy file PDF từ database
        $sql = "SELECT file, project_name FROM projects WHERE uuid = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $uuid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $project = mysqli_fetch_assoc($result);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        if ($project && !empty($project['file'])) {
            // Set headers để hiển thị PDF trong browser
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $project['project_name'] . '.pdf"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Output file content
            echo $project['file'];
            exit;
        } else {
            // Nếu không tìm thấy file, hiển thị thông báo lỗi
            echo "<script>alert('Không tìm thấy file PDF cho đề tài này!'); window.close();</script>";
            exit;
        }
    } catch (Exception $e) {
        echo "<script>alert('Lỗi khi tải file PDF: " . addslashes($e->getMessage()) . "'); window.close();</script>";
        exit;
    }
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
$searchProjectCode = isset($_GET['search_project_code']) ? trim($_GET['search_project_code']) : '';
$searchProjectName = isset($_GET['search_project_name']) ? trim($_GET['search_project_name']) : '';
$searchDateStart = isset($_GET['search_date_start']) ? trim($_GET['search_date_start']) : '';
$searchDateFinish = isset($_GET['search_date_finish']) ? trim($_GET['search_date_finish']) : '';
$searchStatus = isset($_GET['search_status']) ? trim($_GET['search_status']) : '';
$searchNumberExtension = isset($_GET['search_number_extension']) ? trim($_GET['search_number_extension']) : '';

// Lấy thông tin sắp xếp từ session
$sortField = isset($_SESSION['project_sort_field']) ? $_SESSION['project_sort_field'] : 'id';
$sortOrder = isset($_SESSION['project_sort_order']) ? $_SESSION['project_sort_order'] : 'asc';

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
function buildPaginationUrl($page, $sortField, $sortOrder, $limit, $searchProjectCode = '', $searchProjectName = '', $searchDateStart = '', $searchDateFinish = '', $searchStatus = '', $searchNumberExtension = '') {
    $params = [
        'page' => $page,
        'sort_field' => $sortField,
        'sort_order' => $sortOrder,
        'limit' => $limit
    ];
    
    // Thêm tham số tìm kiếm nếu có
    if (!empty($searchProjectCode)) {
        $params['search_project_code'] = $searchProjectCode;
    }
    if (!empty($searchProjectName)) {
        $params['search_project_name'] = $searchProjectName;
    }
    if (!empty($searchDateStart)) {
        $params['search_date_start'] = $searchDateStart;
    }
    if (!empty($searchDateFinish)) {
        $params['search_date_finish'] = $searchDateFinish;
    }
    if (!empty($searchStatus)) {
        $params['search_status'] = $searchStatus;
    }
    if (!empty($searchNumberExtension)) {
        $params['search_number_extension'] = $searchNumberExtension;
    }
    
    return 'project.php?' . http_build_query($params);
}

// Xây dựng URL cho sắp xếp với các tham số hiện tại
function buildSortUrl($field, $sortField, $sortOrder, $page, $limit, $searchProjectCode = '', $searchProjectName = '', $searchDateStart = '', $searchDateFinish = '', $searchStatus = '', $searchNumberExtension = '') {
    $params = [
        'page' => $page,
        'sort_field' => $field,
        'sort_order' => getNextSortOrder($field, $sortField, $sortOrder),
        'limit' => $limit
    ];
    
    // Thêm tham số tìm kiếm nếu có
    if (!empty($searchProjectCode)) {
        $params['search_project_code'] = $searchProjectCode;
    }
    if (!empty($searchProjectName)) {
        $params['search_project_name'] = $searchProjectName;
    }
    if (!empty($searchDateStart)) {
        $params['search_date_start'] = $searchDateStart;
    }
    if (!empty($searchDateFinish)) {
        $params['search_date_finish'] = $searchDateFinish;
    }
    if (!empty($searchStatus)) {
        $params['search_status'] = $searchStatus;
    }
    if (!empty($searchNumberExtension)) {
        $params['search_number_extension'] = $searchNumberExtension;
    }
    
    return 'project.php?' . http_build_query($params);
}

// Thiết lập title và CSS cho trang
$pageTitle = 'Quản lý đề tài - DNU OpenSource';

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

.btn-info {
    background-color: #fff !important;
    border-color: #0dcaf0 !important;
    color: #0dcaf0 !important;
}

.btn-info:hover {
    background-color: #0dcaf0 !important;
    border-color: #0dcaf0 !important;
    color: #fff !important;
    transform: scale(1.05);
}

/* PDF view button hover effect */
.pdf-view-btn i.bi-eye {
    transition: all 0.2s ease-in-out;
}

.pdf-view-btn:hover i.bi-eye:before {
    content: "\f341"; /* bi-eye-fill */
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
';

include __DIR__ . '/header.php';
?>

<body>
    <?php include './menu.php'; ?>
    <div class="container mt-3">
        
        <h3 class="mt-3 mb-3 text-center">DANH SÁCH ĐỀ TÀI NGHIÊN CỨU KHOA HỌC</h3>
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
                
        <!-- Nút thêm đề tài và các controls -->
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <!-- Nút thêm đề tài bên trái -->
                <div>
                    <a href="project/create_project.php" class="btn btn-primary"> 
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
        
        <!-- Form tìm kiếm đề tài -->
        <form method="GET" action="project.php" id="searchForm">
            <!-- Giữ nguyên các tham số hiện tại -->
            <input type="hidden" name="limit" value="<?= htmlspecialchars($limit) ?>">
            <input type="hidden" name="sort_field" value="<?= htmlspecialchars($sortField) ?>">
            <input type="hidden" name="sort_order" value="<?= htmlspecialchars($sortOrder) ?>">
            <input type="hidden" name="page" value="1"> <!-- Reset về trang 1 khi tìm kiếm -->
            
            <div class="row mb-3 mt-3">
                <div class="col-md-12">
                    <!-- Dòng 1: Mã đề tài và Tên đề tài -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="input-group">
                                <span class="input-group-text bg-secondary text-white">Tên đề tài</span>
                                <input type="text" class="form-control" name="search_project_name" 
                                       value="<?= htmlspecialchars($searchProjectName) ?>"
                                       placeholder="So sánh các biến thể của thuật toán Gradient Descent trong huấn luyện mạng MLP trên tập dữ liệu MNIST"
                                       onkeypress="if(event.key==='Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dòng 2: Ngày bắt đầu, Ngày kết thúc, Trạng thái, Gia hạn còn lại -->
                    <div class="row">
                        <div class="col-md-2">
                            <div class="input-group">
                                <span class="input-group-text bg-secondary text-white">Mã đề tài</span>
                                <input type="text" class="form-control" name="search_project_code" 
                                       value="<?= htmlspecialchars($searchProjectCode) ?>"
                                       placeholder="DOI10.1712.83402"
                                       onkeypress="if(event.key==='Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text bg-secondary text-white">Ngày bắt đầu</span>
                                <input type="date" class="form-control" name="search_date_start" 
                                       value="<?= htmlspecialchars($searchDateStart) ?>"
                                       onkeypress="if(event.key==='Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text bg-secondary text-white">Ngày kết thúc</span>
                                <input type="date" class="form-control" name="search_date_finish" 
                                       value="<?= htmlspecialchars($searchDateFinish) ?>"
                                       onkeypress="if(event.key==='Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group">
                                <span class="input-group-text bg-secondary text-white">Trạng thái</span>
                                <select class="form-select" name="search_status" onchange="document.getElementById('searchForm').submit()">
                                    <option value="">All</option>
                                    <option value="Not started" <?= $searchStatus === 'Not started' ? 'selected' : '' ?>>Not started</option>
                                    <option value="In progress" <?= $searchStatus === 'In progress' ? 'selected' : '' ?>>In progress</option>
                                    <option value="Completed" <?= $searchStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Canceled" <?= $searchStatus === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                                    <option value="Pending extension" <?= $searchStatus === 'Pending extension' ? 'selected' : '' ?>>Pending extension</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group">
                                <span class="input-group-text bg-secondary text-white">Gia hạn còn lại</span>
                                <input type="number" class="form-control" name="search_number_extension" 
                                       value="<?= htmlspecialchars($searchNumberExtension) ?>"
                                       placeholder="0, 1, 2"
                                       min="0" max="5"
                                       onkeypress="if(event.key==='Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <br>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th scope="col" class="text-center">
                            <a href="<?= buildSortUrl('id', $sortField, $sortOrder, $page, $limit, $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, $searchStatus, $searchNumberExtension) ?>" class="text-decoration-none text-dark">
                                ID <?= getSortIcon('id', $sortField, $sortOrder) ?>
                            </a>
                        </th>
                        <th scope="col" class="text-center">
                            <a href="<?= buildSortUrl('project_code', $sortField, $sortOrder, $page, $limit, $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, $searchStatus, $searchNumberExtension) ?>" class="text-decoration-none text-dark">
                                Mã đề tài <?= getSortIcon('project_code', $sortField, $sortOrder) ?>
                            </a>
                        </th>
                        <th scope="col" class="text-center">
                            <a href="<?= buildSortUrl('project_name', $sortField, $sortOrder, $page, $limit, $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, $searchStatus, $searchNumberExtension) ?>" class="text-decoration-none text-dark">
                                Tên đề tài <?= getSortIcon('project_name', $sortField, $sortOrder) ?>
                            </a>
                        </th>
                        <th scope="col" class="text-center">
                            <a href="<?= buildSortUrl('date_start', $sortField, $sortOrder, $page, $limit, $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, $searchStatus, $searchNumberExtension) ?>" class="text-decoration-none text-dark">
                                Ngày bắt đầu <?= getSortIcon('date_start', $sortField, $sortOrder) ?>
                            </a>
                        </th>
                        <th scope="col" class="text-center">
                            <a href="<?= buildSortUrl('date_finish', $sortField, $sortOrder, $page, $limit, $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, $searchStatus, $searchNumberExtension) ?>" class="text-decoration-none text-dark">
                                Ngày kết thúc <?= getSortIcon('date_finish', $sortField, $sortOrder) ?>
                            </a>
                        </th>
                        <th scope="col" class="text-center">
                            <a href="<?= buildSortUrl('status', $sortField, $sortOrder, $page, $limit, $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, $searchStatus, $searchNumberExtension) ?>" class="text-decoration-none text-dark">
                                Trạng thái <?= getSortIcon('status', $sortField, $sortOrder) ?>
                            </a>
                        </th>
                        <th scope="col" class="text-center">
                            <a href="<?= buildSortUrl('number_extension', $sortField, $sortOrder, $page, $limit, $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, $searchStatus, $searchNumberExtension) ?>" class="text-decoration-none text-dark">
                                Gia hạn còn lại <?= getSortIcon('number_extension', $sortField, $sortOrder) ?>
                            </a>
                        </th>
                        <th scope="col" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    require '../handle/project_process.php';
                    
                    // Cập nhật thông tin sắp xếp từ URL nếu có
                    if (isset($_GET['sort_field']) && isset($_GET['sort_order'])) {
                        $sortField = $_GET['sort_field'];
                        $sortOrder = $_GET['sort_order'];
                        $_SESSION['project_sort_field'] = $sortField;
                        $_SESSION['project_sort_order'] = $sortOrder;
                    }
                    
                    // Kiểm tra có tìm kiếm hay không
                    $hasSearch = !empty($searchProjectCode) || !empty($searchProjectName) || 
                                !empty($searchDateStart) || !empty($searchDateFinish) || 
                                !empty($searchStatus) || !empty($searchNumberExtension);
                    
                    if ($hasSearch) {
                        // Lấy danh sách đề tài với tìm kiếm và phân trang
                        $paginationData = handleSearchProjectsWithPagination($page, $limit, $sortField, $sortOrder, 
                            $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, 
                            $searchStatus, $searchNumberExtension);
                    } else {
                        // Lấy danh sách đề tài thông thường với phân trang
                        $paginationData = handleGetProjectsWithPagination($page, $limit, $sortField, $sortOrder);
                    }
                    
                    $projects = $paginationData['projects'];
                    $totalProjects = $paginationData['totalProjects'];
                    $totalPages = $paginationData['totalPages'];
                    $currentPage = $paginationData['currentPage'];

                    foreach($projects as $index => $project){
                        // Format các ngày tháng
                        $date_start = date('d/m/Y', strtotime($project['date_start']));
                        $date_finish = date('d/m/Y', strtotime($project['date_finish']));
                        
                        // Set màu cho trạng thái
                        $statusClass = '';
                        switch($project['status']) {
                            case 'Not started':
                                $statusClass = 'text-secondary';
                                break;
                            case 'In progress':
                                $statusClass = 'text-primary';
                                break;
                            case 'Completed':
                                $statusClass = 'text-success';
                                break;
                            case 'Canceled':
                                $statusClass = 'text-danger';
                                break;
                            case 'Pending extension':
                                $statusClass = 'text-warning';
                                break;
                            default:
                                $statusClass = 'text-dark';
                        }
                    ?>
                        <tr>
                            <td class="text-center"><?= $project["id"] ?></td>
                            <td><?= $project["project_code"] ?></td>
                            <td><?= $project["project_name"] ?></td>
                            <td class="text-center"><?= $date_start ?></td>
                            <td class="text-center"><?= $date_finish ?></td>
                            <td class="text-center"><span class="<?= $statusClass ?>"><?= $project["status"] ?></span></td>
                            <td class="text-center"><?= $project["number_extension"] ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <?php if (!empty($project["uuid"]) && !empty($project["file"])): ?>
                                    <a href="project.php?uuid=<?= $project["uuid"] ?>" target="_blank" 
                                       class="btn btn-info btn-sm pdf-view-btn" 
                                       title="Xem file PDF của đề tài">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="btn btn-secondary btn-sm" title="Chưa có file PDF">
                                        <i class="bi bi-file-earmark-x"></i>
                                    </span>
                                    <?php endif; ?>
                                    <a href="project/edit_project.php?id=<?= $project["id"] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                    <a href="../handle/project_process.php?action=delete&id=<?= $project["id"] ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Bạn có chắc chắn muốn xóa đề tài này?')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <?php if ($limit !== 'all'): ?>
        <nav aria-label="Phân trang đề tài">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <?php if ($totalPages > 1): ?>
                <ul class="pagination pagination-sm mb-0">
                    <!-- Nút Previous -->
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPaginationUrl($currentPage - 1, $sortField, $sortOrder, $limit, $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, $searchStatus, $searchNumberExtension) ?>">
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
                        echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl(1, $sortField, $sortOrder, $limit, $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, $searchStatus, $searchNumberExtension) . '">1</a></li>';
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
                                <a class="page-link" href="<?= buildPaginationUrl($i, $sortField, $sortOrder, $limit, $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, $searchStatus, $searchNumberExtension) ?>"><?= $i ?></a>
                            </li>
                        <?php endif;
                    endfor;
                    
                    // Hiển thị trang cuối nếu cần
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl($totalPages, $sortField, $sortOrder, $limit, $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, $searchStatus, $searchNumberExtension) . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Nút Next -->
                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPaginationUrl($currentPage + 1, $sortField, $sortOrder, $limit, $searchProjectCode, $searchProjectName, $searchDateStart, $searchDateFinish, $searchStatus, $searchNumberExtension) ?>">
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
                Hiển thị tất cả <?= $totalProjects ?> đề tài
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
            
            window.location.href = `project.php?limit=${limit}&sort_field=${sortField}&sort_order=${sortOrder}&page=1`;
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
            window.location.href = 'project.php?' + urlParams.toString();
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
