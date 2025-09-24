<?php
require_once 'db_connection.php';

/**
 * Lấy tất cả danh sách gia hạn đề tài từ database
 * @return array Danh sách gia hạn đề tài
 */
function getAllProjectExtensions() {
    $conn = getDbConnection();
    
    // Truy vấn lấy tất cả project_extensions kèm theo tên đề tài từ bảng projects
    $sql = "SELECT pe.id, pe.project_id, p.project_code, p.project_name, pe.datefinish_before, pe.datefinish_after, pe.uuid
            FROM project_extensions pe
            INNER JOIN projects p ON pe.project_id = p.id
            ORDER BY pe.id";
    $result = mysqli_query($conn, $sql);
    
    $extensions = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $extensions[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $extensions;
}

/**
 * Lấy danh sách gia hạn đề tài đã được sắp xếp
 * @param string $field Trường dữ liệu dùng để sắp xếp
 * @param string $order Thứ tự sắp xếp (asc hoặc desc)
 * @return array Danh sách gia hạn đề tài đã được sắp xếp
 */
function getSortedProjectExtensions($field, $order) {
    $conn = getDbConnection();
    $allowedFields = ['id', 'project_code', 'project_name', 'datefinish_before', 'datefinish_after'];
    $allowedOrder = ['asc', 'desc'];
    
    // Validate input để tránh SQL injection
    if (!in_array($field, $allowedFields)) {
        $field = 'id';
    }
    
    if (!in_array(strtolower($order), $allowedOrder)) {
        $order = 'asc';
    }
    
    // Xử lý đặc biệt cho trường project_name và project_code vì chúng nằm trong bảng projects
    if ($field === 'project_name' || $field === 'project_code') {
        $sortField = 'p.' . $field;
    } else {
        $sortField = 'pe.' . $field;
    }
    
    // Truy vấn lấy tất cả project_extensions với sắp xếp
    $sql = "SELECT pe.id, pe.project_id, p.project_code, p.project_name, pe.datefinish_before, pe.datefinish_after 
            FROM project_extensions pe
            INNER JOIN projects p ON pe.project_id = p.id
            ORDER BY {$sortField} {$order}";
    $result = mysqli_query($conn, $sql);
    
    $extensions = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $extensions[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $extensions;
}

/**
 * Kiểm tra số lần gia hạn còn lại của đề tài
 * @param int $project_id ID của đề tài
 * @return int Số lần gia hạn còn lại
 */
function getProjectExtensionCount($project_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT number_extension FROM projects WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $project_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return (int)$row['number_extension'];
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return -1; // Trả về -1 nếu không tìm thấy đề tài
}

/**
 * Thêm gia hạn đề tài mới
 * @param int $project_id ID của đề tài
 * @param string $datefinish_before Ngày kết thúc trước khi gia hạn
 * @param string $datefinish_after Ngày kết thúc sau khi gia hạn
 * @return array Mảng chứa trạng thái và thông báo ['success' => bool, 'message' => string]
 */
function addProjectExtension($project_id, $datefinish_before, $datefinish_after, $uuid = null, $file_content = null) {
    $conn = getDbConnection();
    
    // Kiểm tra số lần gia hạn còn lại
    $extensionCount = getProjectExtensionCount($project_id);
    
    if ($extensionCount == -1) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Không tìm thấy đề tài!'];
    }
    
    if ($extensionCount <= 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Đề tài này đã hết số lần gia hạn được phép!'];
    }
    
    // Lấy thời gian hiện tại cho trường time
    $current_time = date('Y-m-d H:i:s');
    
    // Nếu có uuid và file_content thì thêm vào câu INSERT
    if ($uuid && $file_content) {
        $sql = "INSERT INTO project_extensions (project_id, time, datefinish_before, datefinish_after, uuid, file) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isssss", $project_id, $current_time, $datefinish_before, $datefinish_after, $uuid, $file_content);
        }
    } else {
        $sql = "INSERT INTO project_extensions (project_id, time, datefinish_before, datefinish_after) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isss", $project_id, $current_time, $datefinish_before, $datefinish_after);
        }
    }
    
    if ($stmt) {
        $success = mysqli_stmt_execute($stmt);
        
        // Nếu thêm gia hạn thành công, cập nhật ngày kết thúc và giảm số lần gia hạn
        if ($success) {
            // Cập nhật ngày kết thúc mới và giảm number_extension đi 1
            $update_sql = "UPDATE projects SET date_finish = ?, number_extension = number_extension - 1 WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, "si", $datefinish_after, $project_id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            }
            
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return ['success' => true, 'message' => 'Thêm gia hạn đề tài thành công!'];
        }
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Có lỗi xảy ra khi thêm gia hạn đề tài!'];
    }
    
    mysqli_close($conn);
    return ['success' => false, 'message' => 'Có lỗi xảy ra khi chuẩn bị truy vấn!'];
}

