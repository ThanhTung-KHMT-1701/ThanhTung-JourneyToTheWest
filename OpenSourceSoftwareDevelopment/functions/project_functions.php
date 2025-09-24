<?php
require_once 'db_connection.php';

/**
 * Lấy tất cả danh sách projects từ database
 * @return array Danh sách projects
 */
function getAllProjects() {
    $conn = getDbConnection();
    
    // Truy vấn lấy tất cả projects, bao gồm cả uuid và kiểm tra file
    $sql = "SELECT id, project_code, project_name, date_start, date_finish, status, number_extension, uuid, 
                   CASE WHEN file IS NOT NULL THEN 'has_file' ELSE NULL END as file 
            FROM projects ORDER BY id ASC";
    $result = mysqli_query($conn, $sql);
    
    $projects = [];
    if ($result && mysqli_num_rows($result) > 0) {
        // Lặp qua từng dòng trong kết quả truy vấn $result
        while ($row = mysqli_fetch_assoc($result)) { 
            $projects[] = $row; // Thêm mảng $row vào cuối mảng $projects
        }
    }
    
    mysqli_close($conn);
    return $projects;
}

/**
 * Thêm project mới
 * @param string $project_code Mã đề tài
 * @param string $project_name Tên đề tài
 * @param string $date_start Ngày bắt đầu (Y-m-d)
 * @param string $date_finish Ngày kết thúc (Y-m-d)
 * @param string $status Trạng thái đề tài
 * @param string $uuid UUID của file đính kèm (optional)
 * @param string $fileContent Nội dung file PDF (optional)
 * @return bool True nếu thành công, False nếu thất bại
 */
