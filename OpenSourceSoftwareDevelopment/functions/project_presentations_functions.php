<?php
require_once __DIR__ . '/db_connection.php';

// Hàm lấy tất cả báo cáo đề tài với phân trang
function getProjectPresentationsWithPagination($page, $limit, $sortField = 'id', $sortOrder = 'asc') {
    $conn = getDbConnection();
    
    // Validate sort field để tránh SQL injection
    $allowedSortFields = ['id', 'title', 'project_code', 'project_name', 'time', 'score'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'id';
    }
    
    // Validate sort order
    $sortOrder = ($sortOrder === 'desc') ? 'DESC' : 'ASC';
    
    try {
        // Tính offset cho phân trang
        if ($limit === 'all') {
            $offset = 0;
            $limitClause = '';
        } else {
            $offset = ($page - 1) * $limit;
            $limitClause = "LIMIT $limit OFFSET $offset";
        }
        
        // Query để lấy danh sách báo cáo với thông tin đề tài
        $sql = "SELECT pp.*, p.project_code, p.project_name 
                FROM project_presentations pp
                INNER JOIN projects p ON pp.project_id = p.id
                ORDER BY $sortField $sortOrder
                $limitClause";
        
        $result = mysqli_query($conn, $sql);
        $presentations = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $presentations[] = $row;
            }
        }
        
        // Đếm tổng số bản ghi
        $countSql = "SELECT COUNT(*) as total FROM project_presentations pp
                     INNER JOIN projects p ON pp.project_id = p.id";
        $countResult = mysqli_query($conn, $countSql);
        $totalPresentations = mysqli_fetch_assoc($countResult)['total'];
        
        // Tính số trang
        $totalPages = ($limit === 'all') ? 1 : ceil($totalPresentations / $limit);
        
        mysqli_close($conn);
        
        return [
            'presentations' => $presentations,
            'totalPresentations' => $totalPresentations,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];
        
    } catch (Exception $e) {
        mysqli_close($conn);
        throw $e;
    }
}

// Hàm tìm kiếm báo cáo đề tài với phân trang
function searchProjectPresentationsWithPagination($page, $limit, $sortField, $sortOrder, $searchProjectName, $searchProjectCode, $searchTitle, $searchTimeStart, $searchTimeEnd, $searchScoreMin = '', $searchScoreMax = '') {
    $conn = getDbConnection();
    
    // Validate sort field
    $allowedSortFields = ['id', 'title', 'project_code', 'project_name', 'time', 'score'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'id';
    }
    
    // Validate sort order
    $sortOrder = ($sortOrder === 'desc') ? 'DESC' : 'ASC';
    
    try {
        // Xây dựng điều kiện WHERE
        $whereConditions = [];
        $params = [];
        $types = '';
        
        if (!empty($searchProjectName)) {
            $whereConditions[] = "p.project_name LIKE ?";
            $params[] = '%' . $searchProjectName . '%';
            $types .= 's';
        }
        
        if (!empty($searchProjectCode)) {
            $whereConditions[] = "p.project_code LIKE ?";
            $params[] = '%' . $searchProjectCode . '%';
            $types .= 's';
        }
        
        if (!empty($searchTitle)) {
            $whereConditions[] = "pp.title LIKE ?";
            $params[] = '%' . $searchTitle . '%';
            $types .= 's';
        }
        
        if (!empty($searchTimeStart) && !empty($searchTimeEnd)) {
            $whereConditions[] = "DATE(pp.time) BETWEEN ? AND ?";
            $params[] = $searchTimeStart;
            $params[] = $searchTimeEnd;
            $types .= 'ss';
        } elseif (!empty($searchTimeStart)) {
            $whereConditions[] = "DATE(pp.time) >= ?";
            $params[] = $searchTimeStart;
            $types .= 's';
        } elseif (!empty($searchTimeEnd)) {
            $whereConditions[] = "DATE(pp.time) <= ?";
            $params[] = $searchTimeEnd;
            $types .= 's';
        }
        
        if ($searchScoreMin !== '' && $searchScoreMin !== null && $searchScoreMax !== '' && $searchScoreMax !== null) {
            $whereConditions[] = "pp.score >= ? AND pp.score <= ?";
            $params[] = $searchScoreMin;
            $params[] = $searchScoreMax;
            $types .= 'dd';
        } elseif ($searchScoreMin !== '' && $searchScoreMin !== null) {
            $whereConditions[] = "pp.score >= ?";
            $params[] = $searchScoreMin;
            $types .= 'd';
        } elseif ($searchScoreMax !== '' && $searchScoreMax !== null) {
            $whereConditions[] = "pp.score <= ?";
            $params[] = $searchScoreMax;
            $types .= 'd';
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Tính offset cho phân trang
        if ($limit === 'all') {
            $offset = 0;
            $limitClause = '';
        } else {
            $offset = ($page - 1) * $limit;
            $limitClause = "LIMIT $limit OFFSET $offset";
        }
        
        // Query chính
        $sql = "SELECT pp.*, p.project_code, p.project_name 
                FROM project_presentations pp
                INNER JOIN projects p ON pp.project_id = p.id
                $whereClause
                ORDER BY $sortField $sortOrder
                $limitClause";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $presentations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $presentations[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        
        // Query đếm tổng số
        $countSql = "SELECT COUNT(*) as total 
                     FROM project_presentations pp
                     INNER JOIN projects p ON pp.project_id = p.id
                     $whereClause";
        
        $countStmt = mysqli_prepare($conn, $countSql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
        }
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
        $totalPresentations = mysqli_fetch_assoc($countResult)['total'];
        
        mysqli_stmt_close($countStmt);
        
        // Tính số trang
        $totalPages = ($limit === 'all') ? 1 : ceil($totalPresentations / $limit);
        
        mysqli_close($conn);
        
        return [
            'presentations' => $presentations,
            'totalPresentations' => $totalPresentations,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];
        
    } catch (Exception $e) {
        mysqli_close($conn);
        throw $e;
    }
}

// Hàm lấy báo cáo đề tài theo ID
function getProjectPresentationById($id) {
    $conn = getDbConnection();
    
    try {
        $sql = "SELECT pp.*, p.project_code, p.project_name 
                FROM project_presentations pp
                INNER JOIN projects p ON pp.project_id = p.id
                WHERE pp.id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $presentation = mysqli_fetch_assoc($result);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        return $presentation;
        
    } catch (Exception $e) {
        mysqli_close($conn);
        throw $e;
    }
}

// Hàm tạo báo cáo đề tài mới
function createProjectPresentation($title, $projectId, $time) {
    $conn = getDbConnection();
    
    try {
        $sql = "INSERT INTO project_presentations (title, project_id, time) VALUES (?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sis", $title, $projectId, $time);
        
        if (mysqli_stmt_execute($stmt)) {
            $insertId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return $insertId;
        } else {
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return false;
        }
        
    } catch (Exception $e) {
        mysqli_close($conn);
        throw $e;
    }
}

