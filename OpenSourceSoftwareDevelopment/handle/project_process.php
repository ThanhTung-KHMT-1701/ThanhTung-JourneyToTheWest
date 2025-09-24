<?php
require_once __DIR__ . '/../functions/project_functions.php';

// Kiểm tra action được truyền qua URL hoặc POST
$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
}

switch ($action) {
    case 'create':
        handleCreateProject();
        break;
    case 'edit':
        handleEditProject();
        break;
    case 'delete':
        handleDeleteProject();
        break;
    case 'sort':
        handleSortProjects();
        break;
    // default:
    //     header("Location: ../views/project.php?error=Hành động không hợp lệ");
    //     exit();
}

/**
 * Xử lý sắp xếp danh sách đề tài
 */
function handleSortProjects() {
    $field = isset($_GET['field']) ? $_GET['field'] : 'id';
    $order = isset($_GET['order']) ? $_GET['order'] : 'asc';
    
    $sortedProjects = getSortedProjects($field, $order);
    
    // Lưu thông tin sắp xếp vào session để duy trì trạng thái
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['project_sort_field'] = $field;
    $_SESSION['project_sort_order'] = $order;
    
    // Chuyển hướng về trang danh sách với thông tin sắp xếp
    header("Location: ../views/project.php?sorted=true");
    exit();
}

/**
 * Lấy tất cả danh sách đề tài
 */
function handleGetAllProjects() {
    return getAllProjects();
}

/**
 * Lấy thông tin đề tài theo ID
 */
function handleGetProjectById($id) {
    return getProjectById($id);
}

/**
 * Xử lý tạo đề tài mới
 */
function handleCreateProject() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/project.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['project_code']) || !isset($_POST['project_name']) || 
        !isset($_POST['date_start']) || !isset($_POST['date_finish']) || !isset($_POST['status'])) {
        header("Location: ../views/project/create_project.php?error=Thiếu thông tin cần thiết");
        exit();
    }
    
    $project_code = trim($_POST['project_code']);
    $project_name = trim($_POST['project_name']);
    $date_start = $_POST['date_start'];
    $date_finish = $_POST['date_finish'];
    $status = $_POST['status'];
    
    // Validate dữ liệu
    if (empty($project_code) || empty($project_name) || empty($date_start) || empty($date_finish) || empty($status)) {
        header("Location: ../views/project/create_project.php?error=Vui lòng điền đầy đủ thông tin");
        exit();
    }
    
    // Validate ngày tháng
    $start_timestamp = strtotime($date_start);
    $finish_timestamp = strtotime($date_finish);
    if ($finish_timestamp <= $start_timestamp) {
        header("Location: ../views/project/create_project.php?error=Ngày kết thúc phải sau ngày bắt đầu");
        exit();
    }
    
    // Validate file upload
    if (!isset($_FILES['project_file']) || $_FILES['project_file']['error'] !== UPLOAD_ERR_OK) {
        header("Location: ../views/project/create_project.php?error=Vui lòng chọn file PDF cho đề tài");
        exit();
    }
    
    $file = $_FILES['project_file'];
    
    // Kiểm tra loại file
    $allowedTypes = ['application/pdf'];
    $fileType = $file['type'];
    if (!in_array($fileType, $allowedTypes)) {
        header("Location: ../views/project/create_project.php?error=Chỉ chấp nhận file PDF");
        exit();
    }
    
    // Kiểm tra dung lượng file (16MB = 16 * 1024 * 1024 bytes)
    $maxSize = 16 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        header("Location: ../views/project/create_project.php?error=Dung lượng file không được vượt quá 16MB");
        exit();
    }
    
    // Đọc nội dung file
    $fileContent = file_get_contents($file['tmp_name']);
    if ($fileContent === false) {
        header("Location: ../views/project/create_project.php?error=Không thể đọc file được upload");
        exit();
    }
    
    // Tạo UUID cho file
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    // Gọi hàm thêm đề tài với file
    $result = addProject($project_code, $project_name, $date_start, $date_finish, $status, $uuid, $fileContent);
    
    if ($result) {
        header("Location: ../views/project.php?success=Thêm đề tài thành công");
    } else {
        header("Location: ../views/project/create_project.php?error=Có lỗi xảy ra khi thêm đề tài");
    }
    exit();
}

/**
 * Xử lý chỉnh sửa đề tài
 */
