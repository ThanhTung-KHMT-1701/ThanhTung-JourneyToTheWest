<?php
require_once __DIR__ . '/../functions/auth.php';
checkLogin(__DIR__ . '/../index.php');

// Kiểm tra nếu có tham số uuid để hiển thị PDF gia hạn
if (isset($_GET['uuid']) && !empty($_GET['uuid'])) {
    require_once __DIR__ . '/../functions/db_connection.php';
    
    try {
        $uuid = trim($_GET['uuid']);
        $conn = getDbConnection();
        
        // Truy vấn để lấy file PDF gia hạn từ database
        $sql = "SELECT pe.file, p.project_name FROM project_extensions pe 
                INNER JOIN projects p ON pe.project_id = p.id 
                WHERE pe.uuid = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $uuid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $extension = mysqli_fetch_assoc($result);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        if ($extension && !empty($extension['file'])) {
            // Set headers để hiển thị PDF trong browser
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Don_gia_han_' . $extension['project_name'] . '.pdf"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Output file content
            echo $extension['file'];
            exit;
        } else {
            // Nếu không tìm thấy file, hiển thị thông báo lỗi
            echo '<div style="text-align: center; margin-top: 50px;">';
            echo '<h3>Không tìm thấy file đơn gia hạn</h3>';
            echo '<p>File đơn gia hạn không tồn tại hoặc đã bị xóa.</p>';
            echo '<a href="project_extensions.php" class="btn btn-primary">Quay lại danh sách gia hạn</a>';
            echo '</div>';
            exit;
        }
    } catch (Exception $e) {
        echo '<div style="text-align: center; margin-top: 50px;">';
        echo '<h3>Lỗi hệ thống</h3>';
        echo '<p>Có lỗi xảy ra khi tải file đơn gia hạn.</p>';
        echo '<a href="project_extensions.php" class="btn btn-primary">Quay lại danh sách gia hạn</a>';
        echo '</div>';
        exit;
    }
}

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
$searchDateFinishBefore = isset($_GET['search_datefinish_before']) ? trim($_GET['search_datefinish_before']) : '';
$searchDateFinishAfter = isset($_GET['search_datefinish_after']) ? trim($_GET['search_datefinish_after']) : '';

// Lấy thông tin sắp xếp từ session
$sortField = isset($_SESSION['project_extensions_sort_field']) ? $_SESSION['project_extensions_sort_field'] : 'id';
$sortOrder = isset($_SESSION['project_extensions_sort_order']) ? $_SESSION['project_extensions_sort_order'] : 'asc';

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
function buildPaginationUrl($page, $sortField, $sortOrder, $limit, $searchProjectName = '', $searchDateFinishBefore = '', $searchDateFinishAfter = '') {
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
    if (!empty($searchDateFinishBefore)) {
        $params['search_datefinish_before'] = $searchDateFinishBefore;
    }
    if (!empty($searchDateFinishAfter)) {
        $params['search_datefinish_after'] = $searchDateFinishAfter;
    }
    
    return 'project_extensions.php?' . http_build_query($params);
}

// Xây dựng URL cho sắp xếp với các tham số hiện tại
function buildSortUrl($field, $sortField, $sortOrder, $page, $limit, $searchProjectName = '', $searchProjectCode = '', $searchDateFinishBefore = '', $searchDateFinishAfter = '') {
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
    if (!empty($searchDateFinishBefore)) {
        $params['search_datefinish_before'] = $searchDateFinishBefore;
    }
    if (!empty($searchDateFinishAfter)) {
        $params['search_datefinish_after'] = $searchDateFinishAfter;
    }
    
    return 'project_extensions.php?' . http_build_query($params);
}

// Thiết lập title cho trang
$pageTitle = 'Gia hạn đề tài - DNU OpenSource';

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

/* Style cho icon xem PDF */
.btn-view-pdf {
    background-color: #fff !important;
    border-color: #007bff !important;
    color: #007bff !important;
}

.btn-view-pdf:hover {
    background-color: #007bff !important;
    border-color: #007bff !important;
    color: #fff !important;
    transform: scale(1.05);
}

/* Hiệu ứng hover cho icon mắt */
.btn-view-pdf i.bi-eye {
    display: inline-block;
}

