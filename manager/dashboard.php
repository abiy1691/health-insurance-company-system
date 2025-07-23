<?php
session_start();
if (!isset($_SESSION['manager_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Fetch statistics
$stats = [
    'agents' => 0,
    'customers' => 0,
    'policies' => 0,
    'feedbacks' => 0
];

// Get total agents
$query = "SELECT COUNT(*) as total FROM agents";
$result = $conn->query($query);
if ($result) {
    $stats['agents'] = $result->fetch_assoc()['total'];
}

// Get total customers
$query = "SELECT COUNT(*) as total FROM customers";
$result = $conn->query($query);
if ($result) {
    $stats['customers'] = $result->fetch_assoc()['total'];
}

// Get total policies
$query = "SELECT COUNT(*) as total FROM policies";
$result = $conn->query($query);
if ($result) {
    $stats['policies'] = $result->fetch_assoc()['total'];
}

// Get total feedbacks
$query = "SELECT COUNT(*) as total FROM feedback";
$result = $conn->query($query);
if ($result) {
    $stats['feedbacks'] = $result->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Dashboard</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .agents-card {
            border-left-color: #4e73df;
        }
        .customers-card {
            border-left-color: #1cc88a;
        }
        .policies-card {
            border-left-color: #36b9cc;
        }
        .feedbacks-card {
            border-left-color: #f6c23e;
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
    </style>
</head>
<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

    <?php include 'includes/sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <?php include 'includes/header.php'; ?>

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Dashboard Overview</h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row">

                    <!-- Agents Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2 stat-card agents-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Agents</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['agents'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-tie stat-icon text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customers Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2 stat-card customers-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Customers</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['customers'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users stat-icon text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Policies Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2 stat-card policies-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Policies</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['policies'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-contract stat-icon text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feedbacks Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2 stat-card feedbacks-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Total Feedbacks</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['feedbacks'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-comments stat-icon text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- End of Main Content -->

        <?php include 'includes/footer.php'; ?>

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<script src="../style/js/bootstrap.bundle.min.js"></script>
<script src="../style/js/sb-admin-2.min.js"></script>
</body>
</html>
