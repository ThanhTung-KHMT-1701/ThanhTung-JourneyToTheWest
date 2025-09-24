<?php
require_once 'db_connection.php';

/**
 * Lấy tất cả danh sách project_presentation_details từ database
 * @return array Danh sách project_presentation_details với thông tin báo cáo và giảng viên
 */
function getAllProjectPresentationDetails() {
    $conn = getDbConnection();
    
    // Truy vấn lấy tất cả project_presentation_details với join để lấy thông tin từ các bảng liên quan
    $sql = "SELECT ppd.id, ppd.project_presentation_id, ppd.teacher_id, ppd.score,
                   pp.title as presentation_title, p.project_code, 
                   t.teacher_name, t.teacher_code
            FROM project_presentation_details ppd
            JOIN project_presentations pp ON ppd.project_presentation_id = pp.id
            JOIN projects p ON pp.project_id = p.id
            JOIN teachers t ON ppd.teacher_id = t.id
            ORDER BY ppd.id";
    
    $result = mysqli_query($conn, $sql);
    
    $presentation_details = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { 
            $presentation_details[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $presentation_details;
}

/**
 * Lấy danh sách project_presentation_details với phân trang và sắp xếp
 * @param int $page Số trang hiện tại
 * @param mixed $limit Số bản ghi trên trang hoặc 'all'
 * @param string $sortField Trường sắp xếp
 * @param string $sortOrder Thứ tự sắp xếp (asc/desc)
 * @return array Thông tin phân trang và dữ liệu
 */
function getProjectPresentationDetailsWithPagination($page, $limit, $sortField, $sortOrder) {
    $conn = getDbConnection();
    
    // Tổng số bản ghi
    $countSql = "SELECT COUNT(*) as total 
                 FROM project_presentation_details ppd
                 JOIN project_presentations pp ON ppd.project_presentation_id = pp.id
                 JOIN projects p ON pp.project_id = p.id
                 JOIN teachers t ON ppd.teacher_id = t.id";
    
    $countResult = mysqli_query($conn, $countSql);
    $totalRecords = mysqli_fetch_assoc($countResult)['total'];
    
    // Xác định OFFSET và LIMIT
    if ($limit === 'all') {
        $offset = 0;
        $limitClause = "";
        $totalPages = 1;
        $currentPage = 1;
    } else {
        $offset = ($page - 1) * $limit;
        $limitClause = "LIMIT $limit OFFSET $offset";
        $totalPages = ceil($totalRecords / $limit);
        $currentPage = $page;
    }
    
    // Xác định trường sắp xếp hợp lệ
    $validSortFields = ['id', 'project_code', 'presentation_title', 'teacher_name', 'score'];
    if (!in_array($sortField, $validSortFields)) {
        $sortField = 'id';
    }
    
    // Xác định thứ tự sắp xếp hợp lệ
    $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
    
    // Ánh xạ trường sắp xếp đến tên cột thực tế
    $sortFieldMap = [
        'id' => 'ppd.id',
        'project_code' => 'p.project_code',
        'presentation_title' => 'pp.title',
        'teacher_name' => 't.teacher_name',
        'score' => 'ppd.score'
    ];
    
    $actualSortField = $sortFieldMap[$sortField];
    
    // Truy vấn chính với phân trang và sắp xếp
    $sql = "SELECT ppd.id, ppd.project_presentation_id, ppd.teacher_id, ppd.score,
                   pp.title as presentation_title, p.project_code, 
                   t.teacher_name, t.teacher_code
            FROM project_presentation_details ppd
            JOIN project_presentations pp ON ppd.project_presentation_id = pp.id
            JOIN projects p ON pp.project_id = p.id
            JOIN teachers t ON ppd.teacher_id = t.id
            ORDER BY $actualSortField $sortOrder
            $limitClause";
    
    $result = mysqli_query($conn, $sql);
    
    $presentation_details = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $presentation_details[] = $row;
        }
    }
    
    mysqli_close($conn);
    
    return [
        'presentation_details' => $presentation_details,
        'totalPresentationDetails' => $totalRecords,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage
    ];
}

/**
 * Tìm kiếm project_presentation_details với phân trang và sắp xếp
 */