/**
 * Lấy thông tin một gia hạn đề tài theo ID
 * @param int $id ID của gia hạn đề tài
 * @return array|null Thông tin gia hạn đề tài hoặc null nếu không tìm thấy
 */
function getProjectExtensionById($id) {
    $conn = getDbConnection();
    
    $sql = "SELECT pe.id, pe.project_id, p.project_name, pe.time, pe.datefinish_before, pe.datefinish_after, pe.uuid
            FROM project_extensions pe
            INNER JOIN projects p ON pe.project_id = p.id
            WHERE pe.id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $extension = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return $extension;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return null;
}

/**
 * Cập nhật thông tin gia hạn đề tài
 * @param int $id ID của gia hạn đề tài
 * @param int $project_id ID của đề tài
 * @param string $datefinish_before Ngày kết thúc trước khi gia hạn
 * @param string $datefinish_after Ngày kết thúc sau khi gia hạn
 * @return bool True nếu thành công, False nếu thất bại
 */
function updateProjectExtension($id, $project_id, $datefinish_before, $datefinish_after, $uuid = null, $file_content = null) {
    $conn = getDbConnection();
    
    // Nếu có file mới thì cập nhật cả file
    if ($uuid && $file_content) {
        $sql = "UPDATE project_extensions SET project_id = ?, datefinish_before = ?, datefinish_after = ?, uuid = ?, file = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issssi", $project_id, $datefinish_before, $datefinish_after, $uuid, $file_content, $id);
        }
    } else {
        // Cập nhật không có file mới
        $sql = "UPDATE project_extensions SET project_id = ?, datefinish_before = ?, datefinish_after = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issi", $project_id, $datefinish_before, $datefinish_after, $id);
        }
    }
    
    if ($stmt) {
        $success = mysqli_stmt_execute($stmt);
        
        // Nếu cập nhật gia hạn thành công, cập nhật ngày kết thúc mới cho đề tài
        if ($success) {
            $update_sql = "UPDATE projects SET date_finish = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, "si", $datefinish_after, $project_id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            }
        }
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return $success;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Xóa gia hạn đề tài theo ID
 * @param int $id ID của gia hạn đề tài cần xóa
 * @return bool True nếu thành công, False nếu thất bại
 */
function deleteProjectExtension($id) {
    $conn = getDbConnection();
    
    // Trước khi xóa, lấy thông tin gia hạn để biết project_id
    $extension = getProjectExtensionById($id);
    if (!$extension) {
        mysqli_close($conn);
        return false;
    }
    
    $sql = "DELETE FROM project_extensions WHERE id = ?";
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
 * Lấy tất cả đề tài để chọn trong form
 * @return array Danh sách các đề tài kèm số lần gia hạn còn lại
 */
function getAllProjectsForSelect() {
    $conn = getDbConnection();
    
    $sql = "SELECT id, project_code, project_name, number_extension FROM projects ORDER BY project_name";
    $result = mysqli_query($conn, $sql);
    
    $projects = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $projects[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $projects;
}

/**
 * Lấy ngày kết thúc hiện tại của đề tài theo ID
 * @param int $project_id ID của đề tài
 * @return string|null Ngày kết thúc của đề tài hoặc null nếu không tìm thấy
 */
function getProjectFinishDate($project_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT date_finish FROM projects WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $project_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return $row['date_finish'];
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return null;
}

/**
 * Lấy danh sách gia hạn đề tài có phân trang
 * @param int $page Trang hiện tại
 * @param mixed $limit Số lượng bản ghi trên mỗi trang hoặc 'all'
 * @param string $sortField Trường sắp xếp
 * @param string $sortOrder Thứ tự sắp xếp (asc/desc)
 * @return array Mảng chứa thông tin phân trang và danh sách gia hạn
 */
function getProjectExtensionsWithPagination($page, $limit, $sortField, $sortOrder) {
    $conn = getDbConnection();
    
    // Validate input
    $allowedFields = ['id', 'project_code', 'project_name', 'datefinish_before', 'datefinish_after'];
    $allowedOrder = ['asc', 'desc'];
    
    if (!in_array($sortField, $allowedFields)) {
        $sortField = 'id';
    }
    
    if (!in_array(strtolower($sortOrder), $allowedOrder)) {
        $sortOrder = 'asc';
    }
    
    // Xử lý đặc biệt cho trường project_name và project_code
    if ($sortField === 'project_name' || $sortField === 'project_code') {
        $orderByField = 'p.' . $sortField;
    } else {
        $orderByField = 'pe.' . $sortField;
    }
    
    // Đếm tổng số bản ghi
    $countSql = "SELECT COUNT(*) as total 
                 FROM project_extensions pe
                 INNER JOIN projects p ON pe.project_id = p.id";
    $countResult = mysqli_query($conn, $countSql);
    $totalExtensions = 0;
    
    if ($countResult) {
        $countRow = mysqli_fetch_assoc($countResult);
        $totalExtensions = (int)$countRow['total'];
    }
    
    // Tính toán phân trang
    if ($limit === 'all') {
        $totalPages = 1;
        $currentPage = 1;
        $offset = 0;
        $limitClause = "";
    } else {
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 10;
        
        $totalPages = ceil($totalExtensions / $limit);
        $currentPage = max(1, min($page, $totalPages));
        $offset = ($currentPage - 1) * $limit;
        $limitClause = "LIMIT $limit OFFSET $offset";
    }
    
    // Truy vấn dữ liệu với phân trang
    $sql = "SELECT pe.id, pe.project_id, p.project_code, p.project_name, pe.datefinish_before, pe.datefinish_after, pe.uuid
            FROM project_extensions pe
            INNER JOIN projects p ON pe.project_id = p.id
            ORDER BY $orderByField $sortOrder
            $limitClause";
    
    $result = mysqli_query($conn, $sql);
    $extensions = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $extensions[] = $row;
        }
    }
    
    mysqli_close($conn);
    
    return [
        'extensions' => $extensions,
        'totalExtensions' => $totalExtensions,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage
    ];
}

/**
 * Tìm kiếm gia hạn đề tài có phân trang
 * @param int $page Trang hiện tại
 * @param mixed $limit Số lượng bản ghi trên mỗi trang hoặc 'all'
 * @param string $sortField Trường sắp xếp
 * @param string $sortOrder Thứ tự sắp xếp (asc/desc)
 * @param string $searchProjectName Tên đề tài tìm kiếm
 * @param string $searchDateFinishBefore Ngày kết thúc trước gia hạn
 * @param string $searchDateFinishAfter Ngày kết thúc sau gia hạn
 * @return array Mảng chứa thông tin phân trang và danh sách gia hạn
 */
function searchProjectExtensionsWithPagination($page, $limit, $sortField, $sortOrder, $searchProjectName, $searchProjectCode, $searchDateFinishBefore, $searchDateFinishAfter) {
    $conn = getDbConnection();
    
    // Validate input
    $allowedFields = ['id', 'project_code', 'project_name', 'datefinish_before', 'datefinish_after'];
    $allowedOrder = ['asc', 'desc'];
    
    if (!in_array($sortField, $allowedFields)) {
        $sortField = 'id';
    }
    
    if (!in_array(strtolower($sortOrder), $allowedOrder)) {
        $sortOrder = 'asc';
    }
    
    // Xử lý đặc biệt cho trường project_name và project_code
    if ($sortField === 'project_name' || $sortField === 'project_code') {
        $orderByField = 'p.' . $sortField;
    } else {
        $orderByField = 'pe.' . $sortField;
    }
    
    // Xây dựng điều kiện WHERE
    $whereConditions = [];
    $params = [];
    $types = "";
    
    if (!empty($searchProjectName)) {
        $whereConditions[] = "p.project_name LIKE ?";
        $params[] = "%" . $searchProjectName . "%";
        $types .= "s";
    }
    
    if (!empty($searchProjectCode)) {
        $whereConditions[] = "p.project_code LIKE ?";
        $params[] = "%" . $searchProjectCode . "%";
        $types .= "s";
    }
    
    if (!empty($searchDateFinishBefore)) {
        $whereConditions[] = "pe.datefinish_before = ?";
        $params[] = $searchDateFinishBefore;
        $types .= "s";
    }
    
    if (!empty($searchDateFinishAfter)) {
        $whereConditions[] = "pe.datefinish_after = ?";
        $params[] = $searchDateFinishAfter;
        $types .= "s";
    }
    
    $whereClause = "";
    if (!empty($whereConditions)) {
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Đếm tổng số bản ghi
    $countSql = "SELECT COUNT(*) as total 
                 FROM project_extensions pe
                 INNER JOIN projects p ON pe.project_id = p.id
                 $whereClause";
    
    $totalExtensions = 0;
    if (!empty($params)) {
        $countStmt = mysqli_prepare($conn, $countSql);
        if ($countStmt) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $countResult = mysqli_stmt_get_result($countStmt);
            if ($countResult) {
                $countRow = mysqli_fetch_assoc($countResult);
                $totalExtensions = (int)$countRow['total'];
            }
            mysqli_stmt_close($countStmt);
        }
    } else {
        $countResult = mysqli_query($conn, $countSql);
        if ($countResult) {
            $countRow = mysqli_fetch_assoc($countResult);
            $totalExtensions = (int)$countRow['total'];
        }
    }
    
    // Tính toán phân trang
    if ($limit === 'all') {
        $totalPages = 1;
        $currentPage = 1;
        $offset = 0;
        $limitClause = "";
    } else {
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 10;
        
        $totalPages = ceil($totalExtensions / $limit);
        $currentPage = max(1, min($page, $totalPages));
        $offset = ($currentPage - 1) * $limit;
        $limitClause = "LIMIT $limit OFFSET $offset";
    }
    
    // Truy vấn dữ liệu với phân trang
    $sql = "SELECT pe.id, pe.project_id, p.project_code, p.project_name, pe.datefinish_before, pe.datefinish_after, pe.uuid
            FROM project_extensions pe
            INNER JOIN projects p ON pe.project_id = p.id
            $whereClause
            ORDER BY $orderByField $sortOrder
            $limitClause";
    
    $extensions = [];
    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $extensions[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $extensions[] = $row;
            }
        }
    }
    
    mysqli_close($conn);
    
    return [
        'extensions' => $extensions,
        'totalExtensions' => $totalExtensions,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage
    ];
}

/**
 * Lấy lịch sử gia hạn của một đề tài cụ thể
 * @param int $project_id ID của đề tài
 * @return array Danh sách lịch sử gia hạn được sắp xếp theo thời gian (mới nhất trước)
 */
function getProjectExtensionHistory($project_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT pe.id, pe.project_id, pe.time, pe.datefinish_before, pe.datefinish_after,
                   p.project_code, p.project_name
            FROM project_extensions pe
            INNER JOIN projects p ON pe.project_id = p.id
            WHERE pe.project_id = ?
            ORDER BY pe.time DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    $history = [];
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $project_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $history[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $history;
}
?>
