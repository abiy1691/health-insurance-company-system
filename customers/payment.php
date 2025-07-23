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

// Build the base query
$query = "SELECT p.*, pol.policy_name, pol.policy_type 
          FROM payments p 
          JOIN policies pol ON p.policy_id = pol.policy_id 
          WHERE p.customer_id = ? AND p.status = 'Active'";

$params = array($customer_id);
$types = "i";

// Add policy filter if selected
if (!empty($policy_filter)) {
    $query .= " AND p.policy_id = ?";
    $params[] = $policy_filter;
    $types .= "i";
}

// Add date filter if selected
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

// Get total payments
$total_query = "SELECT SUM(amount) as total_amount FROM payments 
                WHERE customer_id = ? AND status = 'Active'";
$stmt = $conn->prepare($total_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$total_result = $stmt->get_result()->fetch_assoc();
$total_amount = $total_result['total_amount'];

// Get filtered total if filters are applied
$filtered_total = $total_amount;
if (!empty($policy_filter) || !empty($date_filter)) {
    $filtered_total_query = "SELECT SUM(amount) as filtered_total FROM payments 
                            WHERE customer_id = ? AND status = 'Active'";
    if (!empty($policy_filter)) {
        $filtered_total_query .= " AND policy_id = ?";
    }
    if (!empty($date_filter)) {
        $filtered_total_query .= " AND DATE(payment_date) = ?";
    }
    
    $stmt = $conn->prepare($filtered_total_query);
    $params = array($customer_id);
    $types = "i";
    if (!empty($policy_filter)) {
        $params[] = $policy_filter;
        $types .= "i";
    }
    if (!empty($date_filter)) {
        $params[] = $date_filter;
        $types .= "s";
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $filtered_result = $stmt->get_result()->fetch_assoc();
    $filtered_total = $filtered_result['filtered_total'];
}

// Get list of policies for filter dropdown
$policies_query = "SELECT DISTINCT p.policy_id, p.policy_name 
                  FROM payments py 
                  JOIN policies p ON py.policy_id = p.policy_id 
                  WHERE py.customer_id = ? AND py.status = 'Active' 
                  ORDER BY p.policy_name";
$stmt = $conn->prepare($policies_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$policies = $stmt->get_result();

// Get agent information
$agent_query = "SELECT a.* FROM agents a 
                JOIN customers c ON a.agent_id = c.agent_id 
                WHERE c.customer_id = ?";
$stmt = $conn->prepare($agent_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$agent = $stmt->get_result()->fetch_assoc();
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
        }
        .payment-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 20px;
        }
        .payment-body {
            padding: 20px;
            background-color: white;
        }
        .total-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1cc88a;
        }
        .proof-photo {
            max-width: 200px;
            max-height: 200px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .proof-photo:hover {
            transform: scale(1.1);
        }
        .modal-image {
            max-width: 100%;
            max-height: 80vh;
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
                    <h1 class="h3 text-gray-800">Payment History</h1>
                    <a href="owned_pay.php" class="btn btn-success">
                        <i class="fas fa-money-bill-wave mr-2"></i>Make Payment
                    </a>
                </div>

                <!-- Filters -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filter Payments</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row">
                            <div class="col-md-4 mb-3">
                                <label for="policy">Filter by Policy</label>
                                <select class="form-control" id="policy" name="policy">
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
                                <label for="date">Filter by Date</label>
                                <input type="date" class="form-control" id="date" name="date" 
                                       value="<?php echo $date_filter; ?>">
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="payment.php" class="btn btn-secondary ml-2">Clear Filters</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Total Amount Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Total Payments</h6>
                    </div>
                    <div class="card-body">
                        <div class="total-amount">
                            Total Amount: $<?php echo number_format($filtered_total, 2); ?>
                        </div>
                    </div>
                </div>

                <!-- Agent Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Your Insurance Agent</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <?php if ($agent['photo_url']): ?>
                                    <img src="../uploads/<?php echo $agent['photo_url']; ?>" 
                                         class="img-fluid rounded-circle mb-3" 
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary text-white mb-3" 
                                         style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                        <i class="fas fa-user fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-9">
                                <h4 class="mb-3"><?php echo htmlspecialchars($agent['name']); ?></h4>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong><i class="fas fa-envelope mr-2"></i>Email:</strong> 
                                            <?php echo htmlspecialchars($agent['email']); ?></p>
                                        <p><strong><i class="fas fa-phone mr-2"></i>Phone:</strong> 
                                            <?php echo htmlspecialchars($agent['phone']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong><i class="fas fa-star mr-2"></i>Expertise:</strong> 
                                            <?php echo htmlspecialchars($agent['expertise']); ?></p>
                                        <p><strong><i class="fas fa-percent mr-2"></i>Commission Rate:</strong> 
                                            <?php echo $agent['commission_rate']; ?>%</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="agent.php?agent_id=<?php echo $agent['agent_id']; ?>" 
                                       class="btn btn-primary mr-2">
                                        <i class="fas fa-user-tie mr-2"></i>View Full Profile
                                    </a>
                                    <a href="feedback.php?agent_id=<?php echo $agent['agent_id']; ?>" 
                                       class="btn btn-info">
                                        <i class="fas fa-comment mr-2"></i>Send Feedback
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Payment History</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Payment Date</th>
                                        <th>Policy</th>
                                        <th>Amount</th>
                                        <th>Bank Name</th>
                                        <th>Account Number</th>
                                        <th>Proof of Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = $payments->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('F j, Y', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['policy_name']); ?></td>
                                            <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($payment['bank_name']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['account_number']); ?></td>
                                            <td>
                                                <?php if ($payment['proof_photo']): ?>
                                                    <img src="../uploads/<?php echo $payment['proof_photo']; ?>" 
                                                         class="proof-photo" 
                                                         data-toggle="modal" 
                                                         data-target="#proofModal<?php echo $payment['payment_number']; ?>">
                                                <?php else: ?>
                                                    No proof provided
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Proof Photo Modal -->
                                        <?php if ($payment['proof_photo']): ?>
                                            <div class="modal fade" id="proofModal<?php echo $payment['payment_number']; ?>" tabindex="-1" role="dialog">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Proof of Payment</h5>
                                                            <button type="button" class="close" data-dismiss="modal">
                                                                <span>&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body text-center">
                                                            <img src="../uploads/<?php echo $payment['proof_photo']; ?>" 
                                                                 class="modal-image">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
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