<?php
require_once 'db_connection.php';

/**
 * Lấy tất cả danh sách students từ database
 * @return array Danh sách students
 */
function getAllStudents() {
    $conn = getDbConnection();
    
    // Truy vấn lấy tất cả students
    $sql = "SELECT id, student_code, student_name, student_email FROM students ORDER BY id";
    $result = mysqli_query($conn, $sql);
    
    $students = [];
    if ($result && mysqli_num_rows($result) > 0) {
        // Lặp qua từng dòng trong kết quả truy vấn $result
        while ($row = mysqli_fetch_assoc($result)) { 
            $students[] = $row; // Thêm mảng $row vào cuối mảng $students
        }
    }
    
    mysqli_close($conn);
    return $students;
}

/**
 * Thêm student mới
 * @param string $student_code Mã sinh viên
 * @param string $student_name Tên sinh viên
 * @param string $student_email Email sinh viên
 * @return bool True nếu thành công, False nếu thất bại
 */
function addStudent($student_code, $student_name, $student_email) {
    $conn = getDbConnection();
    
    $sql = "INSERT INTO students (student_code, student_name, student_email) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sss", $student_code, $student_name, $student_email);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return $success;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Lấy thông tin một student theo ID
 * @param int $id ID của student
 * @return array|null Thông tin student hoặc null nếu không tìm thấy
 */
function getStudentById($id) {
    $conn = getDbConnection();
    
    $sql = "SELECT id, student_code, student_name, student_email FROM students WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $student = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return $student;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return null;
}

/**
 * Cập nhật thông tin student
 * @param int $id ID của student
 * @param string $student_code Mã sinh viên mới
 * @param string $student_name Tên sinh viên mới
 * @param string $student_email Email sinh viên mới
 * @return bool True nếu thành công, False nếu thất bại
 */
function updateStudent($id, $student_code, $student_name, $student_email) {
    $conn = getDbConnection();
    
    $sql = "UPDATE students SET student_code = ?, student_name = ?, student_email = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssi", $student_code, $student_name, $student_email, $id);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return $success;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Xóa student theo ID
 * @param int $id ID của student cần xóa
 * @return bool True nếu thành công, False nếu thất bại
 */
