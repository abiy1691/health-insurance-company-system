<?php
session_start();
include '../includes/config.php';

// Check if user is logged in as agent
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get agent's statistics
$agent_id = $_SESSION['agent_id'];
$stats = [
    'customers' => 0,
    'policies' => 0,
    'payments' => 0,
    'commissions' => 0
];

// Get total customers
$query = "SELECT COUNT(*) as total FROM Customers WHERE agent_id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing customers query: " . $conn->error);
}
$stmt->bind_param("i", $agent_id);
if (!$stmt->execute()) {
    die("Error executing customers query: " . $stmt->error);
}
$result = $stmt->get_result();
$stats['customers'] = $result->fetch_assoc()['total'];
$stmt->close();

// Get total policies
$query = "SELECT COUNT(*) as total FROM Customer_Policy cp 
          JOIN Customers c ON cp.customer_id = c.customer_id 
          WHERE c.agent_id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing policies query: " . $conn->error);
}
$stmt->bind_param("i", $agent_id);
if (!$stmt->execute()) {
    die("Error executing policies query: " . $stmt->error);
}
$result = $stmt->get_result();
$stats['policies'] = $result->fetch_assoc()['total'];
$stmt->close();

// Get total payments
$query = "SELECT COUNT(*) as total FROM Payments p 
          JOIN Customers c ON p.customer_id = c.customer_id 
          WHERE c.agent_id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing payments query: " . $conn->error);
}
$stmt->bind_param("i", $agent_id);
if (!$stmt->execute()) {
    die("Error executing payments query: " . $stmt->error);
}
$result = $stmt->get_result();
$stats['payments'] = $result->fetch_assoc()['total'];
$stmt->close();

// Get total commissions
$query = "SELECT SUM(commission_amount) as total FROM Commissions WHERE agent_id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing commissions query: " . $conn->error);
}
$stmt->bind_param("i", $agent_id);
if (!$stmt->execute()) {
    die("Error executing commissions query: " . $stmt->error);
}
$result = $stmt->get_result();
$stats['commissions'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - Health Insurance</title>
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
        .customers-card {
            border-left-color: #4e73df;
        }
        .policies-card {
            border-left-color: #1cc88a;
        }
        .payments-card {
            border-left-color: #36b9cc;
        }
        .commissions-card {
            border-left-color: #f6c23e;
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'includes/header.php'; ?>
                
                <div class="container-fluid">
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert" id="messageAlert">
                            <?php 
                                echo $_SESSION['message'];
                                unset($_SESSION['message']);
                                unset($_SESSION['message_type']);
                            ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Dashboard Overview</h1>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row">
                        <!-- Customers Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2 stat-card customers-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Customers</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['customers'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users stat-icon text-primary"></i>
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
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Policies</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['policies'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-file-contract stat-icon text-success"></i>
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
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Total Payments</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['payments'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-money-bill-wave stat-icon text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Commissions Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2 stat-card commissions-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Total Commissions</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?= number_format($stats['commissions'], 2) ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-hand-holding-usd stat-icon text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="../style/js/bootstrap.bundle.min.js"></script>
    <script src="../style/js/sb-admin-2.min.js"></script>
    <script>
        // Auto-hide alert after 3 seconds
        $(document).ready(function() {
            if ($('#messageAlert').length) {
                setTimeout(function() {
                    $('#messageAlert').alert('close');
                }, 3000);
            }
        });
    </script>
</body>
</html>
