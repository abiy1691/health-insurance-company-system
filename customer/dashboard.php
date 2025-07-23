<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Fetch customer's statistics
$customer_id = $_SESSION['customer_id'];
$stats = [
    'policies' => 0,
    'payments' => 0,
    'family_members' => 0,
    'feedbacks' => 0
];

// Get total policies for this customer
$query = "SELECT COUNT(*) as total FROM Customer_Policy WHERE customer_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $stats['policies'] = $result->fetch_assoc()['total'];
    }
    $stmt->close();
}

// Get total payments for this customer
$query = "SELECT COUNT(*) as total FROM Payments WHERE customer_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $stats['payments'] = $result->fetch_assoc()['total'];
    }
    $stmt->close();
}

// Get total family members for this customer
$query = "SELECT COUNT(*) as total FROM Family_Member WHERE customer_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $stats['family_members'] = $result->fetch_assoc()['total'];
    }
    $stmt->close();
}

// Get total feedbacks for this customer
$query = "SELECT COUNT(*) as total FROM Feedback WHERE customer_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $stats['feedbacks'] = $result->fetch_assoc()['total'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard</title>
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
        .policies-card {
            border-left-color: #4e73df;
        }
        .payments-card {
            border-left-color: #1cc88a;
        }
        .family-members-card {
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
                    <h1 class="h3 mb-0 text-gray-800">Welcome, <?= htmlspecialchars($_SESSION['customer_name']) ?></h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row">

                    <!-- Policies Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2 stat-card policies-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            My Policies</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['policies'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-contract stat-icon text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payments Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2 stat-card payments-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            My Payments</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['payments'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-credit-card stat-icon text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Family Members Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2 stat-card family-members-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Family Members</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['family_members'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users stat-icon text-info"></i>
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
                                            My Feedbacks</div>
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
