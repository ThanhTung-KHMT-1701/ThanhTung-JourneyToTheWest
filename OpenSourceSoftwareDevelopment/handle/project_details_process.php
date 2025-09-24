<?php
require_once __DIR__ . '/../functions/project_details_functions.php';

// Kiểm tra action được truyền qua URL hoặc POST
$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
}

switch ($action) {
    case 'create':
        handleCreateProjectDetail();
        break;
    case 'edit':
        handleEditProjectDetail();
        break;
    case 'delete':
        handleDeleteProjectDetail();
        break;
    case 'sort':
        handleSortProjectDetails();
        break;
}

/**
 * Lấy tất cả danh sách chi tiết đề tài
 */
function handleGetAllProjectDetails() {
    return getAllProjectDetails();
}

/**
 * Lấy chi tiết đề tài theo ID
 */
function handleGetProjectDetailById($id) {
    return getProjectDetailById($id);
}

/**
 * Lấy tất cả đề tài để chọn
 */
function handleGetAllProjects() {
    return getAllProjects();
}

/**
 * Lấy tất cả giảng viên để chọn
 */
function handleGetAllTeachers() {
    return getAllTeachers();
}

/**
 * Lấy tất cả sinh viên để chọn
 */
function handleGetAllStudents() {
    return getAllStudentsForSelect();
}

/**
 * Xử lý sắp xếp chi tiết đề tài
 */
function handleSortProjectDetails() {
    $field = isset($_GET['field']) ? $_GET['field'] : 'id';
    $order = isset($_GET['order']) ? $_GET['order'] : 'asc';
    
    $sortedDetails = getSortedProjectDetails($field, $order);
    
    // Lưu thông tin sắp xếp vào session để duy trì trạng thái
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['sort_field'] = $field;
    $_SESSION['sort_order'] = $order;
    
    // Chuyển hướng về trang danh sách với thông tin sắp xếp
    header("Location: ../views/project_details.php?sorted=true");
    exit();
}

/**
 * Xử lý tạo chi tiết đề tài mới
 */
function handleCreateProjectDetail() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/project_details.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['project_id']) || !isset($_POST['teacher_id']) || !isset($_POST['student_id']) || !isset($_POST['student_role'])) {
        header("Location: ../views/project_details/create_project_detail.php?error=Thiếu thông tin cần thiết");
        exit();
    }
    
    $project_id = (int)$_POST['project_id'];
    $teacher_id = (int)$_POST['teacher_id'];
    $student_id = (int)$_POST['student_id'];
    $student_role = trim($_POST['student_role']);
    
    // Validate dữ liệu
    if ($project_id <= 0 || $teacher_id <= 0 || $student_id <= 0 || empty($student_role)) {
        header("Location: ../views/project_details/create_project_detail.php?error=Vui lòng điền đầy đủ thông tin");
        exit();
    }
    
    // Gọi hàm thêm chi tiết đề tài
    $result = addProjectDetail($project_id, $teacher_id, $student_id, $student_role);
    
    if ($result) {
        header("Location: ../views/project_details.php?success=Thêm chi tiết đề tài thành công");
    } else {
        header("Location: ../views/project_details/create_project_detail.php?error=Có lỗi xảy ra khi thêm chi tiết đề tài");
    }
    exit();
}

/**
 * Xử lý chỉnh sửa chi tiết đề tài
 */
function handleEditProjectDetail() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/project_details.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['id']) || !isset($_POST['project_id']) || !isset($_POST['teacher_id']) || !isset($_POST['student_id']) || !isset($_POST['student_role'])) {
        header("Location: ../views/project_details.php?error=Thiếu thông tin cần thiết");
        exit();
    }
    
    $id = $_POST['id'];
    $project_id = (int)$_POST['project_id'];
    $teacher_id = (int)$_POST['teacher_id'];
    $student_id = (int)$_POST['student_id'];
    $student_role = trim($_POST['student_role']);
    
    // Validate dữ liệu
    if ($project_id <= 0 || $teacher_id <= 0 || $student_id <= 0 || empty($student_role)) {
        header("Location: ../views/project_details/edit_project_detail.php?id=" . $id . "&error=Vui lòng điền đầy đủ thông tin");
        exit();
    }
    
    // Gọi function để cập nhật chi tiết đề tài
    $result = updateProjectDetail($id, $project_id, $teacher_id, $student_id, $student_role);
    
    if ($result) {
        header("Location: ../views/project_details.php?success=Cập nhật chi tiết đề tài thành công");
    } else {
        header("Location: ../views/project_details/edit_project_detail.php?id=" . $id . "&error=Cập nhật chi tiết đề tài thất bại");
    }
    exit();
}

/**
 * Xử lý xóa chi tiết đề tài
 */
function handleDeleteProjectDetail() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header("Location: ../views/project_details.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: ../views/project_details.php?error=Không tìm thấy ID chi tiết đề tài");
        exit();
    }
    
    $id = $_GET['id'];
    
    // Validate ID là số
    if (!is_numeric($id)) {
        header("Location: ../views/project_details.php?error=ID chi tiết đề tài không hợp lệ");
        exit();
    }
    
    // Gọi function để xóa chi tiết đề tài
    $result = deleteProjectDetail($id);
    
    if ($result) {
        header("Location: ../views/project_details.php?success=Xóa chi tiết đề tài thành công");
    } else {
        header("Location: ../views/project_details.php?error=Xóa chi tiết đề tài thất bại");
    }
    exit();
}

/**
 * Lấy danh sách chi tiết đề tài với phân trang
 */
function handleGetProjectDetailsWithPagination($page = 1, $limit = 10, $field = 'id', $order = 'asc') {
    $project_details = getProjectDetailsWithPagination($page, $limit, $field, $order);
    $totalProjectDetails = getTotalProjectDetails();
    
    // Tính toán thông tin phân trang
    if ($limit === 'all') {
        $totalPages = 1;
        $currentPage = 1;
    } else {
        $totalPages = ceil($totalProjectDetails / $limit);
        $currentPage = max(1, min($page, $totalPages));
    }
    
    return [
        'project_details' => $project_details,
        'totalProjectDetails' => $totalProjectDetails,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage
    ];
}

/**
 * Tìm kiếm chi tiết đề tài với phân trang
 */
function handleSearchProjectDetailsWithPagination($page = 1, $limit = 10, $field = 'id', $order = 'asc', $projectName = '', $projectCode = '', $teacherName = '', $studentName = '', $studentRole = '') {
    $project_details = searchProjectDetailsWithPagination($page, $limit, $field, $order, $projectName, $projectCode, $teacherName, $studentName, $studentRole);
    $totalProjectDetails = getTotalSearchProjectDetails($projectName, $projectCode, $teacherName, $studentName, $studentRole);
    
    // Tính toán thông tin phân trang
    if ($limit === 'all') {
        $totalPages = 1;
        $currentPage = 1;
    } else {
        $totalPages = ceil($totalProjectDetails / $limit);
        $currentPage = max(1, min($page, $totalPages));
    }
    
    return [
        'project_details' => $project_details,
        'totalProjectDetails' => $totalProjectDetails,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage
    ];
}
?>