.btn-view-pdf i.bi-eye-fill {
    display: none;
}

.btn-view-pdf:hover i.bi-eye {
    display: none;
}

.btn-view-pdf:hover i.bi-eye-fill {
    display: inline-block;
}
';

include __DIR__ . '/header.php';
?>

<body>
    <?php include './menu.php'; ?>
    <div class="container mt-3">
        
        <h3 class="mt-3 mb-3 text-center">DANH SÁCH GIA HẠN ĐỀ TÀI</h3>
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
                
        <!-- Nút thêm gia hạn và các controls -->
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <!-- Nút thêm gia hạn bên trái -->
                <div>
                    <a href="project_extensions/create_project_extension.php" class="btn btn-primary"> 
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
        
        <!-- Form tìm kiếm gia hạn đề tài -->
        <form method="GET" action="project_extensions.php" id="searchForm">
            <!-- Giữ nguyên các tham số hiện tại -->
            <input type="hidden" name="limit" value="<?= htmlspecialchars($limit) ?>">
            <input type="hidden" name="sort_field" value="<?= htmlspecialchars($sortField) ?>">
            <input type="hidden" name="sort_order" value="<?= htmlspecialchars($sortOrder) ?>">
            <input type="hidden" name="page" value="1"> <!-- Reset về trang 1 khi tìm kiếm -->
            
            <div class="row mb-3 mt-3">
                <div class="col-md-12">
                    <!-- Hàng đầu: Tên đề tài -->
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
                    
                    <!-- Hàng thứ hai: Mã đề tài, Ngày kết thúc trước và sau -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Mã đề tài</span>
                                <input type="text" class="form-control" name="search_project_code" 
                                       value="<?= htmlspecialchars($searchProjectCode) ?>"
                                       placeholder="Nhập mã đề tài để tìm kiếm"
                                       onblur="document.getElementById('searchForm').submit()"
                                       onkeypress="if(event.key === 'Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Ngày kết thúc trước</span>
                                <input type="date" class="form-control" name="search_datefinish_before" 
                                       value="<?= htmlspecialchars($searchDateFinishBefore) ?>"
                                       onblur="document.getElementById('searchForm').submit()"
                                       onkeypress="if(event.key === 'Enter') document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary text-white">Ngày kết thúc sau</span>
                                <input type="date" class="form-control" name="search_datefinish_after" 
                                       value="<?= htmlspecialchars($searchDateFinishAfter) ?>"
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
                        <a href="<?= buildSortUrl('id', $sortField, $sortOrder, $page, $limit, $searchProjectName, $searchProjectCode, $searchDateFinishBefore, $searchDateFinishAfter) ?>" class="text-decoration-none text-dark">
                            ID <?= getSortIcon('id', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('project_code', $sortField, $sortOrder, $page, $limit, $searchProjectName, $searchProjectCode, $searchDateFinishBefore, $searchDateFinishAfter) ?>" class="text-decoration-none text-dark">
                            Mã đề tài <?= getSortIcon('project_code', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('project_name', $sortField, $sortOrder, $page, $limit, $searchProjectName, $searchProjectCode, $searchDateFinishBefore, $searchDateFinishAfter) ?>" class="text-decoration-none text-dark">
                            Tên đề tài <?= getSortIcon('project_name', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('datefinish_before', $sortField, $sortOrder, $page, $limit, $searchProjectName, $searchProjectCode, $searchDateFinishBefore, $searchDateFinishAfter) ?>" class="text-decoration-none text-dark">
                            Ngày kết thúc trước <?= getSortIcon('datefinish_before', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('datefinish_after', $sortField, $sortOrder, $page, $limit, $searchProjectName, $searchProjectCode, $searchDateFinishBefore, $searchDateFinishAfter) ?>" class="text-decoration-none text-dark">
                            Ngày kết thúc sau <?= getSortIcon('datefinish_after', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require '../handle/project_extensions_process.php';
                
                // Cập nhật thông tin sắp xếp từ URL nếu có
                if (isset($_GET['sort_field']) && isset($_GET['sort_order'])) {
                    $sortField = $_GET['sort_field'];
                    $sortOrder = $_GET['sort_order'];
                    $_SESSION['project_extensions_sort_field'] = $sortField;
                    $_SESSION['project_extensions_sort_order'] = $sortOrder;
                }
                
                // Kiểm tra có tìm kiếm hay không
                $hasSearch = !empty($searchProjectName) || !empty($searchProjectCode) || !empty($searchDateFinishBefore) || !empty($searchDateFinishAfter);
                
                if ($hasSearch) {
                    // Lấy danh sách gia hạn với tìm kiếm và phân trang
                    $paginationData = handleSearchProjectExtensionsWithPagination($page, $limit, $sortField, $sortOrder, $searchProjectName, $searchProjectCode, $searchDateFinishBefore, $searchDateFinishAfter);
                } else {
                    // Lấy danh sách gia hạn thông thường với phân trang
                    $paginationData = handleGetProjectExtensionsWithPagination($page, $limit, $sortField, $sortOrder);
                }
                
                $extensions = $paginationData['extensions'];
                $totalExtensions = $paginationData['totalExtensions'];
                $totalPages = $paginationData['totalPages'];
                $currentPage = $paginationData['currentPage'];

                // Tìm bản ghi có ngày kết thúc sau lớn nhất cho mỗi mã đề tài (chỉ trong kết quả hiện tại)
                $maxDateFinishIds = [];
                foreach ($extensions as $extension) {
                    $projectCode = $extension["project_code"];
                    if (!isset($maxDateFinishIds[$projectCode]) || 
                        strtotime($extension["datefinish_after"]) > strtotime($maxDateFinishIds[$projectCode]["datefinish_after"])) {
                        $maxDateFinishIds[$projectCode] = $extension;
                    }
                }

                foreach($extensions as $index => $extension){
                    // Kiểm tra xem đây có phải là bản ghi có ngày kết thúc sau lớn nhất cho mã đề tài này không
                    $isLatestExtension = ($extension["id"] == $maxDateFinishIds[$extension["project_code"]]["id"]);
                ?>
                    <tr>
                        <td class="text-center"><?= $extension["id"] ?></td>
                        <td><?= htmlspecialchars($extension["project_code"]) ?></td>
                        <td><?= htmlspecialchars($extension["project_name"]) ?></td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($extension["datefinish_before"])) ?></td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($extension["datefinish_after"])) ?></td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <!-- Icon xem PDF -->
                                <a href="project_extensions.php?uuid=<?= $extension["uuid"] ?>" 
                                   class="btn btn-view-pdf btn-sm" 
                                   target="_blank" 
                                   title="Xem file đơn gia hạn">
                                    <i class="bi bi-eye"></i>
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <?php if ($isLatestExtension): ?>
                                    <a href="project_extensions/edit_project_extension.php?id=<?= $extension["id"] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <!-- Phân trang -->
        <?php if ($limit !== 'all'): ?>
        <nav aria-label="Phân trang gia hạn đề tài">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <?php if ($totalPages > 1): ?>
                <ul class="pagination pagination-sm mb-0">
                    <!-- Nút Previous -->
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPaginationUrl($currentPage - 1, $sortField, $sortOrder, $limit, $searchProjectName, $searchDateFinishBefore, $searchDateFinishAfter) ?>">
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
                        echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl(1, $sortField, $sortOrder, $limit, $searchProjectName, $searchDateFinishBefore, $searchDateFinishAfter) . '">1</a></li>';
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
                                <a class="page-link" href="<?= buildPaginationUrl($i, $sortField, $sortOrder, $limit, $searchProjectName, $searchDateFinishBefore, $searchDateFinishAfter) ?>"><?= $i ?></a>
                            </li>
                        <?php endif;
                    endfor;
                    
                    // Hiển thị trang cuối nếu cần
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl($totalPages, $sortField, $sortOrder, $limit, $searchProjectName, $searchDateFinishBefore, $searchDateFinishAfter) . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Nút Next -->
                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPaginationUrl($currentPage + 1, $sortField, $sortOrder, $limit, $searchProjectName, $searchDateFinishBefore, $searchDateFinishAfter) ?>">
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
                Hiển thị tất cả <?= $totalExtensions ?> gia hạn đề tài
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
            
            window.location.href = `project_extensions.php?limit=${limit}&sort_field=${sortField}&sort_order=${sortOrder}&page=1`;
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
            window.location.href = 'project_extensions.php?' + urlParams.toString();
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
