<?php
require_once __DIR__ . '/../functions/teacher_functions.php';

// Kiểm tra action được truyền qua URL hoặc POST
$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
}

switch ($action) {
    case 'create':
        handleCreateTeacher();
        break;
    case 'edit':
        handleEditTeacher();
        break;
    case 'delete':
        handleDeleteTeacher();
        break;
    case 'sort':
        handleSortTeachers();
        break;
    // default:
    //     header("Location: ../views/teacher.php?error=Hành động không hợp lệ");
    //     exit();
}

/**
 * Xử lý sắp xếp danh sách giảng viên
 */
function handleSortTeachers() {
    $field = isset($_GET['field']) ? $_GET['field'] : 'id';
    $order = isset($_GET['order']) ? $_GET['order'] : 'asc';
    
    $sortedTeachers = getSortedTeachers($field, $order);
    
    // Lưu thông tin sắp xếp vào session để duy trì trạng thái
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['teacher_sort_field'] = $field;
    $_SESSION['teacher_sort_order'] = $order;
    
    // Chuyển hướng về trang danh sách với thông tin sắp xếp
    header("Location: ../views/teacher.php?sorted=true");
    exit();
}

/**
 * Lấy tất cả danh sách giảng viên
 */
function handleGetAllTeachers() {
    return getAllTeachers();
}

/**
 * Lấy thông tin giảng viên theo ID
 */
function handleGetTeacherById($id) {
    return getTeacherById($id);
}

/**
 * Xử lý tạo giảng viên mới
 */
function handleCreateTeacher() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/teacher.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['teacher_code']) || !isset($_POST['teacher_name']) || !isset($_POST['teacher_email'])) {
        header("Location: ../views/teacher/create_teacher.php?error=Thiếu thông tin cần thiết");
        exit();
    }
    
    $teacher_code = trim($_POST['teacher_code']);
    $teacher_name = trim($_POST['teacher_name']);
    $teacher_email = trim($_POST['teacher_email']);
    
    // Validate dữ liệu
    if (empty($teacher_code) || empty($teacher_name) || empty($teacher_email)) {
        header("Location: ../views/teacher/create_teacher.php?error=Vui lòng điền đầy đủ thông tin");
        exit();
    }
    
    // Validate định dạng email
    if (!filter_var($teacher_email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../views/teacher/create_teacher.php?error=Định dạng email không hợp lệ");
        exit();
    }
    
    // Gọi hàm thêm giảng viên
    $result = addTeacher($teacher_code, $teacher_name, $teacher_email);
    
    if ($result) {
        header("Location: ../views/teacher.php?success=Thêm giảng viên thành công");
    } else {
        header("Location: ../views/teacher/create_teacher.php?error=Có lỗi xảy ra khi thêm giảng viên");
    }
    exit();
}

/**
 * Xử lý chỉnh sửa giảng viên
 */
function handleEditTeacher() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/teacher.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['id']) || !isset($_POST['teacher_code']) || !isset($_POST['teacher_name']) || !isset($_POST['teacher_email'])) {
        header("Location: ../views/teacher.php?error=Thiếu thông tin cần thiết");
        exit();
    }
    
    $id = $_POST['id'];
    $teacher_code = trim($_POST['teacher_code']);
    $teacher_name = trim($_POST['teacher_name']);
    $teacher_email = trim($_POST['teacher_email']);
    
    // Validate dữ liệu
    if (empty($teacher_code) || empty($teacher_name) || empty($teacher_email)) {
        header("Location: ../views/teacher/edit_teacher.php?id=" . $id . "&error=Vui lòng điền đầy đủ thông tin");
        exit();
    }
    
    // Validate định dạng email
    if (!filter_var($teacher_email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../views/teacher/edit_teacher.php?id=" . $id . "&error=Định dạng email không hợp lệ");
        exit();
    }
    
    // Gọi function để cập nhật giảng viên
    $result = updateTeacher($id, $teacher_code, $teacher_name, $teacher_email);
    
    if ($result) {
        header("Location: ../views/teacher.php?success=Cập nhật giảng viên thành công");
    } else {
        header("Location: ../views/teacher/edit_teacher.php?id=" . $id . "&error=Cập nhật giảng viên thất bại");
    }
    exit();
}

/**
 * Xử lý xóa giảng viên
 */
function handleDeleteTeacher() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header("Location: ../views/teacher.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: ../views/teacher.php?error=Không tìm thấy ID giảng viên");
        exit();
    }
    
    $id = $_GET['id'];
    
    // Validate ID là số
    if (!is_numeric($id)) {
        header("Location: ../views/teacher.php?error=ID giảng viên không hợp lệ");
        exit();
    }
    
    // Gọi function để xóa giảng viên
    $result = deleteTeacher($id);
    
    if ($result) {
        header("Location: ../views/teacher.php?success=Xóa giảng viên thành công");
    } else {
        header("Location: ../views/teacher.php?error=Xóa giảng viên thất bại");
    }
    exit();
}

/**
 * Lấy danh sách giảng viên với phân trang
 */
function handleGetTeachersWithPagination($page = 1, $limit = 10, $sortField = 'id', $sortOrder = 'asc') {
    return getTeachersWithPagination($page, $limit, $sortField, $sortOrder);
}

/**
 * Tìm kiếm giảng viên với phân trang
 */
function handleSearchTeachersWithPagination($page = 1, $limit = 10, $sortField = 'id', $sortOrder = 'asc', $searchTeacherCode = '', $searchTeacherName = '', $searchTeacherEmail = '') {
    return searchTeachersWithPagination($page, $limit, $sortField, $sortOrder, $searchTeacherCode, $searchTeacherName, $searchTeacherEmail);
}
?>