function searchProjectPresentationDetailsWithPagination($page, $limit, $sortField, $sortOrder, $searchPresentationTitle, $searchProjectCode, $searchTeacherName, $searchScoreMin, $searchScoreMax) {
    $conn = getDbConnection();
    
    // Xây dựng điều kiện WHERE
    $whereConditions = [];
    $params = [];
    $types = "";
    
    if (!empty($searchPresentationTitle)) {
        $whereConditions[] = "pp.title LIKE ?";
        $params[] = "%$searchPresentationTitle%";
        $types .= "s";
    }
    
    if (!empty($searchProjectCode)) {
        $whereConditions[] = "p.project_code LIKE ?";
        $params[] = "%$searchProjectCode%";
        $types .= "s";
    }
    
    if (!empty($searchTeacherName)) {
        $whereConditions[] = "t.teacher_name LIKE ?";
        $params[] = "%$searchTeacherName%";
        $types .= "s";
    }
    
    if (!empty($searchScoreMin) && is_numeric($searchScoreMin)) {
        $whereConditions[] = "ppd.score >= ?";
        $params[] = (float)$searchScoreMin;
        $types .= "d";
    }
    
    if (!empty($searchScoreMax) && is_numeric($searchScoreMax)) {
        $whereConditions[] = "ppd.score <= ?";
        $params[] = (float)$searchScoreMax;
        $types .= "d";
    }
    
    $whereClause = empty($whereConditions) ? "" : "WHERE " . implode(" AND ", $whereConditions);
    
    // Tổng số bản ghi với điều kiện tìm kiếm
    $countSql = "SELECT COUNT(*) as total 
                 FROM project_presentation_details ppd
                 JOIN project_presentations pp ON ppd.project_presentation_id = pp.id
                 JOIN projects p ON pp.project_id = p.id
                 JOIN teachers t ON ppd.teacher_id = t.id
                 $whereClause";
    
    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $countSql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $countResult = mysqli_stmt_get_result($stmt);
            $totalRecords = mysqli_fetch_assoc($countResult)['total'];
            mysqli_stmt_close($stmt);
        } else {
            mysqli_close($conn);
            return [];
        }
    } else {
        $countResult = mysqli_query($conn, $countSql);
        $totalRecords = mysqli_fetch_assoc($countResult)['total'];
    }
    
    // Xác định OFFSET và LIMIT
    if ($limit === 'all') {
        $offset = 0;
        $limitClause = "";
        $totalPages = 1;
        $currentPage = 1;
    } else {
        $offset = ($page - 1) * $limit;
        $limitClause = "LIMIT $limit OFFSET $offset";
        $totalPages = ceil($totalRecords / $limit);
        $currentPage = $page;
    }
    
    // Xác định trường sắp xếp hợp lệ
    $validSortFields = ['id', 'project_code', 'presentation_title', 'teacher_name', 'score'];
    if (!in_array($sortField, $validSortFields)) {
        $sortField = 'id';
    }
    
    // Xác định thứ tự sắp xếp hợp lệ
    $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
    
    // Ánh xạ trường sắp xếp đến tên cột thực tế
    $sortFieldMap = [
        'id' => 'ppd.id',
        'project_code' => 'p.project_code',
        'presentation_title' => 'pp.title',
        'teacher_name' => 't.teacher_name',
        'score' => 'ppd.score'
    ];
    
    $actualSortField = $sortFieldMap[$sortField];
    
    // Truy vấn chính với tìm kiếm, phân trang và sắp xếp
    $sql = "SELECT ppd.id, ppd.project_presentation_id, ppd.teacher_id, ppd.score,
                   pp.title as presentation_title, p.project_code, 
                   t.teacher_name, t.teacher_code
            FROM project_presentation_details ppd
            JOIN project_presentations pp ON ppd.project_presentation_id = pp.id
            JOIN projects p ON pp.project_id = p.id
            JOIN teachers t ON ppd.teacher_id = t.id
            $whereClause
            ORDER BY $actualSortField $sortOrder
            $limitClause";
    
    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $presentation_details = [];
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $presentation_details[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        } else {
            mysqli_close($conn);
            return [];
        }
    } else {
        $result = mysqli_query($conn, $sql);
        $presentation_details = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $presentation_details[] = $row;
            }
        }
    }
    
    mysqli_close($conn);
    
    return [
        'presentation_details' => $presentation_details,
        'totalPresentationDetails' => $totalRecords,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage
    ];
}

/**
 * Lấy chi tiết báo cáo đề tài theo ID
 * @param int $id ID của chi tiết báo cáo đề tài
 * @return array|false Thông tin chi tiết báo cáo đề tài hoặc false nếu không tìm thấy
 */
