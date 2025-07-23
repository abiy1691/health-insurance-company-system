<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$customer_id = $_SESSION['customer_id'];

// Get filter parameters
$policy_filter = isset($_GET['policy']) ? $_GET['policy'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build the base query for payments
$query = "SELECT p.*, cp.policy_name, cp.policy_id 
          FROM payments p 
          JOIN customer_policy cp ON p.policy_id = cp.policy_id 
          WHERE p.customer_id = ? AND p.status = 'Active'";

$params = array($customer_id);
$types = "i";

// Add filters if provided
if (!empty($policy_filter)) {
    $query .= " AND p.policy_id = ?";
    $params[] = $policy_filter;
    $types .= "i";
}

if (!empty($date_filter)) {
    $query .= " AND DATE(p.payment_date) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$query .= " ORDER BY p.payment_date DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$payments = $stmt->get_result();

// Get total payments for all policies
$total_query = "SELECT 
                SUM(amount) as total_amount,
                COUNT(*) as total_payments
                FROM payments 
                WHERE customer_id = ? AND status = 'Active'";
$stmt = $conn->prepare($total_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();

// Get filtered totals
$filtered_total_query = "SELECT 
                        SUM(amount) as filtered_total_amount,
                        COUNT(*) as filtered_total_payments
                        FROM payments 
                        WHERE customer_id = ? AND status = 'Active'";
$filtered_params = array($customer_id);
$filtered_types = "i";

if (!empty($policy_filter)) {
    $filtered_total_query .= " AND policy_id = ?";
    $filtered_params[] = $policy_filter;
    $filtered_types .= "i";
}

if (!empty($date_filter)) {
    $filtered_total_query .= " AND DATE(payment_date) = ?";
    $filtered_params[] = $date_filter;
    $filtered_types .= "s";
}

$stmt = $conn->prepare($filtered_total_query);
if (!empty($filtered_params)) {
    $stmt->bind_param($filtered_types, ...$filtered_params);
}
$stmt->execute();
$filtered_totals = $stmt->get_result()->fetch_assoc();

// Get customer's policies for filter dropdown
$policies_query = "SELECT DISTINCT p.policy_id, p.policy_name 
                  FROM payments p 
                  JOIN customer_policy cp ON p.policy_id = cp.policy_id 
                  WHERE p.customer_id = ? AND p.status = 'Active' 
                  ORDER BY p.policy_name";
$stmt = $conn->prepare($policies_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$policies = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .payment-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .payment-card:hover {
            transform: translateY(-5px);
        }
        .payment-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 15px;
        }
        .payment-body {
            padding: 20px;
            background-color: white;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #4e73df;
        }
        .stat-label {
            color: #5a5c69;
            font-size: 0.9rem;
        }
        .filter-card {
            background: linear-gradient(45deg, #f8f9fc, #e3e6f0);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .proof-photo {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .proof-photo:hover {
            transform: scale(1.05);
        }
        .modal-image {
            max-width: 100%;
            max-height: 80vh;
        }
        .payment-details {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .payment-details p {
            margin-bottom: 8px;
        }
        .filter-btn {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .filter-btn:hover {
            background: linear-gradient(45deg, #224abe, #4e73df);
            transform: translateY(-2px);
        }
        .clear-btn {
            background: #e74a3b;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .clear-btn:hover {
            background: #d52a1a;
            transform: translateY(-2px);
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
                <h1 class="h3 mb-4 text-gray-800">Payment History</h1>

                <!-- Filter Section -->
                <div class="filter-card">
                    <form method="GET" class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Filter by Policy</label>
                            <select name="policy" class="form-control">
                                <option value="">All Policies</option>
                                <?php while ($policy = $policies->fetch_assoc()): ?>
                                    <option value="<?php echo $policy['policy_id']; ?>" 
                                            <?php echo ($policy_filter == $policy['policy_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($policy['policy_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Filter by Date</label>
                            <input type="date" name="date" class="form-control" value="<?php echo $date_filter; ?>">
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <button type="submit" class="filter-btn mr-2">Apply Filters</button>
                            <a href="payments.php" class="clear-btn">Clear Filters</a>
                        </div>
                    </form>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $filtered_totals['filtered_total_payments']; ?></div>
                            <div class="stat-label">Total Payments Made</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-number">ETB <?php echo number_format($filtered_totals['filtered_total_amount'], 2); ?></div>
                            <div class="stat-label">Total Amount Paid</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-number">ETB <?php echo number_format($filtered_totals['filtered_total_amount'] / max(1, $filtered_totals['filtered_total_payments']), 2); ?></div>
                            <div class="stat-label">Average Payment</div>
                        </div>
                    </div>
                </div>

                <!-- Payments List -->
                <?php while ($payment = $payments->fetch_assoc()): ?>
                <div class="payment-card">
                    <div class="payment-header">
                        <h5 class="mb-0">Payment #<?php echo $payment['payment_number']; ?></h5>
                    </div>
                    <div class="payment-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="payment-details">
                                    <p><strong>Policy:</strong> <?php echo htmlspecialchars($payment['policy_name']); ?></p>
                                    <p><strong>Amount:</strong> ETB <?php echo number_format($payment['amount'], 2); ?></p>
                                    <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($payment['payment_date'])); ?></p>
                                    <p><strong>Bank:</strong> <?php echo htmlspecialchars($payment['bank_name']); ?></p>
                                    <p><strong>Account Number:</strong> <?php echo htmlspecialchars($payment['account_number']); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($payment['proof_photo'])): ?>
                                <div class="text-center">
                                    <img src="../uploads/payments/<?php echo $payment['proof_photo']; ?>" 
                                         alt="Payment Proof" 
                                         class="proof-photo"
                                         data-toggle="modal" 
                                         data-target="#proofModal<?php echo $payment['payment_number']; ?>">
                                    <p class="mt-2 text-muted">Click to view proof</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Proof Photo Modal -->
                <?php if (!empty($payment['proof_photo'])): ?>
                <div class="modal fade" id="proofModal<?php echo $payment['payment_number']; ?>" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Payment Proof #<?php echo $payment['payment_number']; ?></h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body text-center">
                                <img src="../uploads/payments/<?php echo $payment['proof_photo']; ?>" 
                                     alt="Payment Proof" 
                                     class="modal-image">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endwhile; ?>

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