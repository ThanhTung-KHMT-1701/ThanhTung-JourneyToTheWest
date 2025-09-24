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
$searchTeacherCode = isset($_GET['search_teacher_code']) ? trim($_GET['search_teacher_code']) : '';
$searchTeacherName = isset($_GET['search_teacher_name']) ? trim($_GET['search_teacher_name']) : '';
$searchTeacherEmail = isset($_GET['search_teacher_email']) ? trim($_GET['search_teacher_email']) : '';

// Lấy thông tin sắp xếp từ session
$sortField = isset($_SESSION['teacher_sort_field']) ? $_SESSION['teacher_sort_field'] : 'id';
$sortOrder = isset($_SESSION['teacher_sort_order']) ? $_SESSION['teacher_sort_order'] : 'asc';

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
function buildPaginationUrl($page, $sortField, $sortOrder, $limit, $searchTeacherCode = '', $searchTeacherName = '', $searchTeacherEmail = '') {
    $params = [
        'page' => $page,
        'sort_field' => $sortField,
        'sort_order' => $sortOrder,
        'limit' => $limit
    ];
    
    // Thêm tham số tìm kiếm nếu có
    if (!empty($searchTeacherCode)) {
        $params['search_teacher_code'] = $searchTeacherCode;
    }
    if (!empty($searchTeacherName)) {
        $params['search_teacher_name'] = $searchTeacherName;
    }
    if (!empty($searchTeacherEmail)) {
        $params['search_teacher_email'] = $searchTeacherEmail;
    }
    
    return 'teacher.php?' . http_build_query($params);
}

// Xây dựng URL cho sắp xếp với các tham số hiện tại
function buildSortUrl($field, $sortField, $sortOrder, $page, $limit, $searchTeacherCode = '', $searchTeacherName = '', $searchTeacherEmail = '') {
    $params = [
        'page' => $page,
        'sort_field' => $field,
        'sort_order' => getNextSortOrder($field, $sortField, $sortOrder),
        'limit' => $limit
    ];
    
    // Thêm tham số tìm kiếm nếu có
    if (!empty($searchTeacherCode)) {
        $params['search_teacher_code'] = $searchTeacherCode;
    }
    if (!empty($searchTeacherName)) {
        $params['search_teacher_name'] = $searchTeacherName;
    }
    if (!empty($searchTeacherEmail)) {
        $params['search_teacher_email'] = $searchTeacherEmail;
    }
    
    return 'teacher.php?' . http_build_query($params);
}

// Thiết lập title cho trang
$pageTitle = 'Danh sách giảng viên - DNU OpenSource';

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
';

include __DIR__ . '/header.php';
?>

