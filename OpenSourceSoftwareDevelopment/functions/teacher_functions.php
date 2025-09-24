<?php
require_once 'db_connection.php';

/**
 * Lấy tất cả danh sách teachers từ database
 * @return array Danh sách teachers
 */
function getAllTeachers() {
    $conn = getDbConnection();
    
    // Truy vấn lấy tất cả teachers
    $sql = "SELECT id, teacher_code, teacher_name, teacher_email FROM teachers ORDER BY id";
    $result = mysqli_query($conn, $sql);
    
    $teachers = [];
    if ($result && mysqli_num_rows($result) > 0) {
        // Lặp qua từng dòng trong kết quả truy vấn $result
        while ($row = mysqli_fetch_assoc($result)) { 
            $teachers[] = $row; // Thêm mảng $row vào cuối mảng $teachers
        }
    }
    
    mysqli_close($conn);
    return $teachers;
}

/**
 * Thêm teacher mới
 * @param string $teacher_code Mã giảng viên
 * @param string $teacher_name Tên giảng viên
 * @param string $teacher_email Email giảng viên
 * @return bool True nếu thành công, False nếu thất bại
 */
function addTeacher($teacher_code, $teacher_name, $teacher_email) {
    $conn = getDbConnection();
    
    $sql = "INSERT INTO teachers (teacher_code, teacher_name, teacher_email) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sss", $teacher_code, $teacher_name, $teacher_email);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return $success;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Lấy thông tin một teacher theo ID
 * @param int $id ID của teacher
 * @return array|null Thông tin teacher hoặc null nếu không tìm thấy
 */
function getTeacherById($id) {
    $conn = getDbConnection();
    
    $sql = "SELECT id, teacher_code, teacher_name, teacher_email FROM teachers WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $teacher = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return $teacher;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return null;
}

/**
 * Cập nhật thông tin teacher
 * @param int $id ID của teacher
 * @param string $teacher_code Mã giảng viên mới
 * @param string $teacher_name Tên giảng viên mới
 * @param string $teacher_email Email giảng viên mới
 * @return bool True nếu thành công, False nếu thất bại
 */
function updateTeacher($id, $teacher_code, $teacher_name, $teacher_email) {
    $conn = getDbConnection();
    
    $sql = "UPDATE teachers SET teacher_code = ?, teacher_name = ?, teacher_email = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssi", $teacher_code, $teacher_name, $teacher_email, $id);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return $success;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Xóa teacher theo ID
 * @param int $id ID của teacher cần xóa
 * @return bool True nếu thành công, False nếu thất bại
 */
function deleteTeacher($id) {
    $conn = getDbConnection();
    
    $sql = "DELETE FROM teachers WHERE id = ?";
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
 * Lấy danh sách giảng viên đã sắp xếp
 * @param string $field Trường để sắp xếp (id, teacher_code, teacher_name, teacher_email)
 * @param string $order Thứ tự sắp xếp (asc hoặc desc)
 * @return array Danh sách giảng viên đã sắp xếp
 */
function getSortedTeachers($field, $order) {
    $conn = getDbConnection();
    $validFields = ['id', 'teacher_code', 'teacher_name', 'teacher_email'];
    
    // Kiểm tra tính hợp lệ của trường và thứ tự sắp xếp
    if (!in_array($field, $validFields)) {
        $field = 'id';
    }
    
    if ($order !== 'asc' && $order !== 'desc') {
        $order = 'asc';
    }
    
    $sql = "SELECT id, teacher_code, teacher_name, teacher_email FROM teachers ORDER BY $field $order";
    
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
 * Lấy danh sách giảng viên với phân trang
 * @param int $page Trang hiện tại
 * @param int|string $limit Số lượng bản ghi trên mỗi trang hoặc 'all'
 * @param string $sortField Trường sắp xếp
 * @param string $sortOrder Thứ tự sắp xếp
 * @return array Dữ liệu phân trang
 */
function getTeachersWithPagination($page = 1, $limit = 10, $sortField = 'id', $sortOrder = 'asc') {
    $conn = getDbConnection();
    $validFields = ['id', 'teacher_code', 'teacher_name', 'teacher_email'];
    
    // Validate tham số
    if (!in_array($sortField, $validFields)) {
        $sortField = 'id';
    }
    if ($sortOrder !== 'asc' && $sortOrder !== 'desc') {
        $sortOrder = 'asc';
    }
    if ($page < 1) $page = 1;
    
    // Đếm tổng số bản ghi
    $countSql = "SELECT COUNT(*) as total FROM teachers";
    $countResult = mysqli_query($conn, $countSql);
    $totalTeachers = mysqli_fetch_assoc($countResult)['total'];
    
    if ($limit === 'all') {
        // Lấy tất cả bản ghi
        $sql = "SELECT id, teacher_code, teacher_name, teacher_email FROM teachers ORDER BY $sortField $sortOrder";
        $result = mysqli_query($conn, $sql);
        
        $teachers = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $teachers[] = $row;
            }
        }
        
        mysqli_close($conn);
        return [
            'teachers' => $teachers,
            'totalTeachers' => $totalTeachers,
            'totalPages' => 1,
            'currentPage' => 1
        ];
    } else {
        // Phân trang
        $limit = (int)$limit;
        $totalPages = ceil($totalTeachers / $limit);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT id, teacher_code, teacher_name, teacher_email FROM teachers ORDER BY $sortField $sortOrder LIMIT $limit OFFSET $offset";
        $result = mysqli_query($conn, $sql);
        
        $teachers = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $teachers[] = $row;
            }
        }
        
        mysqli_close($conn);
        return [
            'teachers' => $teachers,
            'totalTeachers' => $totalTeachers,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];
    }
}