function handleEditProject() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/project.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['id']) || !isset($_POST['project_code']) || !isset($_POST['project_name']) || 
        !isset($_POST['date_start']) || !isset($_POST['date_finish']) || !isset($_POST['status'])) {
        header("Location: ../views/project.php?error=Thiếu thông tin cần thiết");
        exit();
    }
    
    $id = $_POST['id'];
    $project_code = trim($_POST['project_code']);
    $project_name = trim($_POST['project_name']);
    $date_start = $_POST['date_start'];
    $date_finish = $_POST['date_finish'];
    $status = $_POST['status'];
    
    // Validate dữ liệu
    if (empty($project_code) || empty($project_name) || empty($date_start) || empty($date_finish) || empty($status)) {
        header("Location: ../views/project/edit_project.php?id=" . $id . "&error=Vui lòng điền đầy đủ thông tin");
        exit();
    }
    
    // Validate ngày tháng
    $start_timestamp = strtotime($date_start);
    $finish_timestamp = strtotime($date_finish);
    if ($finish_timestamp <= $start_timestamp) {
        header("Location: ../views/project/edit_project.php?id=" . $id . "&error=Ngày kết thúc phải sau ngày bắt đầu");
        exit();
    }
    
    // Xử lý file upload (nếu có)
    $file_content = null;
    if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] == UPLOAD_ERR_OK) {
        // Validate file
        $file_type = $_FILES['project_file']['type'];
        $file_size = $_FILES['project_file']['size'];
        $file_extension = strtolower(pathinfo($_FILES['project_file']['name'], PATHINFO_EXTENSION));
        
        // Kiểm tra định dạng file
        if ($file_extension !== 'pdf' || $file_type !== 'application/pdf') {
            header("Location: ../views/project/edit_project.php?id=" . $id . "&error=Chỉ chấp nhận file PDF");
            exit();
        }
        
        // Kiểm tra kích thước file (16MB = 16 * 1024 * 1024 bytes)
        if ($file_size > 16 * 1024 * 1024) {
            header("Location: ../views/project/edit_project.php?id=" . $id . "&error=File quá lớn (tối đa 16MB)");
            exit();
        }
        
        // Đọc nội dung file
        $file_content = file_get_contents($_FILES['project_file']['tmp_name']);
        if ($file_content === false) {
            header("Location: ../views/project/edit_project.php?id=" . $id . "&error=Không thể đọc file");
            exit();
        }
    }
    
    // Gọi function để cập nhật đề tài
    $result = updateProject($id, $project_code, $project_name, $date_start, $date_finish, $status, $file_content);
    
    if ($result) {
        header("Location: ../views/project.php?success=Cập nhật đề tài thành công");
    } else {
        header("Location: ../views/project/edit_project.php?id=" . $id . "&error=Cập nhật đề tài thất bại");
    }
    exit();
}

/**
 * Xử lý xóa đề tài
 */
function handleDeleteProject() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header("Location: ../views/project.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: ../views/project.php?error=Không tìm thấy ID đề tài");
        exit();
    }
    
    $id = $_GET['id'];
    
    // Validate ID là số
    if (!is_numeric($id)) {
        header("Location: ../views/project.php?error=ID đề tài không hợp lệ");
        exit();
    }
    
    // Gọi function để xóa đề tài
    $result = deleteProject($id);
    
    if ($result) {
        header("Location: ../views/project.php?success=Xóa đề tài thành công");
    } else {
        header("Location: ../views/project.php?error=Xóa đề tài thất bại");
    }
    exit();
}

/**
 * Lấy danh sách đề tài có phân trang và sắp xếp
 * @param int $page Trang hiện tại
 * @param int|string $limit Số bản ghi trên mỗi trang hoặc 'all'
 * @param string $field Trường sắp xếp
 * @param string $order Thứ tự sắp xếp
 * @return array Thông tin phân trang và danh sách đề tài
 */
function handleGetProjectsWithPagination($page = 1, $limit = 10, $field = 'id', $order = 'asc') {
    $projects = getProjectsWithPagination($page, $limit, $field, $order);
    $totalProjects = getTotalProjects();
    
    // Tính toán phân trang
    if ($limit === 'all') {
        $totalPages = 1;
        $currentPage = 1;
    } else {
        $limit = (int)$limit;
        $totalPages = ceil($totalProjects / $limit);
        $currentPage = $page;
    }
    
    return [
        'projects' => $projects,
        'totalProjects' => $totalProjects,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage,
        'limit' => $limit
    ];
}

/**
 * Xử lý tìm kiếm đề tài với phân trang
 * @param int $page Trang hiện tại
 * @param mixed $limit Số lượng bản ghi trên trang hoặc 'all'
 * @param string $field Trường sắp xếp
 * @param string $order Thứ tự sắp xếp (asc/desc)
 * @param string $projectCode Mã đề tài tìm kiếm
 * @param string $projectName Tên đề tài tìm kiếm
 * @param string $dateStart Ngày bắt đầu tìm kiếm
 * @param string $dateFinish Ngày kết thúc tìm kiếm
 * @param string $status Trạng thái tìm kiếm
 * @param string $numberExtension Số lần gia hạn tìm kiếm
 * @return array Dữ liệu phân trang với tìm kiếm
 */
function handleSearchProjectsWithPagination($page = 1, $limit = 10, $field = 'id', $order = 'asc', $projectCode = '', $projectName = '', $dateStart = '', $dateFinish = '', $status = '', $numberExtension = '') {
    $projects = searchProjectsWithPagination($page, $limit, $field, $order, $projectCode, $projectName, $dateStart, $dateFinish, $status, $numberExtension);
    $totalProjects = getTotalProjectsWithSearch($projectCode, $projectName, $dateStart, $dateFinish, $status, $numberExtension);
    
    // Tính toán phân trang
    if ($limit === 'all') {
        $totalPages = 1;
        $currentPage = 1;
    } else {
        $limit = (int)$limit;
        $totalPages = $totalProjects > 0 ? ceil($totalProjects / $limit) : 1;
        $currentPage = $page;
    }
    
    return [
        'projects' => $projects,
        'totalProjects' => $totalProjects,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage,
        'limit' => $limit,
        'searchParams' => [
            'project_code' => $projectCode,
            'project_name' => $projectName,
            'date_start' => $dateStart,
            'date_finish' => $dateFinish,
            'status' => $status,
            'number_extension' => $numberExtension
        ]
    ];
}

