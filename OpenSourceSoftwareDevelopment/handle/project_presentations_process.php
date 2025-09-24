<?php
require_once __DIR__ . '/../functions/project_presentations_functions.php';

// Hàm wrapper để lấy danh sách báo cáo với phân trang
function handleGetProjectPresentationsWithPagination($page, $limit, $sortField = 'id', $sortOrder = 'asc') {
    try {
        return getProjectPresentationsWithPagination($page, $limit, $sortField, $sortOrder);
    } catch (Exception $e) {
        error_log("Error in handleGetProjectPresentationsWithPagination: " . $e->getMessage());
        return [
            'presentations' => [],
            'totalPresentations' => 0,
            'totalPages' => 0,
            'currentPage' => $page
        ];
    }
}

// Hàm wrapper để tìm kiếm báo cáo với phân trang
function handleSearchProjectPresentationsWithPagination($page, $limit, $sortField, $sortOrder, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin = '', $searchScoreMax = '') {
    try {
        return searchProjectPresentationsWithPagination($page, $limit, $sortField, $sortOrder, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin, $searchScoreMax);
    } catch (Exception $e) {
        error_log("Error in handleSearchProjectPresentationsWithPagination: " . $e->getMessage());
        return [
            'presentations' => [],
            'totalPresentations' => 0,
            'totalPages' => 0,
            'currentPage' => $page
        ];
    }
}

// Hàm wrapper để lấy báo cáo theo ID
function handleGetProjectPresentationById($id) {
    try {
        return getProjectPresentationById($id);
    } catch (Exception $e) {
        error_log("Error in handleGetProjectPresentationById: " . $e->getMessage());
        return null;
    }
}

// Hàm wrapper để lấy tất cả đề tài
function handleGetAllProjects() {
    try {
        return getAllProjects();
    } catch (Exception $e) {
        error_log("Error in handleGetAllProjects: " . $e->getMessage());
        return [];
    }
}

// Xử lý các request POST và GET
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            handleCreateProjectPresentation();
            break;
            
        case 'edit':
            handleEditProjectPresentation();
            break;
            
        default:
            header("Location: ../views/project_presentations.php?error=Hành động không hợp lệ");
            exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'delete':
            handleDeleteProjectPresentation();
            break;
            
        case 'get_presentation_history':
            handleGetPresentationHistory();
            break;
            
        default:
            // Không làm gì, để các trang khác xử lý
            break;
    }
}

// Hàm xử lý tạo báo cáo mới
function handleCreateProjectPresentation() {
    try {
        // Validate input
        $title = trim($_POST['title'] ?? '');
        $projectId = $_POST['project_id'] ?? '';
        
        if (empty($title)) {
            header("Location: ../views/project_presentations/create_project_presentations.php?error=Tiêu đề báo cáo không được để trống");
            exit;
        }
        
        if (empty($projectId) || !is_numeric($projectId)) {
            header("Location: ../views/project_presentations/create_project_presentations.php?error=Vui lòng chọn đề tài");
            exit;
        }
        
        // Sử dụng thời gian hiện tại
        $mysqlTime = date('Y-m-d H:i:s');
        
        $result = createProjectPresentation($title, $projectId, $mysqlTime);
        
        if ($result) {
            header("Location: ../views/project_presentations.php?success=Thêm báo cáo đề tài thành công");
        } else {
            header("Location: ../views/project_presentations/create_project_presentations.php?error=Có lỗi xảy ra khi thêm báo cáo đề tài");
        }
        
    } catch (Exception $e) {
        error_log("Error in handleCreateProjectPresentation: " . $e->getMessage());
        header("Location: ../views/project_presentations/create_project_presentations.php?error=Có lỗi xảy ra khi thêm báo cáo đề tài");
    }
    exit;
}

// Hàm xử lý chỉnh sửa báo cáo
function handleEditProjectPresentation() {
    try {
        // Validate input
        $id = $_POST['id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $projectId = $_POST['project_id'] ?? '';
        
        if (empty($id) || !is_numeric($id)) {
            header("Location: ../views/project_presentations.php?error=ID báo cáo không hợp lệ");
            exit;
        }
        
        if (empty($title)) {
            header("Location: ../views/project_presentations/edit_project_presentations.php?id=$id&error=Tiêu đề báo cáo không được để trống");
            exit;
        }
        
        if (empty($projectId) || !is_numeric($projectId)) {
            header("Location: ../views/project_presentations/edit_project_presentations.php?id=$id&error=Vui lòng chọn đề tài");
            exit;
        }
        
        // Không cập nhật thời gian, giữ nguyên thời gian hiện tại trong database
        $result = updateProjectPresentationWithoutTime($id, $title, $projectId);
        
        if ($result) {
            header("Location: ../views/project_presentations.php?success=Cập nhật báo cáo đề tài thành công");
        } else {
            header("Location: ../views/project_presentations/edit_project_presentations.php?id=$id&error=Có lỗi xảy ra khi cập nhật báo cáo đề tài");
        }
        
    } catch (Exception $e) {
        error_log("Error in handleEditProjectPresentation: " . $e->getMessage());
        $id = $_POST['id'] ?? '';
        header("Location: ../views/project_presentations/edit_project_presentations.php?id=$id&error=Có lỗi xảy ra khi cập nhật báo cáo đề tài");
    }
    exit;
}

// Hàm xử lý xóa báo cáo
function handleDeleteProjectPresentation() {
    try {
        $id = $_GET['id'] ?? '';
        
        if (empty($id) || !is_numeric($id)) {
            header("Location: ../views/project_presentations.php?error=ID báo cáo không hợp lệ");
            exit;
        }
        
        $result = deleteProjectPresentation($id);
        
        if ($result) {
            header("Location: ../views/project_presentations.php?success=Xóa báo cáo đề tài thành công");
        } else {
            header("Location: ../views/project_presentations.php?error=Có lỗi xảy ra khi xóa báo cáo đề tài");
        }
        
    } catch (Exception $e) {
        error_log("Error in handleDeleteProjectPresentation: " . $e->getMessage());
        header("Location: ../views/project_presentations.php?error=Có lỗi xảy ra khi xóa báo cáo đề tài");
    }
    exit;
}

// Hàm xử lý lấy lịch sử báo cáo (AJAX)
function handleGetPresentationHistory() {
    try {
        $projectId = $_GET['project_id'] ?? '';
        
        if (empty($projectId) || !is_numeric($projectId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Project ID không hợp lệ']);
            exit;
        }
        
        $history = getProjectPresentationHistory($projectId);
        
        header('Content-Type: application/json');
        echo json_encode($history);
        
    } catch (Exception $e) {
        error_log("Error in handleGetPresentationHistory: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Có lỗi xảy ra khi lấy lịch sử báo cáo']);
    }
    exit;
}
?>