<?php
require_once __DIR__ . '/../functions/project_extensions_functions.php';

// Kiểm tra action được truyền qua URL hoặc POST
$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
}

switch ($action) {
    case 'create':
        handleCreateProjectExtension();
        break;
    case 'edit':
        handleEditProjectExtension();
        break;
    case 'delete':
        handleDeleteProjectExtension();
        break;
    case 'sort':
        handleSortProjectExtensions();
        break;
    case 'get_finish_date':
        handleGetProjectFinishDateAjax();
        break;
    case 'get_extension_history':
        handleGetExtensionHistoryAjax();
        break;
}

/**
 * Lấy tất cả danh sách gia hạn đề tài
 */
function handleGetAllProjectExtensions() {
    return getAllProjectExtensions();
}

/**
 * Lấy gia hạn đề tài theo ID
 */
function handleGetProjectExtensionById($id) {
    return getProjectExtensionById($id);
}

/**
 * Lấy tất cả đề tài để chọn
 */
function handleGetAllProjects() {
    return getAllProjectsForSelect();
}

/**
 * Lấy ngày kết thúc hiện tại của đề tài theo ID
 */
function handleGetProjectFinishDate($project_id) {
    return getProjectFinishDate($project_id);
}

/**
 * Lấy danh sách gia hạn đề tài có phân trang
 */
function handleGetProjectExtensionsWithPagination($page, $limit, $sortField, $sortOrder) {
    return getProjectExtensionsWithPagination($page, $limit, $sortField, $sortOrder);
}

/**
 * Tìm kiếm gia hạn đề tài có phân trang
 */
function handleSearchProjectExtensionsWithPagination($page, $limit, $sortField, $sortOrder, $searchProjectName, $searchProjectCode, $searchDateFinishBefore, $searchDateFinishAfter) {
    return searchProjectExtensionsWithPagination($page, $limit, $sortField, $sortOrder, $searchProjectName, $searchProjectCode, $searchDateFinishBefore, $searchDateFinishAfter);
}

/**
 * Xử lý lấy ngày kết thúc của đề tài qua AJAX
 */
function handleGetProjectFinishDateAjax() {
    if (!isset($_GET['project_id']) || empty($_GET['project_id'])) {
        echo json_encode(['error' => 'Thiếu ID đề tài']);
        exit;
    }
    
    $project_id = (int)$_GET['project_id'];
    $date_finish = getProjectFinishDate($project_id);
    
    echo json_encode(['date_finish' => $date_finish]);
    exit;
}

/**
 * Xử lý lấy lịch sử gia hạn của đề tài qua AJAX
 */
function handleGetExtensionHistoryAjax() {
    if (!isset($_GET['project_id']) || empty($_GET['project_id'])) {
        echo json_encode(['error' => 'Thiếu ID đề tài']);
        exit;
    }
    
    $project_id = (int)$_GET['project_id'];
    $extensionHistory = getProjectExtensionHistory($project_id);
    
    echo json_encode($extensionHistory);
    exit;
}

/**
 * Xử lý sắp xếp gia hạn đề tài
 */
function handleSortProjectExtensions() {
    $field = isset($_GET['field']) ? $_GET['field'] : 'id';
    $order = isset($_GET['order']) ? $_GET['order'] : 'asc';
    
    $sortedExtensions = getSortedProjectExtensions($field, $order);
    
    // Lưu thông tin sắp xếp vào session để duy trì trạng thái
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['sort_field'] = $field;
    $_SESSION['sort_order'] = $order;
    
    // Chuyển hướng về trang danh sách với thông tin sắp xếp
    header("Location: ../views/project_extensions.php?sorted=true");
    exit();
}

/**
 * Xử lý tạo gia hạn đề tài mới
 */
function handleCreateProjectExtension() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/project_extensions.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['project_id']) || !isset($_POST['datefinish_before']) || !isset($_POST['datefinish_after'])) {
        header("Location: ../views/project_extensions/create_project_extension.php?error=Thiếu thông tin cần thiết");
        exit();
    }
    
    $project_id = (int)$_POST['project_id'];
    $datefinish_before = trim($_POST['datefinish_before']);
    $datefinish_after = trim($_POST['datefinish_after']);
    
    // Validate dữ liệu
    if ($project_id <= 0 || empty($datefinish_before) || empty($datefinish_after)) {
        header("Location: ../views/project_extensions/create_project_extension.php?error=Vui lòng điền đầy đủ thông tin");
        exit();
    }
    
    // Kiểm tra định dạng ngày tháng
    $date_pattern = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($date_pattern, $datefinish_before) || !preg_match($date_pattern, $datefinish_after)) {
        header("Location: ../views/project_extensions/create_project_extension.php?error=Định dạng ngày tháng không hợp lệ (YYYY-MM-DD)");
        exit();
    }
    
    // Kiểm tra ngày kết thúc sau phải lớn hơn ngày kết thúc trước
    if (strtotime($datefinish_after) <= strtotime($datefinish_before)) {
        header("Location: ../views/project_extensions/create_project_extension.php?error=Ngày kết thúc sau khi gia hạn phải lớn hơn ngày kết thúc hiện tại");
        exit();
    }
    
    // Xử lý file upload
    $uuid = null;
    $file_content = null;
    
    if (isset($_FILES['extension_file']) && $_FILES['extension_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['extension_file'];
        
        // Kiểm tra loại file (chỉ cho phép PDF)
        $file_type = $file['type'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file_type !== 'application/pdf' || $file_extension !== 'pdf') {
            header("Location: ../views/project_extensions/create_project_extension.php?error=Chỉ chấp nhận file PDF");
            exit();
        }
        
        // Kiểm tra kích thước file (tối đa 16MB)
        if ($file['size'] > 16 * 1024 * 1024) {
            header("Location: ../views/project_extensions/create_project_extension.php?error=Kích thước file không được vượt quá 16MB");
            exit();
        }
        
        // Tạo UUID cho file
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        // Đọc nội dung file
        $file_content = file_get_contents($file['tmp_name']);
        
        if ($file_content === false) {
            header("Location: ../views/project_extensions/create_project_extension.php?error=Không thể đọc file được tải lên");
            exit();
        }
    } else {
        header("Location: ../views/project_extensions/create_project_extension.php?error=Vui lòng tải lên file đơn xin gia hạn");
        exit();
    }
    
    // Gọi hàm thêm gia hạn đề tài
    $result = addProjectExtension($project_id, $datefinish_before, $datefinish_after, $uuid, $file_content);
    
    if ($result['success']) {
        header("Location: ../views/project_extensions.php?success=" . urlencode($result['message']));
    } else {
        header("Location: ../views/project_extensions/create_project_extension.php?error=" . urlencode($result['message']));
    }
    exit();
}

