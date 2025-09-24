<?php
require_once 'db_connection.php';
require_once 'project_functions.php';

/**
 * Lấy tất cả danh sách project_details từ database
 * @return array Danh sách project_details với tên đề tài, giảng viên, sinh viên
 */
function getAllProjectDetails() {
    $conn = getDbConnection();
    
    // Truy vấn lấy tất cả project_details với join để lấy thông tin từ các bảng liên quan
    $sql = "SELECT pd.id, pd.project_id, pd.teacher_id, pd.student_id, pd.student_role,
                   p.project_name, t.teacher_name, s.student_name
            FROM project_details pd
            JOIN projects p ON pd.project_id = p.id
            JOIN teachers t ON pd.teacher_id = t.id
            JOIN students s ON pd.student_id = s.id
            ORDER BY pd.id";
    
    $result = mysqli_query($conn, $sql);
    
    $project_details = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { 
            $project_details[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $project_details;
}

/**
 * Thêm project detail mới
 * @param int $project_id ID của đề tài
 * @param int $teacher_id ID của giảng viên
 * @param int $student_id ID của sinh viên
 * @param string $student_role Vai trò của sinh viên (Member/Leader)
 * @return bool True nếu thành công, False nếu thất bại
 */
function addProjectDetail($project_id, $teacher_id, $student_id, $student_role) {
    $conn = getDbConnection();
    
    $sql = "INSERT INTO project_details (project_id, teacher_id, student_id, student_role) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iiis", $project_id, $teacher_id, $student_id, $student_role);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return $success;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Lấy thông tin một project detail theo ID
 * @param int $id ID của project detail
 * @return array|null Thông tin project detail hoặc null nếu không tìm thấy
 */
function getProjectDetailById($id) {
    $conn = getDbConnection();
    
    $sql = "SELECT pd.*, p.project_name, t.teacher_name, s.student_name 
            FROM project_details pd
            JOIN projects p ON pd.project_id = p.id
            JOIN teachers t ON pd.teacher_id = t.id
            JOIN students s ON pd.student_id = s.id
            WHERE pd.id = ? LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $project_detail = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return $project_detail;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return null;
}

/**
 * Cập nhật thông tin project detail
 * @param int $id ID của project detail
 * @param int $project_id ID của đề tài mới
 * @param int $teacher_id ID của giảng viên mới
 * @param int $student_id ID của sinh viên mới
 * @param string $student_role Vai trò của sinh viên mới
 * @return bool True nếu thành công, False nếu thất bại
 */
function updateProjectDetail($id, $project_id, $teacher_id, $student_id, $student_role) {
    $conn = getDbConnection();
    
    $sql = "UPDATE project_details SET project_id = ?, teacher_id = ?, student_id = ?, student_role = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iiisi", $project_id, $teacher_id, $student_id, $student_role, $id);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return $success;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Xóa project detail theo ID
 * @param int $id ID của project detail cần xóa
 * @return bool True nếu thành công, False nếu thất bại
 */
function deleteProjectDetail($id) {
    $conn = getDbConnection();
    
    $sql = "DELETE FROM project_details WHERE id = ?";
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
 * Lấy danh sách tất cả các giảng viên
 * @return array Danh sách giảng viên
 */
function getAllTeachers() {
    $conn = getDbConnection();
    
    $sql = "SELECT id, teacher_code, teacher_name FROM teachers ORDER BY teacher_name";
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
 * Lấy danh sách tất cả các sinh viên
 * @return array Danh sách sinh viên
 */
function getAllStudentsForSelect() {
    $conn = getDbConnection();
    
    $sql = "SELECT id, student_code, student_name FROM students ORDER BY student_name";
    $result = mysqli_query($conn, $sql);
    
    $students = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $students;
}

/**
 * Sắp xếp danh sách chi tiết đề tài
 * @param string $field Trường để sắp xếp
 * @param string $order Thứ tự sắp xếp (asc hoặc desc)
 * @return array Danh sách đã sắp xếp
 */
function getSortedProjectDetails($field, $order) {
    $conn = getDbConnection();
    $validFields = ['id', 'project_name', 'teacher_name', 'student_name', 'student_role'];
    
    // Kiểm tra tính hợp lệ của trường và thứ tự sắp xếp
    if (!in_array($field, $validFields)) {
        $field = 'id';
    }
    
    if ($order !== 'asc' && $order !== 'desc') {
        $order = 'asc';
    }
    
    // Xác định trường sắp xếp từ tên bảng tương ứng
    $sortField = 'pd.id';
    if ($field === 'project_name') {
        $sortField = 'p.project_name';
    } elseif ($field === 'teacher_name') {
        $sortField = 't.teacher_name';
    } elseif ($field === 'student_name') {
        $sortField = 's.student_name';
    } elseif ($field === 'student_role') {
        $sortField = 'pd.student_role';
    }
    
    $sql = "SELECT pd.id, pd.project_id, pd.teacher_id, pd.student_id, pd.student_role,
                   p.project_name, t.teacher_name, s.student_name
            FROM project_details pd
            JOIN projects p ON pd.project_id = p.id
            JOIN teachers t ON pd.teacher_id = t.id
            JOIN students s ON pd.student_id = s.id
            ORDER BY $sortField $order";
    
    $result = mysqli_query($conn, $sql);
    
    $project_details = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $project_details[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $project_details;
}

/**
 * Lấy tổng số project details
 * @return int Tổng số project details
 */
function getTotalProjectDetails() {
    $conn = getDbConnection();
    
    $sql = "SELECT COUNT(*) as total FROM project_details";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
    mysqli_close($conn);
    return $row['total'];
}

/**
 * Lấy danh sách project details với phân trang
 * @param int $page Trang hiện tại
 * @param mixed $limit Số lượng bản ghi trên trang hoặc 'all'
 * @param string $field Trường để sắp xếp
 * @param string $order Hướng sắp xếp (asc/desc)
 * @return array Danh sách project details
 */
function getProjectDetailsWithPagination($page = 1, $limit = 10, $field = 'id', $order = 'asc') {
    $conn = getDbConnection();
    
    // Validate field để tránh SQL injection
    $allowedFields = ['id', 'project_code', 'project_name', 'teacher_name', 'student_name', 'student_role'];
    if (!in_array($field, $allowedFields)) {
        $field = 'id';
    }
    
    // Validate order
    $order = ($order === 'desc') ? 'DESC' : 'ASC';
    
    // Xây dựng query cơ bản
    $sql = "SELECT pd.id, pd.project_id, pd.teacher_id, pd.student_id, pd.student_role,
                   p.project_code, p.project_name, t.teacher_name, s.student_name
            FROM project_details pd
            JOIN projects p ON pd.project_id = p.id
            JOIN teachers t ON pd.teacher_id = t.id
            JOIN students s ON pd.student_id = s.id";
    
    // Thêm ORDER BY
    if ($field === 'project_code') {
        $sql .= " ORDER BY p.project_code $order";
    } elseif ($field === 'project_name') {
        $sql .= " ORDER BY p.project_name $order";
    } elseif ($field === 'teacher_name') {
        $sql .= " ORDER BY t.teacher_name $order";
    } elseif ($field === 'student_name') {
        $sql .= " ORDER BY s.student_name $order";
    } else {
        $sql .= " ORDER BY pd.$field $order";
    }
    
    // Thêm LIMIT nếu không phải 'all'
    if ($limit !== 'all') {
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT $limit OFFSET $offset";
    }
    
    $result = mysqli_query($conn, $sql);
    
    $project_details = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $project_details[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $project_details;
}

/**
 * Tìm kiếm project details với phân trang
 * @param int $page Trang hiện tại
 * @param mixed $limit Số lượng bản ghi trên trang hoặc 'all'
 * @param string $field Trường để sắp xếp
 * @param string $order Hướng sắp xếp (asc/desc)
 * @param string $projectName Tên đề tài để tìm kiếm
 * @param string $teacherName Tên giảng viên để tìm kiếm
 * @param string $studentName Tên sinh viên để tìm kiếm
 * @param string $studentRole Vai trò sinh viên để tìm kiếm
 * @return array Danh sách project details
 */
function searchProjectDetailsWithPagination($page = 1, $limit = 10, $field = 'id', $order = 'asc', $projectName = '', $projectCode = '', $teacherName = '', $studentName = '', $studentRole = '') {
    $conn = getDbConnection();
    
    // Validate field để tránh SQL injection
    $allowedFields = ['id', 'project_code', 'project_name', 'teacher_name', 'student_name', 'student_role'];
    if (!in_array($field, $allowedFields)) {
        $field = 'id';
    }
    
    // Validate order
    $order = ($order === 'desc') ? 'DESC' : 'ASC';
    
    // Xây dựng query cơ bản
    $sql = "SELECT pd.id, pd.project_id, pd.teacher_id, pd.student_id, pd.student_role,
                   p.project_code, p.project_name, t.teacher_name, s.student_name
            FROM project_details pd
            JOIN projects p ON pd.project_id = p.id
            JOIN teachers t ON pd.teacher_id = t.id
            JOIN students s ON pd.student_id = s.id";
    
    // Xây dựng điều kiện WHERE
    $whereConditions = [];
    if (!empty($projectName)) {
        $whereConditions[] = "p.project_name LIKE '%" . mysqli_real_escape_string($conn, $projectName) . "%'";
    }
    if (!empty($projectCode)) {
        $whereConditions[] = "p.project_code LIKE '%" . mysqli_real_escape_string($conn, $projectCode) . "%'";
    }
    if (!empty($teacherName)) {
        $whereConditions[] = "t.teacher_name LIKE '%" . mysqli_real_escape_string($conn, $teacherName) . "%'";
    }
    if (!empty($studentName)) {
        $whereConditions[] = "s.student_name LIKE '%" . mysqli_real_escape_string($conn, $studentName) . "%'";
    }
    if (!empty($studentRole)) {
        $whereConditions[] = "pd.student_role = '" . mysqli_real_escape_string($conn, $studentRole) . "'";
    }
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Thêm ORDER BY
    if ($field === 'project_code') {
        $sql .= " ORDER BY p.project_code $order";
    } elseif ($field === 'project_name') {
        $sql .= " ORDER BY p.project_name $order";
    } elseif ($field === 'teacher_name') {
        $sql .= " ORDER BY t.teacher_name $order";
    } elseif ($field === 'student_name') {
        $sql .= " ORDER BY s.student_name $order";
    } else {
        $sql .= " ORDER BY pd.$field $order";
    }
    
    // Thêm LIMIT nếu không phải 'all'
    if ($limit !== 'all') {
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT $limit OFFSET $offset";
    }
    
    $result = mysqli_query($conn, $sql);
    
    $project_details = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $project_details[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $project_details;
}

/**
 * Lấy tổng số project details theo điều kiện tìm kiếm
 * @param string $projectName Tên đề tài để tìm kiếm
 * @param string $projectCode Mã đề tài để tìm kiếm
 * @param string $teacherName Tên giảng viên để tìm kiếm
 * @param string $studentName Tên sinh viên để tìm kiếm
 * @param string $studentRole Vai trò sinh viên để tìm kiếm
 * @return int Tổng số project details
 */
function getTotalSearchProjectDetails($projectName = '', $projectCode = '', $teacherName = '', $studentName = '', $studentRole = '') {
    $conn = getDbConnection();
    
    $sql = "SELECT COUNT(*) as total
            FROM project_details pd
            JOIN projects p ON pd.project_id = p.id
            JOIN teachers t ON pd.teacher_id = t.id
            JOIN students s ON pd.student_id = s.id";
    
    // Xây dựng điều kiện WHERE
    $whereConditions = [];
    if (!empty($projectName)) {
        $whereConditions[] = "p.project_name LIKE '%" . mysqli_real_escape_string($conn, $projectName) . "%'";
    }
    if (!empty($projectCode)) {
        $whereConditions[] = "p.project_code LIKE '%" . mysqli_real_escape_string($conn, $projectCode) . "%'";
    }
    if (!empty($teacherName)) {
        $whereConditions[] = "t.teacher_name LIKE '%" . mysqli_real_escape_string($conn, $teacherName) . "%'";
    }
    if (!empty($studentName)) {
        $whereConditions[] = "s.student_name LIKE '%" . mysqli_real_escape_string($conn, $studentName) . "%'";
    }
    if (!empty($studentRole)) {
        $whereConditions[] = "pd.student_role = '" . mysqli_real_escape_string($conn, $studentRole) . "'";
    }
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
    mysqli_close($conn);
    return $row['total'];
}
?>
