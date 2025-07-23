<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$customer_id = $_SESSION['customer_id'];

// Get customer's owned policies
$query = "SELECT 
            cp.policy_id,
            cp.start_date,
            cp.end_date,
            cp.status,
            p.policy_name,
            p.policy_type,
            p.description,
            p.price,
            p.payment_interval,
            p.policy_term,
            c.name as customer_name 
          FROM customer_policy cp 
          JOIN policies p ON cp.policy_id = p.policy_id 
          JOIN customers c ON cp.customer_id = c.customer_id 
          WHERE cp.customer_id = ? AND cp.status = 'Active'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$policies = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .policy-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease;
            background: white;
        }
        .policy-card:hover {
            transform: translateY(-5px);
        }
        .policy-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 20px;
            position: relative;
        }
        .policy-body {
            padding: 20px;
        }
        .policy-footer {
            background: #f8f9fc;
            padding: 15px 20px;
            border-top: 1px solid #e3e6f0;
        }
        .policy-info-box {
            background: #f8f9fc;
            border-radius: 8px;
            padding: 15px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e3e6f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #5a5c69;
            flex: 1;
        }
        .info-value {
            color: #4e73df;
            flex: 1;
            text-align: right;
        }
        .payment-btn {
            width: 100%;
            padding: 10px;
            font-weight: bold;
            border-radius: 5px;
        }
        .policy-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            background: rgba(255, 255, 255, 0.2);
        }
        .status-active {
            background-color: #1cc88a;
            color: white;
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 text-gray-800">Make Payment</h1>
                    <a href="payment.php" class="btn btn-primary">
                        <i class="fas fa-history mr-2"></i>View Payment History
                    </a>
                </div>

                <!-- Policies Grid -->
                <div class="row">
                    <?php while ($policy = $policies->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="policy-card">
                                <div class="policy-header">
                                    <h4 class="mb-0"><?php echo htmlspecialchars($policy['policy_name']); ?></h4>
                                    <span class="policy-status status-active"><?php echo $policy['status']; ?></span>
                                </div>
                                <div class="policy-body">
                                    <div class="policy-info-box">
                                        <div class="info-row">
                                            <span class="info-label">Policy Type:</span>
                                            <span class="info-value"><?php echo ucfirst($policy['policy_type']); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Policy Holder:</span>
                                            <span class="info-value"><?php echo htmlspecialchars($policy['customer_name']); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Payment Interval:</span>
                                            <span class="info-value"><?php echo ucfirst($policy['payment_interval']); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Start Date:</span>
                                            <span class="info-value"><?php echo date('F j, Y', strtotime($policy['start_date'])); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">End Date:</span>
                                            <span class="info-value"><?php echo date('F j, Y', strtotime($policy['end_date'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="policy-footer">
                                    <a href="make_payment.php?policy_id=<?php echo $policy['policy_id']; ?>" 
                                       class="btn btn-success payment-btn">
                                        <i class="fas fa-money-bill-wave mr-2"></i>Pay Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
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

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Bootstrap core JavaScript-->
<script src="../style/js/jquery.min.js"></script>
<script src="../style/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="../style/js/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="../style/js/sb-admin-2.min.js"></script>

</body>
</html> 