<body>
    <?php include './menu.php'; ?>
    <div class="container mt-3">
        
        <h3 class="mt-3 mb-3 text-center">DANH SÁCH GIẢNG VIÊN</h3>
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
        
        <!-- Nút thêm giảng viên và các controls -->
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <!-- Nút thêm giảng viên bên trái -->
                <div>
                    <a href="teacher/create_teacher.php" class="btn btn-primary">
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
        
        <!-- Form tìm kiếm giảng viên -->
        <form method="GET" action="teacher.php" id="searchForm">
            <!-- Giữ nguyên các tham số hiện tại -->
            <input type="hidden" name="limit" value="<?= htmlspecialchars($limit) ?>">
            <input type="hidden" name="sort_field" value="<?= htmlspecialchars($sortField) ?>">
            <input type="hidden" name="sort_order" value="<?= htmlspecialchars($sortOrder) ?>">
            <input type="hidden" name="page" value="1"> <!-- Reset về trang 1 khi tìm kiếm -->
            
            <div class="row mb-3 mt-3">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary  text-white">Mã giảng viên</span>
                                <input type="text" class="form-control" name="search_teacher_code" 
                                       value="<?= htmlspecialchars($searchTeacherCode) ?>"
                                       placeholder="Nhập mã giảng viên"
                                       onchange="document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary  text-white">Họ và tên</span>
                                <input type="text" class="form-control" name="search_teacher_name" 
                                       value="<?= htmlspecialchars($searchTeacherName) ?>"
                                       placeholder="Nhập họ và tên"
                                       onchange="document.getElementById('searchForm').submit()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-secondary  text-white">Email</span>
                                <input type="text" class="form-control" name="search_teacher_email" 
                                       value="<?= htmlspecialchars($searchTeacherEmail) ?>"
                                       placeholder="Nhập email"
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
                        <a href="<?= buildSortUrl('id', $sortField, $sortOrder, $page, $limit, $searchTeacherCode, $searchTeacherName, $searchTeacherEmail) ?>" class="text-decoration-none text-dark">
                            ID <?= getSortIcon('id', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('teacher_code', $sortField, $sortOrder, $page, $limit, $searchTeacherCode, $searchTeacherName, $searchTeacherEmail) ?>" class="text-decoration-none text-dark">
                            Mã giảng viên <?= getSortIcon('teacher_code', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('teacher_name', $sortField, $sortOrder, $page, $limit, $searchTeacherCode, $searchTeacherName, $searchTeacherEmail) ?>" class="text-decoration-none text-dark">
                            Họ và tên <?= getSortIcon('teacher_name', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">
                        <a href="<?= buildSortUrl('teacher_email', $sortField, $sortOrder, $page, $limit, $searchTeacherCode, $searchTeacherName, $searchTeacherEmail) ?>" class="text-decoration-none text-dark">
                            Email <?= getSortIcon('teacher_email', $sortField, $sortOrder) ?>
                        </a>
                    </th>
                    <th scope="col" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require '../handle/teacher_process.php';
                
                // Cập nhật thông tin sắp xếp từ URL nếu có
                if (isset($_GET['sort_field']) && isset($_GET['sort_order'])) {
                    $sortField = $_GET['sort_field'];
                    $sortOrder = $_GET['sort_order'];
                    $_SESSION['teacher_sort_field'] = $sortField;
                    $_SESSION['teacher_sort_order'] = $sortOrder;
                }
                
                // Kiểm tra có tìm kiếm hay không
                $hasSearch = !empty($searchTeacherCode) || !empty($searchTeacherName) || !empty($searchTeacherEmail);
                
                if ($hasSearch) {
                    // Lấy danh sách giảng viên với tìm kiếm và phân trang
                    $paginationData = handleSearchTeachersWithPagination($page, $limit, $sortField, $sortOrder, $searchTeacherCode, $searchTeacherName, $searchTeacherEmail);
                } else {
                    // Lấy danh sách giảng viên thông thường với phân trang
                    $paginationData = handleGetTeachersWithPagination($page, $limit, $sortField, $sortOrder);
                }
                
                $teachers = $paginationData['teachers'];
                $totalTeachers = $paginationData['totalTeachers'];
                $totalPages = $paginationData['totalPages'];
                $currentPage = $paginationData['currentPage'];

                foreach($teachers as $index => $teacher){
                    // Tính STT dựa trên trang hiện tại
                    $stt = ($currentPage - 1) * ($limit === 'all' ? 0 : $limit) + $index + 1;
                ?>
                    <tr>
                        <td class="text-center"><?= $teacher["id"] ?></td>
                        <td class="text-center"><?= $teacher["teacher_code"] ?></td>
                        <td><?= $teacher["teacher_name"] ?></td>
                        <td><?= $teacher["teacher_email"] ?></td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="teacher/edit_teacher.php?id=<?= $teacher["id"] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="../handle/teacher_process.php?action=delete&id=<?= $teacher["id"] ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Bạn có chắc chắn muốn xóa giảng viên này?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <!-- Phân trang -->
        <?php if ($limit !== 'all'): ?>
        <nav aria-label="Phân trang giảng viên">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <?php if ($totalPages > 1): ?>
                <ul class="pagination pagination-sm mb-0">
                    <!-- Nút Previous -->
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPaginationUrl($currentPage - 1, $sortField, $sortOrder, $limit, $searchTeacherCode, $searchTeacherName, $searchTeacherEmail) ?>">
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
                        echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl(1, $sortField, $sortOrder, $limit, $searchTeacherCode, $searchTeacherName, $searchTeacherEmail) . '">1</a></li>';
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
                                <a class="page-link" href="<?= buildPaginationUrl($i, $sortField, $sortOrder, $limit, $searchTeacherCode, $searchTeacherName, $searchTeacherEmail) ?>"><?= $i ?></a>
                            </li>
                        <?php endif;
                    endfor;
                    
                    // Hiển thị trang cuối nếu cần
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl($totalPages, $sortField, $sortOrder, $limit, $searchTeacherCode, $searchTeacherName, $searchTeacherEmail) . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Nút Next -->
                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPaginationUrl($currentPage + 1, $sortField, $sortOrder, $limit, $searchTeacherCode, $searchTeacherName, $searchTeacherEmail) ?>">
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
                Hiển thị tất cả <?= $totalTeachers ?> giảng viên
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
            
            window.location.href = `teacher.php?limit=${limit}&sort_field=${sortField}&sort_order=${sortOrder}&page=1`;
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
            window.location.href = 'teacher.php?' + urlParams.toString();
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
