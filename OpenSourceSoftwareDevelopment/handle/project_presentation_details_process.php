<?php
require_once __DIR__ . '/../functions/project_presentation_details_functions.php';

// Kiểm tra action được truyền qua URL hoặc POST
$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
}

switch ($action) {
    case 'create':
        handleCreateProjectPresentationDetail();
        break;
    case 'edit':
        handleEditProjectPresentationDetail();
        break;
    case 'delete':
        handleDeleteProjectPresentationDetail();
        break;
    case 'sort':
        handleSortProjectPresentationDetails();
        break;
}

/**
 * Lấy tất cả danh sách chi tiết báo cáo đề tài
 */
function handleGetAllProjectPresentationDetails() {
    return getAllProjectPresentationDetails();
}

/**
 * Lấy chi tiết báo cáo đề tài theo ID
 */
function handleGetProjectPresentationDetailById($id) {
    return getProjectPresentationDetailById($id);
}

/**
 * Lấy tất cả báo cáo đề tài để chọn
 */
function handleGetAllProjectPresentations() {
    return getAllProjectPresentations();
}

/**
 * Lấy tất cả giảng viên để chọn
 */
function handleGetAllTeachers() {
    return getAllTeachers();
}

/**
 * Lấy danh sách chi tiết báo cáo đề tài với phân trang
 */
function handleGetProjectPresentationDetailsWithPagination($page, $limit, $sortField, $sortOrder) {
    return getProjectPresentationDetailsWithPagination($page, $limit, $sortField, $sortOrder);
}

/**
 * Tìm kiếm chi tiết báo cáo đề tài với phân trang
 */
function handleSearchProjectPresentationDetailsWithPagination($page, $limit, $sortField, $sortOrder, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax) {
    return searchProjectPresentationDetailsWithPagination($page, $limit, $sortField, $sortOrder, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax);
}

/**
 * Xử lý tạo chi tiết báo cáo đề tài mới
 */
function handleCreateProjectPresentationDetail() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $project_presentation_id = $_POST['project_presentation_id'];
        $teacher_id = $_POST['teacher_id'];
        $score = $_POST['score'];
        
        // Validate dữ liệu
        if (empty($project_presentation_id) || empty($teacher_id) || empty($score)) {
            header("Location: ../views/project_presentation_details/create_project_presentation_details.php?error=Vui lòng điền đầy đủ thông tin");
            exit;
        }
        
        if ($score < 0 || $score > 10) {
            header("Location: ../views/project_presentation_details/create_project_presentation_details.php?error=Điểm phải từ 0 đến 10");
            exit;
        }
        
        // Kiểm tra xem đã có chi tiết báo cáo của giảng viên này cho báo cáo này chưa
        if (checkProjectPresentationDetailExists($project_presentation_id, $teacher_id)) {
            header("Location: ../views/project_presentation_details/create_project_presentation_details.php?error=Giảng viên này đã chấm điểm cho báo cáo này rồi");
            exit;
        }
        
        if (createProjectPresentationDetail($project_presentation_id, $teacher_id, $score)) {
            // Cập nhật điểm trung bình cho báo cáo đề tài
            updateProjectPresentationAverageScore($project_presentation_id);
            
            header("Location: ../views/project_presentation_details.php?success=Thêm chi tiết báo cáo đề tài thành công");
        } else {
            header("Location: ../views/project_presentation_details/create_project_presentation_details.php?error=Có lỗi xảy ra khi thêm chi tiết báo cáo đề tài");
        }
        exit;
    }
}

/**
 * Xử lý chỉnh sửa chi tiết báo cáo đề tài
 */
function handleEditProjectPresentationDetail() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];
        $project_presentation_id = $_POST['project_presentation_id'];
        $teacher_id = $_POST['teacher_id'];
        $score = $_POST['score'];
        
        // Validate dữ liệu
        if (empty($id) || empty($project_presentation_id) || empty($teacher_id) || empty($score)) {
            header("Location: ../views/project_presentation_details/edit_project_presentation_details.php?id=$id&error=Vui lòng điền đầy đủ thông tin");
            exit;
        }
        
        if ($score < 0 || $score > 10) {
            header("Location: ../views/project_presentation_details/edit_project_presentation_details.php?id=$id&error=Điểm phải từ 0 đến 10");
            exit;
        }
        
        // Kiểm tra xem đã có chi tiết báo cáo khác của giảng viên này cho báo cáo này chưa (ngoại trừ bản ghi hiện tại)
        if (checkProjectPresentationDetailExistsExclude($project_presentation_id, $teacher_id, $id)) {
            header("Location: ../views/project_presentation_details/edit_project_presentation_details.php?id=$id&error=Giảng viên này đã chấm điểm cho báo cáo này rồi");
            exit;
        }
        
        if (updateProjectPresentationDetail($id, $project_presentation_id, $teacher_id, $score)) {
            // Cập nhật điểm trung bình cho báo cáo đề tài
            updateProjectPresentationAverageScore($project_presentation_id);
            
            header("Location: ../views/project_presentation_details.php?success=Cập nhật chi tiết báo cáo đề tài thành công");
        } else {
            header("Location: ../views/project_presentation_details/edit_project_presentation_details.php?id=$id&error=Có lỗi xảy ra khi cập nhật chi tiết báo cáo đề tài");
        }
        exit;
    }
}

/**
 * Xử lý xóa chi tiết báo cáo đề tài
 */
function handleDeleteProjectPresentationDetail() {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        
        // Lấy thông tin chi tiết báo cáo trước khi xóa để cập nhật điểm trung bình
        $detail = getProjectPresentationDetailById($id);
        $project_presentation_id = $detail ? $detail['project_presentation_id'] : null;
        
        if (deleteProjectPresentationDetail($id)) {
            // Cập nhật điểm trung bình cho báo cáo đề tài sau khi xóa
            if ($project_presentation_id) {
                updateProjectPresentationAverageScore($project_presentation_id);
            }
            
            header("Location: ../views/project_presentation_details.php?success=Xóa chi tiết báo cáo đề tài thành công");
        } else {
            header("Location: ../views/project_presentation_details.php?error=Có lỗi xảy ra khi xóa chi tiết báo cáo đề tài");
        }
        exit;
    }
}

/**
 * Xử lý sắp xếp danh sách chi tiết báo cáo đề tài
 */
function handleSortProjectPresentationDetails() {
    // Logic sắp xếp sẽ được thực hiện trong trang danh sách
    return true;
}
?>