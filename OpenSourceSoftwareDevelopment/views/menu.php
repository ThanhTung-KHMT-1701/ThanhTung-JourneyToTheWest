<?php
// Sử dụng __DIR__ để tính toán đường dẫn chính xác từ vị trí file hiện tại
require_once __DIR__ . '/../functions/auth.php';
checkLogin(__DIR__ . '/../index.php');
$currentUser = getCurrentUser();

// Thiết lập title cho trang menu
$pageTitle = 'Trang chủ - DNU OpenSource';
include __DIR__ . '/header.php';
?>

<body>
    <?php
    // Lấy tên tệp hiện tại (ví dụ: "index.php", "ve-chung-toi.php")
    $currentPage = basename($_SERVER['PHP_SELF']);
    ?>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <!-- Container wrapper -->
        <div class="container-fluid">
            <!-- Toggle button -->
            <button data-mdb-collapse-init class="navbar-toggler" type="button"
                data-mdb-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Collapsible wrapper -->
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Navbar brand -->
                <a class="navbar-brand mt-2 mt-lg-0" href="#">
                    <img src="../images/fitdnu_logo.png" height="40"
                        alt="FIT-DNU Logo" loading="lazy" />
                </a>
                <!-- Left links -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_overview.php">Bảng điều khiển</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="student.php">Quản lý sinh viên</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="teacher.php">Quản lý giảng viên</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="project.php">Quản lý đề tài</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="project_details.php">Chi tiết đề tài</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="project_extensions.php">Gia hạn đề tài</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="project_presentations.php">Báo cáo đề tài</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="project_presentation_details.php">Chi tiết báo cáo đề tài</a>
                    </li>
                </ul>
                <!-- Left links -->
            </div>
            <!-- Collapsible wrapper -->

            <!-- Right elements -->
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <a data-mdb-dropdown-init class="dropdown-toggle d-flex align-items-center hidden-arrow" href="#"
                        id="navbarDropdownMenuAvatar" role="button" aria-expanded="false">
                        <img src="../images/aiotlab_logo.png" class="rounded-circle" height="25"
                            alt="AVT" loading="lazy" />
                        <!-- <span class="ms-2"><?= htmlspecialchars($currentUser['username']) ?></span> -->
                        <span class="ms-2"><a class="dropdown-item" href="../handle/logout_process.php">Logout</a></span>
                         
                    </a>
                    
                </div>
            </div>
            <!-- Right elements -->
        </div>
        <!-- Container wrapper -->
    </nav>
    <!-- Navbar -->
</body>

</html>