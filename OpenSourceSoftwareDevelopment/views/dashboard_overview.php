<?php
session_start();
require_once '../functions/db_connection.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$pageTitle = "Dashboard Overview - Quản lý Đề tài Nghiên cứu Khoa học";
$customCSS = ["../css/project_extensions.css"];

// Kết nối database
$conn = getDbConnection();

// Lấy số liệu thống kê cơ bản
$stats = [];

// Đếm số giảng viên
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM teachers");
$stats['teachers'] = mysqli_fetch_assoc($result)['count'];

// Đếm số sinh viên
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM students");
$stats['students'] = mysqli_fetch_assoc($result)['count'];

// Đếm số đề tài
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM projects");
$stats['projects'] = mysqli_fetch_assoc($result)['count'];

// Thống kê đề tài theo trạng thái (hiển thị đầy đủ 5 trạng thái)
$all_statuses = ['Not started', 'In progress', 'Completed', 'Canceled', 'Pending extension'];
$project_status_data = [];

// Khởi tạo tất cả trạng thái với count = 0
foreach ($all_statuses as $status) {
    $project_status_data[$status] = 0;
}

// Lấy dữ liệu thực tế từ database
$project_status_query = "
    SELECT 
        status,
        COUNT(*) as count 
    FROM projects 
    GROUP BY status
";
$project_status_result = mysqli_query($conn, $project_status_query);
while ($row = mysqli_fetch_assoc($project_status_result)) {
    $project_status_data[$row['status']] = $row['count'];
}

// Tính tổng số đề tài để có tỉ lệ
$total_projects = array_sum($project_status_data);

// Tính tỉ lệ và màu sắc cho từng trạng thái
$status_colors = [];
$status_border_colors = [];
foreach ($project_status_data as $status => $count) {
    // Tính tỉ lệ (0-1)
    $ratio = $total_projects > 0 ? $count / $total_projects : 0;
    
    // Màu primary base: #0d6efd (13, 110, 253)
    $primary_r = 13;
    $primary_g = 110; 
    $primary_b = 253;
    
    // Màu nhạt nhất: #e6f2ff (230, 242, 255)
    $light_r = 230;
    $light_g = 242;
    $light_b = 255;
    
    // Tính màu dựa trên tỉ lệ (tỉ lệ cao = gần primary, tỉ lệ thấp = gần màu nhạt)
    $r = round($light_r + ($primary_r - $light_r) * $ratio);
    $g = round($light_g + ($primary_g - $light_g) * $ratio);
    $b = round($light_b + ($primary_b - $light_b) * $ratio);
    
    // Màu border đậm hơn 20%
    $border_r = max(0, $r - 30);
    $border_g = max(0, $g - 30);
    $border_b = max(0, $b - 30);
    
    $status_colors[$status] = "rgb($r, $g, $b)";
    $status_border_colors[$status] = "rgb($border_r, $border_g, $border_b)";
}

// Điểm trung bình các đề tài đã báo cáo
$avg_score_query = "
    SELECT AVG(score) as avg_score 
    FROM project_presentations 
    WHERE score IS NOT NULL
";
$avg_score_result = mysqli_query($conn, $avg_score_query);
$avg_score = mysqli_fetch_assoc($avg_score_result)['avg_score'];

// Top 3 đề tài có điểm cao nhất
$top_projects_query = "
    SELECT 
        p.project_name,
        p.project_code,
        p.id as project_id,
        AVG(pp.score) as avg_score
    FROM projects p
    INNER JOIN project_presentations pp ON p.id = pp.project_id
    WHERE pp.score IS NOT NULL
    GROUP BY p.id, p.project_name, p.project_code
    ORDER BY avg_score DESC
    LIMIT 3
";
$top_projects_result = mysqli_query($conn, $top_projects_query);
$top_projects = [];
while ($row = mysqli_fetch_assoc($top_projects_result)) {
    $top_projects[] = $row;
}