// Xử lý các request AJAX cho dynamic loading
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    header('Content-Type: application/json');
    
    // Chỉ cho phép phương thức GET cho AJAX requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    $ajax_action = isset($_GET['action']) ? $_GET['action'] : '';
    
    try {
        switch ($ajax_action) {
            case 'get_project_details':
                // Trường hợp 1: Chỉ chọn đề tài
                if (isset($_GET['project_id']) && $_GET['project_id']) {
                    $project_id = intval($_GET['project_id']);
                    $teachers = getTeachersByProject($project_id);
                    $students = getStudentsByProject($project_id);
                    
                    echo json_encode([
                        'success' => true,
                        'teachers' => $teachers,
                        'students' => $students
                    ]);
                } else {
                    echo json_encode(['error' => 'Project ID is required']);
                }
                break;

            case 'get_teacher_projects':
                // Trường hợp 2: Chỉ chọn giảng viên
                if (isset($_GET['teacher_id']) && $_GET['teacher_id']) {
                    $teacher_id = intval($_GET['teacher_id']);
                    $projects = getProjectsByTeacher($teacher_id);
                    
                    echo json_encode([
                        'success' => true,
                        'projects' => $projects
                    ]);
                } else {
                    echo json_encode(['error' => 'Teacher ID is required']);
                }
                break;

            case 'get_student_projects':
                // Trường hợp 3: Chỉ chọn sinh viên
                if (isset($_GET['student_id']) && $_GET['student_id']) {
                    $student_id = intval($_GET['student_id']);
                    $projects = getProjectsByStudent($student_id);
                    
                    echo json_encode([
                        'success' => true,
                        'projects' => $projects
                    ]);
                } else {
                    echo json_encode(['error' => 'Student ID is required']);
                }
                break;

            case 'get_project_and_teacher_details':
                // Trường hợp 4: Chọn cả đề tài và giảng viên
                if (isset($_GET['project_id']) && $_GET['project_id'] && 
                    isset($_GET['teacher_id']) && $_GET['teacher_id']) {
                    
                    $project_id = intval($_GET['project_id']);
                    $teachers = getTeachersByProject($project_id);
                    $students = getStudentsByProject($project_id);
                    
                    echo json_encode([
                        'success' => true,
                        'teachers' => $teachers,
                        'students' => $students
                    ]);
                } else {
                    echo json_encode(['error' => 'Project ID and Teacher ID are required']);
                }
                break;

            case 'get_project_and_student_details':
                // Trường hợp 5: Chọn cả đề tài và sinh viên
                if (isset($_GET['project_id']) && $_GET['project_id'] && 
                    isset($_GET['student_id']) && $_GET['student_id']) {
                    
                    $project_id = intval($_GET['project_id']);
                    $teachers = getTeachersByProject($project_id);
                    $students = getStudentsByProject($project_id);
                    
                    echo json_encode([
                        'success' => true,
                        'teachers' => $teachers,
                        'students' => $students
                    ]);
                } else {
                    echo json_encode(['error' => 'Project ID and Student ID are required']);
                }
                break;

            case 'get_teacher_student_projects':
                // Trường hợp 6: Chọn cả giảng viên và sinh viên
                if (isset($_GET['teacher_id']) && $_GET['teacher_id'] && 
                    isset($_GET['student_id']) && $_GET['student_id']) {
                    
                    $teacher_id = intval($_GET['teacher_id']);
                    $student_id = intval($_GET['student_id']);
                    $projects = getProjectsByTeacherAndStudent($teacher_id, $student_id);
                    
                    echo json_encode([
                        'success' => true,
                        'projects' => $projects
                    ]);
                } else {
                    echo json_encode(['error' => 'Teacher ID and Student ID are required']);
                }
                break;

            case 'check_duplicate':
                // Kiểm tra trùng lặp khi chọn đủ cả 3 trường
                if (isset($_GET['project_id']) && $_GET['project_id'] && 
                    isset($_GET['teacher_id']) && $_GET['teacher_id'] &&
                    isset($_GET['student_id']) && $_GET['student_id']) {
                    
                    $project_id = intval($_GET['project_id']);
                    $teacher_id = intval($_GET['teacher_id']);
                    $student_id = intval($_GET['student_id']);
                    
                    $exists = checkProjectDetailExists($project_id, $teacher_id, $student_id);
                    
                    echo json_encode([
                        'success' => true,
                        'exists' => $exists
                    ]);
                } else {
                    echo json_encode(['error' => 'Project ID, Teacher ID and Student ID are required']);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Unknown action']);
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
    }
    exit;
}
?>