// Hàm cập nhật báo cáo đề tài
function updateProjectPresentation($id, $title, $projectId, $time) {
    $conn = getDbConnection();
    
    try {
        $sql = "UPDATE project_presentations SET title = ?, project_id = ?, time = ? WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sisi", $title, $projectId, $time, $id);
        
        $result = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        return $result;
        
    } catch (Exception $e) {
        mysqli_close($conn);
        throw $e;
    }
}

// Hàm cập nhật báo cáo đề tài không thay đổi thời gian
function updateProjectPresentationWithoutTime($id, $title, $projectId) {
    $conn = getDbConnection();
    
    try {
        $sql = "UPDATE project_presentations SET title = ?, project_id = ? WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sii", $title, $projectId, $id);
        
        $result = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        return $result;
        
    } catch (Exception $e) {
        mysqli_close($conn);
        throw $e;
    }
}

// Hàm xóa báo cáo đề tài
function deleteProjectPresentation($id) {
    $conn = getDbConnection();
    
    try {
        // Xóa báo cáo đề tài (các bản ghi liên quan sẽ tự động xóa do CASCADE)
        $sql = "DELETE FROM project_presentations WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        $result = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        return $result;
        
    } catch (Exception $e) {
        mysqli_close($conn);
        throw $e;
    }
}

// Hàm lấy lịch sử báo cáo của một đề tài
function getProjectPresentationHistory($projectId) {
    $conn = getDbConnection();
    
    try {
        $sql = "SELECT * FROM project_presentations 
                WHERE project_id = ? 
                ORDER BY time DESC";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $projectId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $history = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $history[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        
        return $history;
        
    } catch (Exception $e) {
        mysqli_close($conn);
        throw $e;
    }
}

// Hàm lấy tất cả đề tài
function getAllProjects() {
    $conn = getDbConnection();
    
    try {
        $sql = "SELECT * FROM projects ORDER BY project_code ASC";
        $result = mysqli_query($conn, $sql);
        
        $projects = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $projects[] = $row;
            }
        }
        
        mysqli_close($conn);
        return $projects;
        
    } catch (Exception $e) {
        mysqli_close($conn);
        throw $e;
    }
}
?>