// Top 5 sinh viên tham gia nhiều đề tài nhất
$top_students_query = "
    SELECT 
        s.student_name,
        s.student_code,
        s.id as student_id,
        COUNT(pd.project_id) as project_count
    FROM students s
    LEFT JOIN project_details pd ON s.id = pd.student_id
    GROUP BY s.id, s.student_name, s.student_code
    ORDER BY project_count DESC
    LIMIT 5
";
$top_students_result = mysqli_query($conn, $top_students_query);
$top_students = [];
while ($row = mysqli_fetch_assoc($top_students_result)) {
    $top_students[] = $row;
}

// Top 5 giảng viên hướng dẫn nhiều đề tài nhất
$top_teachers_query = "
    SELECT 
        t.teacher_name,
        t.teacher_code,
        t.id as teacher_id,
        COUNT(DISTINCT pd.project_id) as project_count
    FROM teachers t
    LEFT JOIN project_details pd ON t.id = pd.teacher_id
    GROUP BY t.id, t.teacher_name, t.teacher_code
    ORDER BY project_count DESC
    LIMIT 5
";
$top_teachers_result = mysqli_query($conn, $top_teachers_query);
$top_teachers = [];
while ($row = mysqli_fetch_assoc($top_teachers_result)) {
    $top_teachers[] = $row;
}

// Thống kê dung lượng các bảng
$table_sizes_query = "
    SELECT 
        TABLE_NAME as table_name,
        ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS size_mb,
        TABLE_ROWS as table_rows
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = 'quanly_detainghiencuukhoahoc'
    ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
";
$table_sizes_result = mysqli_query($conn, $table_sizes_query);
$table_sizes = [];
$total_size = 0;
if ($table_sizes_result) {
    while ($row = mysqli_fetch_assoc($table_sizes_result)) {
        $table_sizes[] = $row;
        $total_size += $row['size_mb'];
    }
}

include 'header.php';
?>

<style>
.dashboard-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    font-size: 3rem;
    opacity: 0.8;
}

.chart-container {
    position: relative;
    height: 400px;
    margin: 20px 0;
}

.timeline-item {
    border-left: 3px solid var(--bs-primary);
    padding-left: 20px;
    margin-bottom: 20px;
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 0;
    width: 13px;
    height: 13px;
    border-radius: 50%;
    background: var(--bs-primary);
}

.timeline-item.orange::before {
    background: var(--bs-orange, #fd7e14);
}

.timeline-item.green::before {
    background: var(--bs-success);
}

.eye-icon {
    font-size: 1.2rem;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 5px;
    border-radius: 50%;
}

.eye-icon:hover {
    color: var(--bs-primary);
    background-color: rgba(13, 110, 253, 0.1);
    transform: scale(1.1);
}

.timeline-item-with-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-radius: 8px;
    transition: background-color 0.3s ease;
}

.timeline-item-with-link:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.score-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
    margin: 0 auto;
}

.table-size-bar {
    height: 20px;
    border-radius: 10px;
    background: linear-gradient(90deg, var(--bs-primary), var(--bs-success));
    margin-bottom: 5px;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, var(--bs-primary), #4e73df);
}

