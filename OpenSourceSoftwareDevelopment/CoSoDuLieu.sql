-- Tạo cơ sở dữ liệu
DROP DATABASE IF EXISTS quanly_detainghiencuukhoahoc;
CREATE DATABASE quanly_detainghiencuukhoahoc;
USE quanly_detainghiencuukhoahoc;

-- Tạo bảng người dùng (users)
CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) COLLATE ascii_general_ci NOT NULL UNIQUE,
    password VARCHAR(100) COLLATE ascii_general_ci NOT NULL,
    role VARCHAR(10) NOT NULL,
    CONSTRAINT chk_role CHECK (role IN ('admin', 'student', 'teacher')),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Tạo bảng giảng viên (teachers)
CREATE TABLE teachers (
    id INT NOT NULL AUTO_INCREMENT,
    teacher_code VARCHAR(15) NOT NULL UNIQUE,
    teacher_name NVARCHAR(30) NOT NULL,
    teacher_email NVARCHAR(50) NOT NULL UNIQUE,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng sinh viên (students)
CREATE TABLE students (
    id INT NOT NULL AUTO_INCREMENT,
    student_code VARCHAR(15) NOT NULL UNIQUE,
    student_name NVARCHAR(30) NOT NULL,
    student_email NVARCHAR(50) NOT NULL UNIQUE,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng đề tài (projects)
CREATE TABLE projects (
    id INT NOT NULL AUTO_INCREMENT,
    project_code VARCHAR(15) NOT NULL UNIQUE,
    project_name NVARCHAR(250) NOT NULL UNIQUE,
    date_created DATETIME NOT NULL,
    date_start DATE NOT NULL,
    date_finish DATE NOT NULL,
    status ENUM('Not started', 'In progress', 'Completed', 'Canceled', 'Pending extension') NOT NULL,
    number_extension INT NOT NULL DEFAULT 2 CHECK (number_extension >= 0),
    uuid VARCHAR(255) NOT NULL UNIQUE,
    file MEDIUMBLOB NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_uuid (uuid),
    CONSTRAINT check_dates CHECK (date_finish >= date_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng thông tin chi tiết đề tài (project_details)
CREATE TABLE project_details (
    id INT NOT NULL AUTO_INCREMENT,
    project_id INT NOT NULL,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    student_role ENUM('Member', 'Leader') NOT NULL DEFAULT 'Member',
    PRIMARY KEY (id),
    UNIQUE KEY unique_project_teacher_student (project_id, teacher_id, student_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng lịch sử gia hạn đề tài (project_extensions)
CREATE TABLE project_extensions (
    id INT NOT NULL AUTO_INCREMENT,
    project_id INT NOT NULL,
    time DATETIME NOT NULL,
    datefinish_before DATE NOT NULL,
    datefinish_after DATE NOT NULL,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    file MEDIUMBLOB NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_uuid (uuid),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT check_extension_dates CHECK (datefinish_after >= datefinish_before)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng lịch sử cấp kinh phí cho đề tài (project_disbursements)
CREATE TABLE project_disbursements (
    id INT NOT NULL AUTO_INCREMENT,
    project_id INT NOT NULL,
    time DATETIME NOT NULL,
    fund BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT check_fund CHECK (fund >= 1000000)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng lịch sử báo cáo của đề tài (project_presentations)
CREATE TABLE project_presentations (
    id INT NOT NULL AUTO_INCREMENT,
    title NVARCHAR(250) NOT NULL,
    project_id INT NOT NULL,
    time DATETIME NOT NULL,
    score FLOAT CHECK (score >= 0),
    PRIMARY KEY (id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng thông tin chi tiết chấm nghiên cứu khoa học (project_presentation_details)
CREATE TABLE project_presentation_details (
    id INT NOT NULL AUTO_INCREMENT,
    project_presentation_id INT NOT NULL,
    teacher_id INT NOT NULL,
    score FLOAT CHECK (score >= 0),
    PRIMARY KEY (id),
    UNIQUE KEY unique_presentation_teacher (project_presentation_id, teacher_id),
    FOREIGN KEY (project_presentation_id) REFERENCES project_presentations(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chèn dữ liệu mẫu vào bảng người dùng (users)
INSERT INTO users (username, password, role) VALUES
('admin', 'admin', 'admin');

-- Chèn dữ liệu mẫu vào bảng giảng viên (teachers)
INSERT INTO teachers (teacher_code, teacher_name, teacher_email) VALUES
('GV001', 'Nguyễn Văn An', 'an.nguyenvan@edu.vn'),
('GV002', 'Trần Thị Bình', 'binh.tranthi@edu.vn'),
('GV003', 'Lê Hoàng Cường', 'cuong.lehoang@edu.vn'),
('GV004', 'Phạm Minh Đức', 'duc.phamminh@edu.vn'),
('GV005', 'Võ Thị Em', 'em.vothi@edu.vn'),
('GV006', 'Đỗ Văn Giang', 'giang.dovan@edu.vn'),
('GV007', 'Nguyễn Thị Hương', 'huong.nguyenthi@edu.vn'),
('GV008', 'Lý Văn Khánh', 'khanh.lyvan@edu.vn'),
('GV009', 'Bùi Thị Lan', 'lan.buithi@edu.vn'),
('GV010', 'Hoàng Minh Nam', 'nam.hoangminh@edu.vn');

-- Chèn dữ liệu mẫu vào bảng sinh viên (students)
INSERT INTO students (student_code, student_name, student_email) VALUES
('SV001', 'Nguyễn Anh Tuấn', 'tuan.nguyenanh@student.edu.vn'),
('SV002', 'Lê Thị Bích Ngọc', 'ngoc.lethibich@student.edu.vn'),
('SV003', 'Trần Văn Cường', 'cuong.tranvan@student.edu.vn'),
('SV004', 'Phạm Thị Diệp', 'diep.phamthi@student.edu.vn'),
('SV005', 'Hoàng Văn Em', 'em.hoangvan@student.edu.vn'),
('SV006', 'Vũ Thị Giang', 'giang.vuthi@student.edu.vn'),
('SV007', 'Đặng Văn Hải', 'hai.dangvan@student.edu.vn'),
('SV008', 'Ngô Thị Kim', 'kim.ngothi@student.edu.vn'),
('SV009', 'Đỗ Văn Lâm', 'lam.dovan@student.edu.vn'),
('SV010', 'Phan Thị Mai', 'mai.phanthi@student.edu.vn'),
('SV011', 'Lý Văn Nam', 'nam.lyvan@student.edu.vn'),
('SV012', 'Bùi Thị Oanh', 'oanh.buithi@student.edu.vn'),
('SV013', 'Huỳnh Văn Phú', 'phu.huynhvan@student.edu.vn'),
('SV014', 'Đinh Thị Quỳnh', 'quynh.dinhthi@student.edu.vn'),
('SV015', 'Đoàn Văn Sơn', 'son.doanvan@student.edu.vn'),
('SV016', 'Mai Thị Trang', 'trang.maithi@student.edu.vn'),
('SV017', 'Chu Văn Uy', 'uy.chuvan@student.edu.vn'),
('SV018', 'Dương Thị Vân', 'van.duongthi@student.edu.vn'),
('SV019', 'Cao Văn Xuân', 'xuan.caovan@student.edu.vn'),
('SV020', 'Tô Thị Yến', 'yen.tothi@student.edu.vn');

-- Chèn dữ liệu mẫu vào bảng đề tài (projects) - Với file PDF từ Uploads
INSERT INTO projects (project_code, project_name, date_created, date_start, date_finish, status, number_extension, uuid, file) VALUES
('DT001', 'Nghiên cứu ứng dụng trí tuệ nhân tạo trong nhận diện khuôn mặt', '2024-05-15 10:30:00', '2024-06-01', '2025-01-15', 'In progress', 2, '550e8400-e29b-41d4-a716-446655440001', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT002', 'Phát triển ứng dụng học máy cho dự đoán thị trường chứng khoán', '2024-06-20 09:15:00', '2024-07-01', '2025-02-28', 'In progress', 1, '550e8400-e29b-41d4-a716-446655440002', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT003', 'Nghiên cứu công nghệ blockchain trong bảo mật dữ liệu y tế', '2024-02-10 14:45:00', '2024-03-01', '2024-12-31', 'In progress', 2, '550e8400-e29b-41d4-a716-446655440003', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT004', 'Xây dựng hệ thống phân tích dữ liệu lớn cho nông nghiệp thông minh', '2024-01-05 11:20:00', '2024-02-01', '2024-08-31', 'Completed', 0, '550e8400-e29b-41d4-a716-446655440004', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT005', 'Phát triển thuật toán tối ưu cho bài toán định tuyến trong mạng không dây', '2024-04-18 08:30:00', '2024-05-01', '2025-04-30', 'In progress', 2, '550e8400-e29b-41d4-a716-446655440005', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT006', 'Nghiên cứu giải pháp IoT cho nhà thông minh tiết kiệm năng lượng', '2023-12-12 13:10:00', '2024-01-15', '2024-07-15', 'Completed', 0, '550e8400-e29b-41d4-a716-446655440006', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT007', 'Phát triển phương pháp học sâu cho nhận dạng hình ảnh y khoa', '2024-07-01 15:45:00', '2024-08-01', '2025-07-31', 'Not started', 2, '550e8400-e29b-41d4-a716-446655440007', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT008', 'Nghiên cứu công nghệ xử lý ngôn ngữ tự nhiên cho chatbot tiếng Việt', '2024-03-22 09:50:00', '2024-04-15', '2025-03-31', 'In progress', 2, '550e8400-e29b-41d4-a716-446655440008', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT009', 'Xây dựng hệ thống quản lý nguồn nước thông minh sử dụng IoT', '2023-11-05 10:25:00', '2023-12-01', '2024-06-30', 'Completed', 0, '550e8400-e29b-41d4-a716-446655440009', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT010', 'Nghiên cứu giải pháp thực tế ảo trong giáo dục đại học', '2024-06-10 14:20:00', '2024-07-01', '2025-06-30', 'Not started', 2, '550e8400-e29b-41d4-a716-446655440010', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT011', 'Phát triển ứng dụng di động hỗ trợ người khiếm thị', '2024-02-18 11:15:00', '2024-03-01', '2024-11-30', 'In progress', 1, '550e8400-e29b-41d4-a716-446655440011', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT012', 'Nghiên cứu phương pháp phát hiện gian lận trong thương mại điện tử', '2024-05-20 16:30:00', '2024-06-15', '2025-05-31', 'In progress', 2, '550e8400-e29b-41d4-a716-446655440012', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT013', 'Xây dựng hệ thống khuyến nghị thông minh cho thư viện số', '2024-01-30 13:45:00', '2024-02-15', '2024-10-15', 'Canceled', 0, '550e8400-e29b-41d4-a716-446655440013', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT014', 'Nghiên cứu ứng dụng dữ liệu lớn trong dự báo thời tiết', '2023-10-15 09:30:00', '2023-11-01', '2024-05-31', 'Completed', 0, '550e8400-e29b-41d4-a716-446655440014', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT015', 'Phát triển hệ thống giám sát an ninh thông minh dựa trên AI', '2024-04-02 10:15:00', '2024-05-01', '2025-04-30', 'In progress', 2, '550e8400-e29b-41d4-a716-446655440015', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT016', 'Nghiên cứu công nghệ in 3D trong y học tái tạo', '2024-07-10 11:00:00', '2024-08-01', '2025-07-31', 'Not started', 2, '550e8400-e29b-41d4-a716-446655440016', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT017', 'Xây dựng hệ thống phát hiện mã độc sử dụng học máy', '2024-03-15 14:30:00', '2024-04-01', '2025-01-31', 'In progress', 2, '550e8400-e29b-41d4-a716-446655440017', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT018', 'Nghiên cứu phương pháp học liên tục trong trí tuệ nhân tạo', '2024-06-25 15:40:00', '2024-07-15', '2025-06-30', 'Not started', 2, '550e8400-e29b-41d4-a716-446655440018', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT019', 'Phát triển giải pháp quản lý chuỗi cung ứng bằng blockchain', '2023-12-05 08:45:00', '2024-01-10', '2024-07-10', 'Pending extension', 1, '550e8400-e29b-41d4-a716-446655440019', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf')),
('DT020', 'Nghiên cứu công nghệ thực tế tăng cường trong bảo tàng số', '2024-04-30 13:15:00', '2024-05-15', '2025-04-15', 'In progress', 2, '550e8400-e29b-41d4-a716-446655440020', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinDangKy.pdf'));

-- Chèn dữ liệu mẫu vào bảng thông tin chi tiết đề tài (project_details)
INSERT INTO project_details (project_id, teacher_id, student_id, student_role) VALUES
-- Đề tài 1
(1, 1, 1, 'Leader'),
(1, 1, 2, 'Member'),
(1, 1, 3, 'Member'),
-- Đề tài 2
(2, 2, 4, 'Leader'),
(2, 2, 5, 'Member'),
(2, 2, 6, 'Member'),
-- Đề tài 3
(3, 3, 7, 'Leader'),
(3, 3, 8, 'Member'),
(3, 3, 9, 'Member'),
-- Đề tài 4
(4, 4, 10, 'Leader'),
(4, 4, 11, 'Member'),
-- Đề tài 5
(5, 5, 12, 'Leader'),
(5, 5, 13, 'Member'),
(5, 5, 14, 'Member'),
-- Đề tài 6
(6, 6, 15, 'Leader'),
(6, 6, 16, 'Member'),
-- Đề tài 7
(7, 7, 17, 'Leader'),
(7, 7, 18, 'Member'),
-- Đề tài 8
(8, 8, 19, 'Leader'),
(8, 8, 20, 'Member'),
-- Đề tài 9
(9, 9, 1, 'Leader'),
(9, 9, 3, 'Member'),
-- Đề tài 10
(10, 10, 2, 'Leader'),
(10, 10, 4, 'Member'),
(10, 10, 6, 'Member'),
-- Đề tài 11
(11, 1, 5, 'Leader'),
(11, 1, 7, 'Member'),
-- Đề tài 12
(12, 2, 8, 'Leader'),
(12, 2, 10, 'Member'),
(12, 2, 12, 'Member'),
-- Đề tài 13
(13, 3, 9, 'Leader'),
(13, 3, 11, 'Member'),
-- Đề tài 14
(14, 4, 13, 'Leader'),
(14, 4, 15, 'Member'),
(14, 4, 17, 'Member'),
-- Đề tài 15
(15, 5, 14, 'Leader'),
(15, 5, 16, 'Member'),
-- Đề tài 16
(16, 6, 18, 'Leader'),
(16, 6, 20, 'Member'),
-- Đề tài 17
(17, 7, 19, 'Leader'),
(17, 7, 1, 'Member'),
-- Đề tài 18
(18, 8, 2, 'Leader'),
(18, 8, 4, 'Member'),
-- Đề tài 19
(19, 9, 3, 'Leader'),
(19, 9, 5, 'Member'),
(19, 9, 7, 'Member'),
-- Đề tài 20
(20, 10, 6, 'Leader'),
(20, 10, 8, 'Member'),
(20, 10, 10, 'Member');

-- Chèn dữ liệu mẫu vào bảng lịch sử gia hạn đề tài (project_extensions)
INSERT INTO project_extensions (project_id, time, datefinish_before, datefinish_after, uuid, file) VALUES
-- Đề tài 2 gia hạn 1 lần
(2, '2024-12-15 09:30:00', '2025-01-31', '2025-02-28', '650e8400-e29b-41d4-a716-446655440001', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinGiaHan.pdf')),
-- Đề tài 11 gia hạn 1 lần
(11, '2024-09-20 14:15:00', '2024-10-31', '2024-11-30', '650e8400-e29b-41d4-a716-446655440002', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinGiaHan.pdf')),
-- Đề tài 19 gia hạn 1 lần và đang chờ xét gia hạn lần 2
(19, '2024-05-25 10:45:00', '2024-06-10', '2024-07-10', '650e8400-e29b-41d4-a716-446655440003', LOAD_FILE('C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/DeTaiNghienCuuKhoaHoc_DonXinGiaHan.pdf'));

-- Chèn dữ liệu mẫu vào bảng lịch sử cấp kinh phí cho đề tài (project_disbursements)
INSERT INTO project_disbursements (project_id, time, fund) VALUES
-- Đề tài 1: 2 lần cấp kinh phí
(1, '2024-06-05 14:30:00', 5000000),
(1, '2024-09-15 10:20:00', 10000000),
-- Đề tài 2: 1 lần cấp kinh phí
(2, '2024-07-10 09:45:00', 15000000),
-- Đề tài 3: 3 lần cấp kinh phí
(3, '2024-03-15 11:30:00', 8000000),
(3, '2024-06-20 13:45:00', 7000000),
(3, '2024-09-10 15:15:00', 5000000),
-- Đề tài 4: 2 lần cấp kinh phí (đã hoàn thành)
(4, '2024-02-12 10:10:00', 12000000),
(4, '2024-05-22 14:25:00', 8000000),
-- Đề tài 5: 1 lần cấp kinh phí
(5, '2024-05-10 09:15:00', 20000000),
-- Đề tài 6: 2 lần cấp kinh phí (đã hoàn thành)
(6, '2024-01-25 13:30:00', 10000000),
(6, '2024-04-18 11:45:00', 5000000),
-- Đề tài 8: 2 lần cấp kinh phí
(8, '2024-04-25 10:30:00', 7000000),
(8, '2024-07-15 13:15:00', 8000000),
-- Đề tài 9: 1 lần cấp kinh phí (đã hoàn thành)
(9, '2023-12-10 09:30:00', 15000000),
-- Đề tài 11: 2 lần cấp kinh phí
(11, '2024-03-12 14:40:00', 6000000),
(11, '2024-06-25 11:10:00', 9000000),
-- Đề tài 12: 1 lần cấp kinh phí
(12, '2024-06-30 09:45:00', 12000000),
-- Đề tài 14: 2 lần cấp kinh phí (đã hoàn thành)
(14, '2023-11-15 10:15:00', 10000000),
(14, '2024-02-20 14:30:00', 8000000),
-- Đề tài 15: 2 lần cấp kinh phí
(15, '2024-05-12 11:20:00', 15000000),
(15, '2024-08-18 13:40:00', 10000000),
-- Đề tài 17: 1 lần cấp kinh phí
(17, '2024-04-15 10:10:00', 18000000),
-- Đề tài 19: 1 lần cấp kinh phí (đang chờ gia hạn)
(19, '2024-01-25 09:25:00', 14000000),
-- Đề tài 20: 2 lần cấp kinh phí
(20, '2024-06-01 14:15:00', 7000000),
(20, '2024-08-15 10:50:00', 8000000);

-- Chèn dữ liệu mẫu vào bảng lịch sử báo cáo của đề tài (project_presentations)
INSERT INTO project_presentations (title, project_id, time, score) VALUES
-- Đề tài 1: 2 lần báo cáo
('Báo cáo tiến độ giữa kỳ đề tài nghiên cứu AI nhận diện khuôn mặt', 1, '2024-08-20 09:30:00', 8.5),
('Báo cáo cập nhật thuật toán mới cho đề tài nhận diện khuôn mặt', 1, '2024-11-15 14:15:00', 9.0),
-- Đề tài 2: 1 lần báo cáo
('Báo cáo tiến độ giữa kỳ về ứng dụng học máy cho thị trường chứng khoán', 2, '2024-10-05 10:45:00', 7.8),
-- Đề tài 3: 2 lần báo cáo
('Báo cáo tiến độ nghiên cứu blockchain trong y tế', 3, '2024-06-25 13:30:00', 8.2),
('Báo cáo kết quả thử nghiệm hệ thống blockchain y tế', 3, '2024-10-10 11:00:00', 8.7),
-- Đề tài 4: 3 lần báo cáo (đã hoàn thành)
('Báo cáo tiến độ hệ thống phân tích dữ liệu nông nghiệp', 4, '2024-03-15 10:15:00', 7.5),
('Báo cáo kết quả thử nghiệm thực địa', 4, '2024-06-20 14:30:00', 8.3),
('Báo cáo tổng kết đề tài phân tích dữ liệu nông nghiệp', 4, '2024-08-25 09:45:00', 9.2),
-- Đề tài 5: 1 lần báo cáo
('Báo cáo tiến độ thuật toán tối ưu định tuyến mạng không dây', 5, '2024-09-10 11:30:00', 8.0),
-- Đề tài 6: 2 lần báo cáo (đã hoàn thành)
('Báo cáo giữa kỳ về giải pháp IoT nhà thông minh', 6, '2024-04-12 10:30:00', 8.4),
('Báo cáo tổng kết đề tài IoT nhà thông minh', 6, '2024-07-10 13:45:00', 9.5),
-- Đề tài 8: 1 lần báo cáo
('Báo cáo tiến độ xây dựng chatbot tiếng Việt', 8, '2024-08-15 14:00:00', 7.9),
-- Đề tài 9: 2 lần báo cáo (đã hoàn thành)
('Báo cáo giữa kỳ hệ thống quản lý nguồn nước thông minh', 9, '2024-02-20 09:15:00', 8.1),
('Báo cáo tổng kết đề tài quản lý nguồn nước', 9, '2024-06-25 11:30:00', 8.8),
-- Đề tài 11: 1 lần báo cáo
('Báo cáo tiến độ ứng dụng di động hỗ trợ người khiếm thị', 11, '2024-06-15 10:00:00', 8.6),
-- Đề tài 12: 1 lần báo cáo
('Báo cáo giữa kỳ phương pháp phát hiện gian lận thương mại điện tử', 12, '2024-10-20 13:15:00', 7.7),
-- Đề tài 14: 2 lần báo cáo (đã hoàn thành)
('Báo cáo giữa kỳ ứng dụng dữ liệu lớn trong dự báo thời tiết', 14, '2024-02-15 11:30:00', 8.3),
('Báo cáo tổng kết đề tài dự báo thời tiết', 14, '2024-05-25 14:45:00', 9.1),
-- Đề tài 15: 1 lần báo cáo
('Báo cáo tiến độ hệ thống giám sát an ninh thông minh', 15, '2024-09-05 10:30:00', 8.2),
-- Đề tài 17: 1 lần báo cáo
('Báo cáo tiến độ hệ thống phát hiện mã độc', 17, '2024-08-10 09:45:00', 7.8),
-- Đề tài 19: 1 lần báo cáo (đang chờ gia hạn)
('Báo cáo tiến độ giải pháp quản lý chuỗi cung ứng', 19, '2024-04-18 13:30:00', 8.0),
-- Đề tài 20: 1 lần báo cáo
('Báo cáo giữa kỳ công nghệ thực tế tăng cường trong bảo tàng số', 20, '2024-09-25 11:15:00', 8.5);

-- Bật chức năng sự kiện
SET GLOBAL event_scheduler = ON;

-- Tạo sự kiện cập nhật trạng thái đề tài hàng ngày
DELIMITER //
CREATE EVENT IF NOT EXISTS update_project_status_daily
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE
DO
BEGIN
    -- Cập nhật trạng thái từ Not started sang In progress khi đến ngày bắt đầu
    UPDATE projects
    SET status = 'In progress'
    WHERE status = 'Not started' AND CURDATE() >= date_start;
    
    -- Cập nhật trạng thái sang Completed khi hết hạn
    UPDATE projects
    SET status = 'Completed'
    WHERE status IN ('In progress', 'Pending extension') 
    AND CURDATE() > date_finish;
END //
DELIMITER ;

-- Chèn dữ liệu mẫu vào bảng thông tin chi tiết chấm nghiên cứu khoa học (project_presentation_details)
INSERT INTO project_presentation_details (project_presentation_id, teacher_id, score) VALUES
-- Báo cáo 1 của đề tài 1: 3 giảng viên chấm
(1, 1, 8.5),
(1, 2, 8.0),
(1, 3, 9.0),
-- Báo cáo 2 của đề tài 1: 3 giảng viên chấm
(2, 1, 9.0),
(2, 2, 8.5),
(2, 4, 9.5),
-- Báo cáo 1 của đề tài 2: 3 giảng viên chấm
(3, 2, 8.0),
(3, 3, 7.5),
(3, 5, 8.0),
-- Báo cáo 1 của đề tài 3: 3 giảng viên chấm
(4, 3, 8.5),
(4, 4, 8.0),
(4, 6, 8.0),
-- Báo cáo 2 của đề tài 3: 3 giảng viên chấm
(5, 3, 9.0),
(5, 5, 8.5),
(5, 7, 8.5),
-- Báo cáo 1 của đề tài 4: 3 giảng viên chấm
(6, 4, 7.5),
(6, 5, 7.0),
(6, 8, 8.0),
-- Báo cáo 2 của đề tài 4: 3 giảng viên chấm
(7, 4, 8.5),
(7, 6, 8.0),
(7, 9, 8.5),
-- Báo cáo 3 của đề tài 4: 3 giảng viên chấm
(8, 4, 9.5),
(8, 7, 9.0),
(8, 10, 9.0),
-- Báo cáo 1 của đề tài 5: 3 giảng viên chấm
(9, 5, 8.0),
(9, 6, 8.0),
(9, 1, 8.0),
-- Báo cáo 1 của đề tài 6: 3 giảng viên chấm
(10, 6, 8.5),
(10, 7, 8.0),
(10, 2, 8.5),
-- Báo cáo 2 của đề tài 6: 3 giảng viên chấm
(11, 6, 9.5),
(11, 8, 9.0),
(11, 3, 10.0),
-- Báo cáo 1 của đề tài 8: 3 giảng viên chấm
(12, 8, 8.0),
(12, 9, 7.5),
(12, 4, 8.0),
-- Báo cáo 1 của đề tài 9: 3 giảng viên chấm
(13, 9, 8.0),
(13, 10, 8.0),
(13, 5, 8.5),
-- Báo cáo 2 của đề tài 9: 3 giảng viên chấm
(14, 9, 9.0),
(14, 1, 8.5),
(14, 6, 9.0),
-- Báo cáo 1 của đề tài 11: 3 giảng viên chấm
(15, 1, 9.0),
(15, 2, 8.0),
(15, 7, 8.5),
-- Báo cáo 1 của đề tài 12: 3 giảng viên chấm
(16, 2, 7.5),
(16, 3, 8.0),
(16, 8, 7.5),
-- Báo cáo 1 của đề tài 14: 3 giảng viên chấm
(17, 4, 8.0),
(17, 5, 8.5),
(17, 9, 8.5),
-- Báo cáo 2 của đề tài 14: 3 giảng viên chấm
(18, 4, 9.0),
(18, 6, 9.0),
(18, 10, 9.5),
-- Báo cáo 1 của đề tài 15: 3 giảng viên chấm
(19, 5, 8.0),
(19, 7, 8.5),
(19, 1, 8.0),
-- Báo cáo 1 của đề tài 17: 3 giảng viên chấm
(20, 7, 8.0),
(20, 8, 7.5),
(20, 2, 8.0),
-- Báo cáo 1 của đề tài 19: 3 giảng viên chấm
(21, 9, 8.0),
(21, 10, 8.0),
(21, 3, 8.0),
-- Báo cáo 1 của đề tài 20: 3 giảng viên chấm
(22, 10, 8.5),
(22, 1, 8.0),
(22, 4, 9.0);