/**
 * Tìm kiếm giảng viên với phân trang
 * @param int $page Trang hiện tại
 * @param int|string $limit Số lượng bản ghi trên mỗi trang hoặc 'all'
 * @param string $sortField Trường sắp xếp
 * @param string $sortOrder Thứ tự sắp xếp
 * @param string $searchTeacherCode Mã giảng viên tìm kiếm
 * @param string $searchTeacherName Tên giảng viên tìm kiếm
 * @param string $searchTeacherEmail Email giảng viên tìm kiếm
 * @return array Dữ liệu phân trang
 */
function searchTeachersWithPagination($page = 1, $limit = 10, $sortField = 'id', $sortOrder = 'asc', $searchTeacherCode = '', $searchTeacherName = '', $searchTeacherEmail = '') {
    $conn = getDbConnection();
    $validFields = ['id', 'teacher_code', 'teacher_name', 'teacher_email'];
    
    // Validate tham số
    if (!in_array($sortField, $validFields)) {
        $sortField = 'id';
    }
    if ($sortOrder !== 'asc' && $sortOrder !== 'desc') {
        $sortOrder = 'asc';
    }
    if ($page < 1) $page = 1;
    
    // Xây dựng điều kiện WHERE cho tìm kiếm
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($searchTeacherCode)) {
        $whereConditions[] = "teacher_code LIKE ?";
        $params[] = '%' . $searchTeacherCode . '%';
        $types .= 's';
    }
    
    if (!empty($searchTeacherName)) {
        $whereConditions[] = "teacher_name LIKE ?";
        $params[] = '%' . $searchTeacherName . '%';
        $types .= 's';
    }
    
    if (!empty($searchTeacherEmail)) {
        $whereConditions[] = "teacher_email LIKE ?";
        $params[] = '%' . $searchTeacherEmail . '%';
        $types .= 's';
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Đếm tổng số bản ghi với điều kiện tìm kiếm
    $countSql = "SELECT COUNT(*) as total FROM teachers" . $whereClause;
    if (!empty($params)) {
        $countStmt = mysqli_prepare($conn, $countSql);
        if ($countStmt) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $countResult = mysqli_stmt_get_result($countStmt);
            $totalTeachers = mysqli_fetch_assoc($countResult)['total'];
            mysqli_stmt_close($countStmt);
        } else {
            $totalTeachers = 0;
        }
    } else {
        $countResult = mysqli_query($conn, $countSql);
        $totalTeachers = mysqli_fetch_assoc($countResult)['total'];
    }
    
    if ($limit === 'all') {
        // Lấy tất cả bản ghi với điều kiện tìm kiếm
        $sql = "SELECT id, teacher_code, teacher_name, teacher_email FROM teachers" . $whereClause . " ORDER BY $sortField $sortOrder";
        
        if (!empty($params)) {
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
            } else {
                $result = false;
            }
        } else {
            $result = mysqli_query($conn, $sql);
        }
        
        $teachers = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $teachers[] = $row;
            }
        }
        
        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
        }
        mysqli_close($conn);
        
        return [
            'teachers' => $teachers,
            'totalTeachers' => $totalTeachers,
            'totalPages' => 1,
            'currentPage' => 1
        ];
    } else {
        // Phân trang với tìm kiếm
        $limit = (int)$limit;
        $totalPages = ceil($totalTeachers / $limit);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT id, teacher_code, teacher_name, teacher_email FROM teachers" . $whereClause . " ORDER BY $sortField $sortOrder LIMIT $limit OFFSET $offset";
        
        if (!empty($params)) {
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
            } else {
                $result = false;
            }
        } else {
            $result = mysqli_query($conn, $sql);
        }
        
        $teachers = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $teachers[] = $row;
            }
        }
        
        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
        }
        mysqli_close($conn);
        
        return [
            'teachers' => $teachers,
            'totalTeachers' => $totalTeachers,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];
    }
}
?>