function addProject($project_code, $project_name, $date_start, $date_finish, $status, $uuid = null, $fileContent = null) {
    $conn = getDbConnection();
    
    // Kiểm tra nếu có uuid và file thì thêm vào query
    if (!empty($uuid) && !empty($fileContent)) {
        $sql = "INSERT INTO projects (project_code, project_name, date_created, date_start, date_finish, status, uuid, file) 
                VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssss", $project_code, $project_name, $date_start, $date_finish, $status, $uuid, $fileContent);
            $success = mysqli_stmt_execute($stmt);
            
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return $success;
        }
    } else {
        // Trường hợp không có file (chỉ thêm uuid nếu có)
        if (!empty($uuid)) {
            $sql = "INSERT INTO projects (project_code, project_name, date_created, date_start, date_finish, status, uuid) 
                    VALUES (?, ?, NOW(), ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssssss", $project_code, $project_name, $date_start, $date_finish, $status, $uuid);
                $success = mysqli_stmt_execute($stmt);
                
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                return $success;
            }
        } else {
            // Fallback cho trường hợp không có uuid và file (backward compatibility)
            $sql = "INSERT INTO projects (project_code, project_name, date_created, date_start, date_finish, status) 
                    VALUES (?, ?, NOW(), ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sssss", $project_code, $project_name, $date_start, $date_finish, $status);
                $success = mysqli_stmt_execute($stmt);
                
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                return $success;
            }
        }
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Lấy thông tin một project theo ID
 * @param int $id ID của project
 * @return array|null Thông tin project hoặc null nếu không tìm thấy
 */
function getProjectById($id) {
    $conn = getDbConnection();
    
    $sql = "SELECT id, project_code, project_name, date_created, date_start, date_finish, status, number_extension, uuid 
            FROM projects WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $project = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return $project;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return null;
}

/**
 * Cập nhật thông tin project
 * @param int $id ID của project
 * @param string $project_code Mã đề tài mới
 * @param string $project_name Tên đề tài mới
 * @param string $date_start Ngày bắt đầu mới (Y-m-d)
 * @param string $date_finish Ngày kết thúc mới (Y-m-d)
 * @param string $status Trạng thái mới
 * @param string|null $file_content Nội dung file PDF (null nếu không cập nhật file)
 * @return bool True nếu thành công, False nếu thất bại
 */
function updateProject($id, $project_code, $project_name, $date_start, $date_finish, $status, $file_content = null) {
    $conn = getDbConnection();
    
    // Xây dựng câu SQL tùy theo có file hay không
    if ($file_content !== null) {
        // Cập nhật cả file
        $sql = "UPDATE projects SET project_code = ?, project_name = ?, date_start = ?, date_finish = ?, status = ?, file = ? 
                WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssssi", $project_code, $project_name, $date_start, $date_finish, $status, $file_content, $id);
            $success = mysqli_stmt_execute($stmt);
            
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return $success;
        }
    } else {
        // Không cập nhật file, chỉ cập nhật thông tin khác
        $sql = "UPDATE projects SET project_code = ?, project_name = ?, date_start = ?, date_finish = ?, status = ? 
                WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssi", $project_code, $project_name, $date_start, $date_finish, $status, $id);
            $success = mysqli_stmt_execute($stmt);
            
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return $success;
        }
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Xóa project theo ID
 * @param int $id ID của project cần xóa
 * @return bool True nếu thành công, False nếu thất bại
 */
function deleteProject($id) {
    $conn = getDbConnection();
    
    $sql = "DELETE FROM projects WHERE id = ?";
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
 * Lấy danh sách đề tài đã sắp xếp
 * @param string $field Trường để sắp xếp (id, project_code, project_name, date_start, date_finish, status, number_extension)
 * @param string $order Thứ tự sắp xếp (asc hoặc desc)
 * @return array Danh sách đề tài đã sắp xếp
 */
function getSortedProjects($field, $order) {
    $conn = getDbConnection();
    $validFields = ['id', 'project_code', 'project_name', 'date_start', 'date_finish', 'status', 'number_extension'];
    
    // Kiểm tra tính hợp lệ của trường và thứ tự sắp xếp
    if (!in_array($field, $validFields)) {
        $field = 'id';
    }
    
    if ($order !== 'asc' && $order !== 'desc') {
        $order = 'asc';
    }
    
    $sql = "SELECT id, project_code, project_name, date_start, date_finish, status, number_extension, uuid,
                   CASE WHEN file IS NOT NULL THEN 'has_file' ELSE NULL END as file 
            FROM projects ORDER BY $field $order";
    
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
 * Đếm tổng số đề tài
 * @return int Tổng số đề tài
 */
function getTotalProjects() {
    $conn = getDbConnection();
    
    $sql = "SELECT COUNT(*) as total FROM projects";
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
 * Lấy danh sách đề tài có phân trang
 * @param int $page Trang hiện tại (bắt đầu từ 1)
 * @param int|string $limit Số bản ghi trên mỗi trang hoặc 'all'
 * @param string $field Trường để sắp xếp
 * @param string $order Thứ tự sắp xếp
 * @return array Danh sách đề tài
 */
function getProjectsWithPagination($page = 1, $limit = 10, $field = 'id', $order = 'asc') {
    $conn = getDbConnection();
    $validFields = ['id', 'project_code', 'project_name', 'date_start', 'date_finish', 'status', 'number_extension'];
    
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
    $sql = "SELECT id, project_code, project_name, date_start, date_finish, status, number_extension, uuid,
                   CASE WHEN file IS NOT NULL THEN 'has_file' ELSE NULL END as file  
            FROM projects 
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
 * Tìm kiếm đề tài với phân trang
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
 * @return array Danh sách đề tài
 */
function searchProjectsWithPagination($page = 1, $limit = 10, $field = 'id', $order = 'asc', $projectCode = '', $projectName = '', $dateStart = '', $dateFinish = '', $status = '', $numberExtension = '') {
    $conn = getDbConnection();
    
    // Xây dựng câu truy vấn với điều kiện tìm kiếm
    $sql = "SELECT id, project_code, project_name, date_start, date_finish, status, number_extension, uuid,
                   CASE WHEN file IS NOT NULL THEN 'has_file' ELSE NULL END as file 
            FROM projects WHERE 1=1";
    $params = [];
    $types = "";
    
    // Thêm điều kiện tìm kiếm
    if (!empty($projectCode)) {
        $sql .= " AND project_code LIKE ?";
        $params[] = "%" . $projectCode . "%";
        $types .= "s";
    }
    
    if (!empty($projectName)) {
        $sql .= " AND project_name LIKE ?";
        $params[] = "%" . $projectName . "%";
        $types .= "s";
    }
    
    if (!empty($dateStart)) {
        $sql .= " AND date_start >= ?";
        $params[] = $dateStart;
        $types .= "s";
    }
    
    if (!empty($dateFinish)) {
        $sql .= " AND date_finish <= ?";
        $params[] = $dateFinish;
        $types .= "s";
    }
    
    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($numberExtension)) {
        $sql .= " AND number_extension = ?";
        $params[] = (int)$numberExtension;
        $types .= "i";
    }
    
    // Thêm sắp xếp
    $validFields = ['id', 'project_code', 'project_name', 'date_start', 'date_finish', 'status', 'number_extension'];
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
    $allProjects = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $allProjects[] = $row;
        }
    }
    
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
    
    // Áp dụng phân trang
    $totalResults = count($allProjects);
    
    if ($limit === 'all') {
        return $allProjects;
    } else {
        $limit = (int)$limit;
        if ($limit < 1) {
            $limit = 10;
        }
        
        $offset = ($page - 1) * $limit;
        return array_slice($allProjects, $offset, $limit);
    }
}

/**
 * Đếm tổng số đề tài theo điều kiện tìm kiếm
 * @param string $projectCode Mã đề tài tìm kiếm
 * @param string $projectName Tên đề tài tìm kiếm
 * @param string $dateStart Ngày bắt đầu tìm kiếm
 * @param string $dateFinish Ngày kết thúc tìm kiếm
 * @param string $status Trạng thái tìm kiếm
 * @param string $numberExtension Số lần gia hạn tìm kiếm
 * @return int Tổng số đề tài
 */
function getTotalProjectsWithSearch($projectCode = '', $projectName = '', $dateStart = '', $dateFinish = '', $status = '', $numberExtension = '') {
    $conn = getDbConnection();
    
    // Xây dựng câu truy vấn đếm
    $sql = "SELECT COUNT(*) as total FROM projects WHERE 1=1";
    $params = [];
    $types = "";
    
    // Thêm điều kiện tìm kiếm
    if (!empty($projectCode)) {
        $sql .= " AND project_code LIKE ?";
        $params[] = "%" . $projectCode . "%";
        $types .= "s";
    }
    
    if (!empty($projectName)) {
        $sql .= " AND project_name LIKE ?";
        $params[] = "%" . $projectName . "%";
        $types .= "s";
    }
    
    if (!empty($dateStart)) {
        $sql .= " AND date_start >= ?";
        $params[] = $dateStart;
        $types .= "s";
    }
    
    if (!empty($dateFinish)) {
        $sql .= " AND date_finish <= ?";
        $params[] = $dateFinish;
        $types .= "s";
    }
    
    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($numberExtension)) {
        $sql .= " AND number_extension = ?";
        $params[] = (int)$numberExtension;
        $types .= "i";
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

/**
 * Lấy danh sách giảng viên đang hướng dẫn đề tài cụ thể
 * @param int $project_id ID của đề tài
 * @return array Danh sách giảng viên với thống kê
 */
function getTeachersByProject($project_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT DISTINCT t.id, t.teacher_code, t.teacher_name,
            (SELECT COUNT(DISTINCT pd2.project_id) 
             FROM project_details pd2 
             WHERE pd2.teacher_id = t.id) as total_projects
            FROM teachers t
            INNER JOIN project_details pd ON t.id = pd.teacher_id
            WHERE pd.project_id = ?
            ORDER BY t.teacher_code ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    $teachers = [];
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $project_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $teachers[] = $row;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $teachers;
}

/**
 * Lấy danh sách sinh viên đang tham gia đề tài cụ thể
 * @param int $project_id ID của đề tài
 * @return array Danh sách sinh viên với thống kê
 */
function getStudentsByProject($project_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT DISTINCT s.id, s.student_code, s.student_name, pd.student_role, pd.id as project_detail_id,
            (SELECT COUNT(DISTINCT pd2.project_id) 
             FROM project_details pd2 
             WHERE pd2.student_id = s.id) as total_projects
            FROM students s
            INNER JOIN project_details pd ON s.id = pd.student_id
            WHERE pd.project_id = ?
            ORDER BY s.student_code ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    $students = [];
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $project_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = $row;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $students;
}

/**
 * Lấy danh sách đề tài mà giảng viên đang hướng dẫn
 * @param int $teacher_id ID của giảng viên
 * @return array Danh sách đề tài với thống kê
 */
function getProjectsByTeacher($teacher_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT DISTINCT p.id, p.project_code, p.project_name,
            (SELECT COUNT(DISTINCT pd2.teacher_id) 
             FROM project_details pd2 
             WHERE pd2.project_id = p.id) as total_teachers,
            (SELECT COUNT(DISTINCT pd2.student_id) 
             FROM project_details pd2 
             WHERE pd2.project_id = p.id) as total_students
            FROM projects p
            INNER JOIN project_details pd ON p.id = pd.project_id
            WHERE pd.teacher_id = ?
            ORDER BY p.project_code ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    $projects = [];
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $teacher_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $projects[] = $row;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $projects;
}

/**
 * Lấy danh sách đề tài mà sinh viên đang tham gia
 * @param int $student_id ID của sinh viên
 * @return array Danh sách đề tài với thống kê
 */
function getProjectsByStudent($student_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT DISTINCT p.id, p.project_code, p.project_name,
            (SELECT COUNT(DISTINCT pd2.teacher_id) 
             FROM project_details pd2 
             WHERE pd2.project_id = p.id) as total_teachers,
            (SELECT COUNT(DISTINCT pd2.student_id) 
             FROM project_details pd2 
             WHERE pd2.project_id = p.id) as total_students
            FROM projects p
            INNER JOIN project_details pd ON p.id = pd.project_id
            WHERE pd.student_id = ?
            ORDER BY p.project_code ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    $projects = [];
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $projects[] = $row;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $projects;
}

/**
 * Lấy danh sách đề tài mà giảng viên và sinh viên cùng tham gia
 * @param int $teacher_id ID của giảng viên
 * @param int $student_id ID của sinh viên
 * @return array Danh sách đề tài với thống kê
 */
function getProjectsByTeacherAndStudent($teacher_id, $student_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT DISTINCT p.id, p.project_code, p.project_name,
            (SELECT COUNT(DISTINCT pd2.teacher_id) 
             FROM project_details pd2 
             WHERE pd2.project_id = p.id) as total_teachers,
            (SELECT COUNT(DISTINCT pd2.student_id) 
             FROM project_details pd2 
             WHERE pd2.project_id = p.id) as total_students
            FROM projects p
            WHERE EXISTS (
                SELECT 1 FROM project_details pd1 
                WHERE pd1.project_id = p.id AND pd1.teacher_id = ?
            )
            AND EXISTS (
                SELECT 1 FROM project_details pd2 
                WHERE pd2.project_id = p.id AND pd2.student_id = ?
            )
            ORDER BY p.project_code ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    $projects = [];
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $teacher_id, $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $projects[] = $row;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $projects;
}

/**
 * Kiểm tra xem bộ (project_id, teacher_id, student_id) đã tồn tại trong project_details hay chưa
 * @param int $project_id ID của đề tài
 * @param int $teacher_id ID của giảng viên
 * @param int $student_id ID của sinh viên
 * @return bool True nếu đã tồn tại, False nếu chưa tồn tại
 */
function checkProjectDetailExists($project_id, $teacher_id, $student_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT COUNT(*) as count 
            FROM project_details 
            WHERE project_id = ? AND teacher_id = ? AND student_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    $exists = false;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iii", $project_id, $teacher_id, $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $exists = ($row['count'] > 0);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $exists;
}
?>