function getProjectPresentationDetailById($id) {
    $conn = getDbConnection();
    
    $sql = "SELECT ppd.id, ppd.project_presentation_id, ppd.teacher_id, ppd.score,
                   pp.title as presentation_title, p.project_code, 
                   t.teacher_name, t.teacher_code
            FROM project_presentation_details ppd
            JOIN project_presentations pp ON ppd.project_presentation_id = pp.id
            JOIN projects p ON pp.project_id = p.id
            JOIN teachers t ON ppd.teacher_id = t.id
            WHERE ppd.id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $detail = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return $detail;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Lấy tất cả báo cáo đề tài để chọn
 * @return array Danh sách báo cáo đề tài
 */
function getAllProjectPresentations() {
    $conn = getDbConnection();
    
    $sql = "SELECT pp.id, pp.title, p.project_code, p.project_name
            FROM project_presentations pp
            JOIN projects p ON pp.project_id = p.id
            ORDER BY pp.id";
    
    $result = mysqli_query($conn, $sql);
    
    $presentations = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $presentations[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $presentations;
}

/**
 * Lấy tất cả giảng viên để chọn
 * @return array Danh sách giảng viên
 */
function getAllTeachers() {
    $conn = getDbConnection();
    
    $sql = "SELECT id, teacher_code, teacher_name, teacher_email FROM teachers ORDER BY teacher_code";
    $result = mysqli_query($conn, $sql);
    
    $teachers = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $teachers[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $teachers;
}

/**
 * Kiểm tra xem chi tiết báo cáo đề tài đã tồn tại chưa
 * @param int $project_presentation_id ID báo cáo đề tài
 * @param int $teacher_id ID giảng viên
 * @return bool True nếu đã tồn tại, False nếu chưa
 */
function checkProjectPresentationDetailExists($project_presentation_id, $teacher_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT COUNT(*) as count FROM project_presentation_details 
            WHERE project_presentation_id = ? AND teacher_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $project_presentation_id, $teacher_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        return $count > 0;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Kiểm tra xem chi tiết báo cáo đề tài đã tồn tại chưa (ngoại trừ ID hiện tại)
 * @param int $project_presentation_id ID báo cáo đề tài
 * @param int $teacher_id ID giảng viên
 * @param int $exclude_id ID cần loại trừ
 * @return bool True nếu đã tồn tại, False nếu chưa
 */
function checkProjectPresentationDetailExistsExclude($project_presentation_id, $teacher_id, $exclude_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT COUNT(*) as count FROM project_presentation_details 
            WHERE project_presentation_id = ? AND teacher_id = ? AND id != ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iii", $project_presentation_id, $teacher_id, $exclude_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        return $count > 0;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Thêm chi tiết báo cáo đề tài mới
 * @param int $project_presentation_id ID báo cáo đề tài
 * @param int $teacher_id ID giảng viên
 * @param float $score Điểm
 * @return bool True nếu thành công, False nếu thất bại
 */
function createProjectPresentationDetail($project_presentation_id, $teacher_id, $score) {
    $conn = getDbConnection();
    
    $sql = "INSERT INTO project_presentation_details (project_presentation_id, teacher_id, score) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iid", $project_presentation_id, $teacher_id, $score);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        return $success;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Cập nhật chi tiết báo cáo đề tài
 * @param int $id ID chi tiết báo cáo đề tài
 * @param int $project_presentation_id ID báo cáo đề tài
 * @param int $teacher_id ID giảng viên
 * @param float $score Điểm
 * @return bool True nếu thành công, False nếu thất bại
 */
function updateProjectPresentationDetail($id, $project_presentation_id, $teacher_id, $score) {
    $conn = getDbConnection();
    
    $sql = "UPDATE project_presentation_details 
            SET project_presentation_id = ?, teacher_id = ?, score = ? 
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iidi", $project_presentation_id, $teacher_id, $score, $id);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        return $success;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Xóa chi tiết báo cáo đề tài
 * @param int $id ID chi tiết báo cáo đề tài
 * @return bool True nếu thành công, False nếu thất bại
 */
function deleteProjectPresentationDetail($id) {
    $conn = getDbConnection();
    
    $sql = "DELETE FROM project_presentation_details WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        return $success;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Cập nhật điểm trung bình cho báo cáo đề tài
 * @param int $project_presentation_id ID báo cáo đề tài
 * @return bool True nếu thành công, False nếu thất bại
 */
function updateProjectPresentationAverageScore($project_presentation_id) {
    $conn = getDbConnection();
    
    // Tính điểm trung bình từ tất cả chi tiết báo cáo của báo cáo này
    $avgSql = "SELECT AVG(score) as avg_score 
               FROM project_presentation_details 
               WHERE project_presentation_id = ?";
    
    $avgStmt = mysqli_prepare($conn, $avgSql);
    
    if ($avgStmt) {
        mysqli_stmt_bind_param($avgStmt, "i", $project_presentation_id);
        mysqli_stmt_execute($avgStmt);
        $avgResult = mysqli_stmt_get_result($avgStmt);
        
        if ($avgResult && $avgRow = mysqli_fetch_assoc($avgResult)) {
            $averageScore = $avgRow['avg_score'];
            
            // Nếu có điểm trung bình thì cập nhật, nếu không có chi tiết nào thì để điểm = 0
            if ($averageScore === null) {
                $averageScore = 0;
            }
            
            // Làm tròn đến 1 chữ số thập phân
            $averageScore = round($averageScore, 1);
            
            // Cập nhật điểm trung bình vào bảng project_presentations
            $updateSql = "UPDATE project_presentations SET score = ? WHERE id = ?";
            $updateStmt = mysqli_prepare($conn, $updateSql);
            
            if ($updateStmt) {
                mysqli_stmt_bind_param($updateStmt, "di", $averageScore, $project_presentation_id);
                $success = mysqli_stmt_execute($updateStmt);
                
                mysqli_stmt_close($updateStmt);
                mysqli_stmt_close($avgStmt);
                mysqli_close($conn);
                
                return $success;
            }
        }
        
        mysqli_stmt_close($avgStmt);
    }
    
    mysqli_close($conn);
    return false;
}
?>