.bg-gradient-orange {
    background: linear-gradient(135deg, #fd7e14, #ff9500);
}

.bg-gradient-success {
    background: linear-gradient(135deg, var(--bs-success), #1cc88a);
}

.text-orange {
    color: #fd7e14 !important;
}
</style>

<body>
    <!-- Navigation Menu ở trên -->
    <div class="container-fluid p-0">
        <?php include 'menu.php'; ?>
    </div>

    <!-- Main Content ở dưới, full width -->
    <div class="container-fluid" style="margin-top: 20px;">
        <div class="row">
            <div class="col-12">
                <div class="px-4 py-3">

                    <!-- Thống kê tổng quan -->
                    <div class="row mb-5">
                        <div class="col-md-4 mb-3">
                            <div class="card dashboard-card bg-gradient-success text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-chalkboard-teacher stat-icon mb-3"></i>
                                    <h3 class="mb-1"><?php echo $stats['teachers']; ?></h3>
                                    <p class="mb-0">Giảng viên</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card dashboard-card bg-gradient-primary text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-project-diagram stat-icon mb-3"></i>
                                    <h3 class="mb-1"><?php echo $stats['projects']; ?></h3>
                                    <p class="mb-0">Đề tài nghiên cứu</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card dashboard-card bg-gradient-orange text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-graduate stat-icon mb-3"></i>
                                    <h3 class="mb-1"><?php echo $stats['students']; ?></h3>
                                    <p class="mb-0">Sinh viên</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ trạng thái đề tài và điểm trung bình -->
                    <div class="row mb-5">
                        <div class="col-md-8">
                            <div class="card dashboard-card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Tổng quan trạng thái của đề tài nghiên cứu</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="projectStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card dashboard-card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-star me-2"></i>Điểm trung bình</h5>
                                </div>
                                <div class="card-body text-center">
                                    <div class="score-circle bg-gradient-success mx-auto mb-3">
                                        <?php echo $avg_score ? number_format($avg_score, 1) : 'N/A'; ?>
                                    </div>
                                    <p class="text-muted">Điểm trung bình các đề tài đã báo cáo</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Rankings Row với tỷ lệ 1:2:1 -->
                    <div class="row mb-5">
                        <!-- Top 5 sinh viên tích cực (25%) -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="card dashboard-card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-trophy me-2"></i>Top 5 sinh viên tích cực</h6>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <?php foreach ($top_students as $index => $student): ?>
                                            <div class="timeline-item <?php echo $index % 3 == 0 ? '' : ($index % 3 == 1 ? 'orange' : 'green'); ?>">
                                                <div class="timeline-item-with-link">
                                                    <div>
                                                        <h6 class="mb-1 text-primary"><?php echo htmlspecialchars($student['student_name']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($student['student_code']); ?></small>
                                                        <div class="mt-1">
                                                            <span class="badge bg-primary rounded-pill"><?php echo $student['project_count']; ?> đề tài</span>
                                                        </div>
                                                    </div>
                                                    <i class="bi bi-eye eye-icon" 
                                                       onclick="window.open('student/edit_student.php?id=<?php echo $student['student_id']; ?>', '_blank')"
                                                       onmouseover="this.className='bi bi-eye-fill eye-icon'"
                                                       onmouseout="this.className='bi bi-eye eye-icon'">
                                                    </i>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top 3 đề tài xuất sắc (50%) -->
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="card dashboard-card h-100">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-star me-2"></i>Top 3 đề tài có chất lượng cao nhất</h6>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <?php foreach ($top_projects as $index => $project): ?>
                                            <div class="timeline-item green">
                                                <div class="timeline-item-with-link">
                                                    <div>
                                                        <h6 class="mb-1 text-success"><?php echo htmlspecialchars($project['project_name']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($project['project_code']); ?></small>
                                                        <div class="mt-1">
                                                            <span class="badge bg-success rounded-pill"><?php echo number_format($project['avg_score'], 1); ?> điểm</span>
                                                        </div>
                                                    </div>
                                                    <i class="bi bi-eye eye-icon" 
                                                       onclick="window.open('project/edit_project.php?id=<?php echo $project['project_id']; ?>', '_blank')"
                                                       onmouseover="this.className='bi bi-eye-fill eye-icon'"
                                                       onmouseout="this.className='bi bi-eye eye-icon'">
                                                    </i>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top 5 giảng viên hướng dẫn (25%) -->
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="card dashboard-card h-100">
                                <div class="card-header text-white" style="background: #fd7e14;">
                                    <h6 class="mb-0"><i class="fas fa-award me-2"></i>Top 5 giảng viên tích cực nhất</h6>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <?php foreach ($top_teachers as $index => $teacher): ?>
                                            <div class="timeline-item <?php echo $index % 3 == 0 ? '' : ($index % 3 == 1 ? 'orange' : 'green'); ?>">
                                                <div class="timeline-item-with-link">
                                                    <div>
                                                        <h6 class="mb-1 text-primary"><?php echo htmlspecialchars($teacher['teacher_name']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($teacher['teacher_code']); ?></small>
                                                        <div class="mt-1">
                                                            <span class="badge rounded-pill" style="background: #fd7e14;"><?php echo $teacher['project_count']; ?> đề tài</span>
                                                        </div>
                                                    </div>
                                                    <i class="bi bi-eye eye-icon" 
                                                       onclick="window.open('teacher/edit_teacher.php?id=<?php echo $teacher['teacher_id']; ?>', '_blank')"
                                                       onmouseover="this.className='bi bi-eye-fill eye-icon'"
                                                       onmouseout="this.className='bi bi-eye eye-icon'">
                                                    </i>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê dung lượng database -->
                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="card dashboard-card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-database me-2"></i>Thống kê Dung lượng Database</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h4 class="text-success"><?php echo number_format($total_size, 2); ?> MB</h4>
                                                <p class="text-muted">Tổng dung lượng</p>
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead class="table-primary">
                                                        <tr>
                                                            <th>Tên bảng</th>
                                                            <th>Số dòng</th>
                                                            <th>Dung lượng (MB)</th>
                                                            <th>Tỷ lệ (%)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($table_sizes as $table): ?>
                                                            <tr>
                                                                <td><strong><?php echo htmlspecialchars($table['table_name']); ?></strong></td>
                                                                <td><?php echo number_format($table['table_rows']); ?></td>
                                                                <td><?php echo number_format($table['size_mb'], 2); ?></td>
                                                                <td>
                                                                    <div class="progress" style="height: 10px;">
                                                                        <div class="progress-bar bg-success" 
                                                                             style="width: <?php echo $total_size > 0 ? ($table['size_mb'] / $total_size * 100) : 0; ?>%">
                                                                        </div>
                                                                    </div>
                                                                    <small><?php echo $total_size > 0 ? number_format($table['size_mb'] / $total_size * 100, 1) : 0; ?>%</small>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Biểu đồ cột trạng thái đề tài
        const ctx = document.getElementById('projectStatusChart').getContext('2d');
        const projectStatusChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    'Not started',
                    'In progress', 
                    'Completed',
                    'Canceled',
                    'Pending extension'
                ],
                datasets: [{
                    label: 'Số lượng đề tài',
                    data: [
                        <?php echo $project_status_data['Not started']; ?>,
                        <?php echo $project_status_data['In progress']; ?>,
                        <?php echo $project_status_data['Completed']; ?>,
                        <?php echo $project_status_data['Canceled']; ?>,
                        <?php echo $project_status_data['Pending extension']; ?>
                    ],
                    backgroundColor: [
                        '<?php echo $status_colors['Not started']; ?>',
                        '<?php echo $status_colors['In progress']; ?>',
                        '<?php echo $status_colors['Completed']; ?>',
                        '<?php echo $status_colors['Canceled']; ?>',
                        '<?php echo $status_colors['Pending extension']; ?>'
                    ],
                    borderColor: [
                        '<?php echo $status_border_colors['Not started']; ?>',
                        '<?php echo $status_border_colors['In progress']; ?>',
                        '<?php echo $status_border_colors['Completed']; ?>',
                        '<?php echo $status_border_colors['Canceled']; ?>',
                        '<?php echo $status_border_colors['Pending extension']; ?>'
                    ],
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#0d6efd',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#6c757d'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#6c757d'
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });

        // Animation cho các card
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.dashboard-card');
            
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '0';
                        entry.target.style.transform = 'translateY(20px)';
                        entry.target.style.transition = 'all 0.6s ease';
                        
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, 100);
                        
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            cards.forEach(card => {
                observer.observe(card);
            });
        });
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>