/**
 * Xử lý chỉnh sửa gia hạn đề tài
 */
function handleEditProjectExtension() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/project_extensions.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['id']) || !isset($_POST['project_id']) || !isset($_POST['datefinish_before']) || !isset($_POST['datefinish_after'])) {
        header("Location: ../views/project_extensions.php?error=Thiếu thông tin cần thiết");
        exit();
    }
    
    $id = (int)$_POST['id'];
    $project_id = (int)$_POST['project_id'];
    $datefinish_before = trim($_POST['datefinish_before']);
    $datefinish_after = trim($_POST['datefinish_after']);
    
    // Validate dữ liệu
    if ($id <= 0 || $project_id <= 0 || empty($datefinish_before) || empty($datefinish_after)) {
        header("Location: ../views/project_extensions/edit_project_extension.php?id=" . $id . "&error=Vui lòng điền đầy đủ thông tin");
        exit();
    }
    
    // Kiểm tra định dạng ngày tháng
    $date_pattern = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($date_pattern, $datefinish_before) || !preg_match($date_pattern, $datefinish_after)) {
        header("Location: ../views/project_extensions/edit_project_extension.php?id=" . $id . "&error=Định dạng ngày tháng không hợp lệ (YYYY-MM-DD)");
        exit();
    }
    
    // Kiểm tra ngày kết thúc sau phải lớn hơn ngày kết thúc trước
    if (strtotime($datefinish_after) <= strtotime($datefinish_before)) {
        header("Location: ../views/project_extensions/edit_project_extension.php?id=" . $id . "&error=Ngày kết thúc sau khi gia hạn phải lớn hơn ngày kết thúc hiện tại");
        exit();
    }
    
    // Xử lý file upload (tùy chọn khi edit)
    $uuid = null;
    $file_content = null;
    
    if (isset($_FILES['extension_file']) && $_FILES['extension_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['extension_file'];
        
        // Kiểm tra loại file (chỉ cho phép PDF)
        $file_type = $file['type'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file_type !== 'application/pdf' || $file_extension !== 'pdf') {
            header("Location: ../views/project_extensions/edit_project_extension.php?id=" . $id . "&error=Chỉ chấp nhận file PDF");
            exit();
        }
        
        // Kiểm tra kích thước file (tối đa 16MB)
        if ($file['size'] > 16 * 1024 * 1024) {
            header("Location: ../views/project_extensions/edit_project_extension.php?id=" . $id . "&error=Kích thước file không được vượt quá 16MB");
            exit();
        }
        
        // Tạo UUID mới cho file
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        // Đọc nội dung file
        $file_content = file_get_contents($file['tmp_name']);
        
        if ($file_content === false) {
            header("Location: ../views/project_extensions/edit_project_extension.php?id=" . $id . "&error=Không thể đọc file được tải lên");
            exit();
        }
    }
    
    // Gọi function để cập nhật gia hạn đề tài
    $result = updateProjectExtension($id, $project_id, $datefinish_before, $datefinish_after, $uuid, $file_content);
    
    if ($result) {
        header("Location: ../views/project_extensions.php?success=Cập nhật gia hạn đề tài thành công");
    } else {
        header("Location: ../views/project_extensions/edit_project_extension.php?id=" . $id . "&error=Cập nhật gia hạn đề tài thất bại");
    }
    exit();
}

/**
 * Xử lý xóa gia hạn đề tài
 */
function handleDeleteProjectExtension() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header("Location: ../views/project_extensions.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: ../views/project_extensions.php?error=Không tìm thấy ID gia hạn đề tài");
        exit();
    }
    
    $id = $_GET['id'];
    
    // Validate ID là số
    if (!is_numeric($id)) {
        header("Location: ../views/project_extensions.php?error=ID gia hạn đề tài không hợp lệ");
        exit();
    }
    
    // Gọi function để xóa gia hạn đề tài
    $result = deleteProjectExtension($id);
    
    if ($result) {
        header("Location: ../views/project_extensions.php?success=Xóa gia hạn đề tài thành công");
    } else {
        header("Location: ../views/project_extensions.php?error=Xóa gia hạn đề tài thất bại");
    }
    exit();
}
?>
