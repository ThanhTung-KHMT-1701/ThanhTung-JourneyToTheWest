<?php
// session_start();
require_once __DIR__ . '/../functions/student_functions.php';

// Kiểm tra action được truyền qua URL hoặc POST
$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
}

switch ($action) {
    case 'create':
        handleCreateStudent();
        break;
    case 'edit':
        handleEditStudent();
        break;
    case 'delete':
        handleDeleteStudent();
        break;
    case 'sort':
        handleSortStudents();
        break;
    // default:
    //     header("Location: ../views/student.php?error=Hành động không hợp lệ");
    //     exit();
}

/**
 * Xử lý sắp xếp danh sách sinh viên
 */
function handleSortStudents() {
    $field = isset($_GET['field']) ? $_GET['field'] : 'id';
    $order = isset($_GET['order']) ? $_GET['order'] : 'asc';
    
    $sortedStudents = getSortedStudents($field, $order);
    
    // Lưu thông tin sắp xếp vào session để duy trì trạng thái
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['student_sort_field'] = $field;
    $_SESSION['student_sort_order'] = $order;
    
    // Chuyển hướng về trang danh sách với thông tin sắp xếp
    header("Location: ../views/student.php?sorted=true");
    exit();
}

/**
 * Lấy tất cả danh sách sinh viên
 */
function handleGetAllStudents() {
    return getAllStudents();
}

function handleGetStudentById($id) {
    return getStudentById($id);
}

/**
 * Lấy danh sách sinh viên có phân trang và sắp xếp
 * @param int $page Trang hiện tại
 * @param int|string $limit Số bản ghi trên mỗi trang hoặc 'all'
 * @param string $field Trường sắp xếp
 * @param string $order Thứ tự sắp xếp
 * @return array Thông tin phân trang và danh sách sinh viên
 */
function handleGetStudentsWithPagination($page = 1, $limit = 10, $field = 'id', $order = 'asc') {
    $students = getStudentsWithPagination($page, $limit, $field, $order);
    $totalStudents = getTotalStudents();
    
    // Tính toán phân trang
    if ($limit === 'all') {
        $totalPages = 1;
        $currentPage = 1;
    } else {
        $limit = (int)$limit;
        $totalPages = ceil($totalStudents / $limit);
        $currentPage = $page;
    }
    
    return [
        'students' => $students,
        'totalStudents' => $totalStudents,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage,
        'limit' => $limit
    ];
}

/**
 * Xử lý tạo sinh viên mới
 */
function handleCreateStudent() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/student.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['student_code']) || !isset($_POST['student_name']) || !isset($_POST['student_email'])) {
        header("Location: ../views/student/create_student.php?error=Thiếu thông tin cần thiết");
        exit();
    }
    
    $student_code = trim($_POST['student_code']);
    $student_name = trim($_POST['student_name']);
    $student_email = trim($_POST['student_email']);
    
    // Validate dữ liệu
    if (empty($student_code) || empty($student_name) || empty($student_email)) {
        header("Location: ../views/student/create_student.php?error=Vui lòng điền đầy đủ thông tin");
        exit();
    }
    
    // Gọi hàm thêm sinh viên
    $result = addStudent($student_code, $student_name, $student_email);
    
    if ($result) {
        header("Location: ../views/student.php?success=Thêm sinh viên thành công");
    } else {
        header("Location: ../views/student/create_student.php?error=Có lỗi xảy ra khi thêm sinh viên");
    }
    exit();
}

/**
 * Xử lý chỉnh sửa sinh viên
 */
function handleEditStudent() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/student.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['id']) || !isset($_POST['student_code']) || !isset($_POST['student_name']) || !isset($_POST['student_email'])) {
        header("Location: ../views/student.php?error=Thiếu thông tin cần thiết");
        exit();
    }
    
    $id = $_POST['id'];
    $student_code = trim($_POST['student_code']);
    $student_name = trim($_POST['student_name']);
    $student_email = trim($_POST['student_email']);
    
    // Validate dữ liệu
    if (empty($student_code) || empty($student_name) || empty($student_email)) {
        header("Location: ../views/edit_student.php?id=" . $id . "&error=Vui lòng điền đầy đủ thông tin");
        exit();
    }
    
    // Gọi function để cập nhật sinh viên
    $result = updateStudent($id, $student_code, $student_name, $student_email);
    
    if ($result) {
        header("Location: ../views/student.php?success=Cập nhật sinh viên thành công");
    } else {
        header("Location: ../views/edit_student.php?id=" . $id . "&error=Cập nhật sinh viên thất bại");
    }
    exit();
}

/**
 * Xử lý xóa sinh viên
 */
function handleDeleteStudent() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header("Location: ../views/student.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: ../views/student.php?error=Không tìm thấy ID sinh viên");
        exit();
    }
    
    $id = $_GET['id'];
    
    // Validate ID là số
    if (!is_numeric($id)) {
        header("Location: ../views/student.php?error=ID sinh viên không hợp lệ");
        exit();
    }
    
    // Gọi function để xóa sinh viên
    $result = deleteStudent($id);
    
    if ($result) {
        header("Location: ../views/student.php?success=Xóa sinh viên thành công");
    } else {
        header("Location: ../views/student.php?error=Xóa sinh viên thất bại");
    }
    exit();
}

/**
 * Xử lý tìm kiếm sinh viên với phân trang
 * @param int $page Trang hiện tại
 * @param mixed $limit Số lượng bản ghi trên trang hoặc 'all'
 * @param string $field Trường sắp xếp
 * @param string $order Thứ tự sắp xếp (asc/desc)
 * @param string $studentCode Mã sinh viên tìm kiếm
 * @param string $studentName Tên sinh viên tìm kiếm
 * @param string $studentEmail Email sinh viên tìm kiếm
 * @return array Dữ liệu phân trang với tìm kiếm
 */
function handleSearchStudentsWithPagination($page = 1, $limit = 10, $field = 'id', $order = 'asc', $studentCode = '', $studentName = '', $studentEmail = '') {
    $students = searchStudentsWithPagination($page, $limit, $field, $order, $studentCode, $studentName, $studentEmail);
    $totalStudents = getTotalStudentsWithSearch($studentCode, $studentName, $studentEmail);
    
    // Tính toán phân trang
    if ($limit === 'all') {
        $totalPages = 1;
        $currentPage = 1;
    } else {
        $limit = (int)$limit;
        $totalPages = $totalStudents > 0 ? ceil($totalStudents / $limit) : 1;
        $currentPage = $page;
    }
    
    return [
        'students' => $students,
        'totalStudents' => $totalStudents,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage,
        'limit' => $limit,
        'searchParams' => [
            'student_code' => $studentCode,
            'student_name' => $studentName,
            'student_email' => $studentEmail
        ]
    ];
}
?>