function deleteStudent($id) {
    $conn = getDbConnection();
    
    $sql = "DELETE FROM students WHERE id = ?";
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
 * Lấy danh sách sinh viên đã sắp xếp
 * @param string $field Trường để sắp xếp (id, student_code, student_name, student_email)
 * @param string $order Thứ tự sắp xếp (asc hoặc desc)
 * @return array Danh sách sinh viên đã sắp xếp
 */
function getSortedStudents($field, $order) {
    $conn = getDbConnection();
    $validFields = ['id', 'student_code', 'student_name', 'student_email'];
    
    // Kiểm tra tính hợp lệ của trường và thứ tự sắp xếp
    if (!in_array($field, $validFields)) {
        $field = 'id';
    }
    
    if ($order !== 'asc' && $order !== 'desc') {
        $order = 'asc';
    }
    
    $sql = "SELECT id, student_code, student_name, student_email FROM students ORDER BY $field $order";
    
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
 * Đếm tổng số sinh viên
 * @return int Tổng số sinh viên
 */
function getTotalStudents() {
    $conn = getDbConnection();
    
    $sql = "SELECT COUNT(*) as total FROM students";
    $result = mysqli_query($conn, $sql);
    
    $total = 0;
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $total = (int)$row['total'];
    }
    
    mysqli_close($conn);
    return $total;
}

/**
 * Lấy danh sách sinh viên có phân trang
 * @param int $page Trang hiện tại (bắt đầu từ 1)
 * @param int|string $limit Số bản ghi trên mỗi trang hoặc 'all'
 * @param string $field Trường để sắp xếp
 * @param string $order Thứ tự sắp xếp
 * @return array Danh sách sinh viên
 */
function getStudentsWithPagination($page = 1, $limit = 10, $field = 'id', $order = 'asc') {
    $conn = getDbConnection();
    $validFields = ['id', 'student_code', 'student_name', 'student_email'];
    
    // Kiểm tra tính hợp lệ của các tham số
    if (!in_array($field, $validFields)) {
        $field = 'id';
    }
    
    if ($order !== 'asc' && $order !== 'desc') {
        $order = 'asc';
    }
    
    if ($page < 1) {
        $page = 1;
    }
    
    // Xây dựng truy vấn SQL
    $sql = "SELECT id, student_code, student_name, student_email 
            FROM students 
            ORDER BY $field $order";
    
    // Thêm LIMIT và OFFSET nếu không phải 'all'
    if ($limit !== 'all') {
        $limit = (int)$limit;
        if ($limit < 1) {
            $limit = 10;
        }
        
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT $limit OFFSET $offset";
    }
    
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
 * Tìm kiếm sinh viên với phân trang
 * @param int $page Trang hiện tại
 * @param mixed $limit Số lượng bản ghi trên trang hoặc 'all'
 * @param string $field Trường sắp xếp
 * @param string $order Thứ tự sắp xếp (asc/desc)
 * @param string $studentCode Mã sinh viên tìm kiếm
 * @param string $studentName Tên sinh viên tìm kiếm
 * @param string $studentEmail Email sinh viên tìm kiếm
 * @return array Danh sách sinh viên
 */
function searchStudentsWithPagination($page = 1, $limit = 10, $field = 'id', $order = 'asc', $studentCode = '', $studentName = '', $studentEmail = '') {
    $conn = getDbConnection();
    
    // Xây dựng câu truy vấn với điều kiện tìm kiếm
    $sql = "SELECT id, student_code, student_name, student_email FROM students WHERE 1=1";
    $params = [];
    $types = "";
    
    // Thêm điều kiện tìm kiếm
    if (!empty($studentCode)) {
        $sql .= " AND student_code LIKE ?";
        $params[] = "%" . $studentCode . "%";
        $types .= "s";
    }
    
    if (!empty($studentName)) {
        $sql .= " AND student_name LIKE ?";
        $params[] = "%" . $studentName . "%";
        $types .= "s";
    }
    
    if (!empty($studentEmail)) {
        $sql .= " AND student_email LIKE ?";
        $params[] = "%" . $studentEmail . "%";
        $types .= "s";
    }
    
    // Thêm sắp xếp
    $validFields = ['id', 'student_code', 'student_name', 'student_email'];
    $validOrders = ['asc', 'desc'];
    
    if (in_array($field, $validFields) && in_array($order, $validOrders)) {
        $sql .= " ORDER BY $field $order";
    } else {
        $sql .= " ORDER BY id asc";
    }
    
    // Chuẩn bị và thực thi truy vấn
    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            mysqli_close($conn);
            return [];
        }
    } else {
        $result = mysqli_query($conn, $sql);
    }
    
    // Lấy tất cả kết quả trước khi phân trang
    $allStudents = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $allStudents[] = $row;
        }
    }
    
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
    
    // Áp dụng phân trang
    $totalResults = count($allStudents);
    
    if ($limit === 'all') {
        return $allStudents;
    } else {
        $limit = (int)$limit;
        if ($limit < 1) {
            $limit = 10;
        }
        
        $offset = ($page - 1) * $limit;
        return array_slice($allStudents, $offset, $limit);
    }
}

/**
 * Đếm tổng số sinh viên theo điều kiện tìm kiếm
 * @param string $studentCode Mã sinh viên tìm kiếm
 * @param string $studentName Tên sinh viên tìm kiếm
 * @param string $studentEmail Email sinh viên tìm kiếm
 * @return int Tổng số sinh viên
 */
function getTotalStudentsWithSearch($studentCode = '', $studentName = '', $studentEmail = '') {
    $conn = getDbConnection();
    
    // Xây dựng câu truy vấn đếm
    $sql = "SELECT COUNT(*) as total FROM students WHERE 1=1";
    $params = [];
    $types = "";
    
    // Thêm điều kiện tìm kiếm
    if (!empty($studentCode)) {
        $sql .= " AND student_code LIKE ?";
        $params[] = "%" . $studentCode . "%";
        $types .= "s";
    }
    
    if (!empty($studentName)) {
        $sql .= " AND student_name LIKE ?";
        $params[] = "%" . $studentName . "%";
        $types .= "s";
    }
    
    if (!empty($studentEmail)) {
        $sql .= " AND student_email LIKE ?";
        $params[] = "%" . $studentEmail . "%";
        $types .= "s";
    }
    
    // Chuẩn bị và thực thi truy vấn
    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            mysqli_close($conn);
            return 0;
        }
    } else {
        $result = mysqli_query($conn, $sql);
    }
    
    $total = 0;
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $total = (int)$row['total'];
    }
    
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
    
    return $total;
}
?>
