<?php
session_start();
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$agent_id = $_SESSION['agent_id'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$customer_filter = isset($_GET['customer_id']) ? $_GET['customer_id'] : '';
$policy_filter = isset($_GET['policy_id']) ? $_GET['policy_id'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Get selected customer for individual total
$selected_customer_id = isset($_GET['selected_customer']) ? $_GET['selected_customer'] : '';

// Build the query with filters
$query = "SELECT p.*, c.name as customer_name, pol.policy_name 
          FROM payments p 
          JOIN customers c ON p.customer_id = c.customer_id 
          JOIN policies pol ON p.policy_id = pol.policy_id 
          WHERE c.agent_id = ?";

$params = [$agent_id];
$types = "i";

if (!empty($status_filter)) {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($customer_filter)) {
    $query .= " AND p.customer_id = ?";
    $params[] = $customer_filter;
    $types .= "i";
}

if (!empty($policy_filter)) {
    $query .= " AND p.policy_id = ?";
    $params[] = $policy_filter;
    $types .= "i";
}

if (!empty($date_from)) {
    $query .= " AND DATE(p.payment_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND DATE(p.payment_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY p.payment_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);

// Calculate total amount paid by active payments
$total_query = "SELECT SUM(p.amount) as total_amount 
                FROM payments p 
                JOIN customers c ON p.customer_id = c.customer_id 
                WHERE c.agent_id = ? AND p.status = 'Active'";
$stmt = $conn->prepare($total_query);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$total_result = $stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_amount = $total_row['total_amount'] ?? 0;

// Get all customers assigned to this agent for filter dropdown
$customers_query = "SELECT customer_id, name FROM customers WHERE agent_id = ? ORDER BY name";
$stmt = $conn->prepare($customers_query);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$customers_result = $stmt->get_result();
$customers = $customers_result->fetch_all(MYSQLI_ASSOC);

// Get all policies for filter dropdown
$policies_query = "SELECT DISTINCT p.policy_id, p.policy_name 
                  FROM policies p 
                  JOIN customer_policy cp ON p.policy_id = cp.policy_id 
                  JOIN customers c ON cp.customer_id = c.customer_id 
                  WHERE c.agent_id = ? 
                  ORDER BY p.policy_name";
$stmt = $conn->prepare($policies_query);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$policies_result = $stmt->get_result();
$policies = $policies_result->fetch_all(MYSQLI_ASSOC);

// Get total amount paid by each customer
$customer_totals_query = "SELECT c.customer_id, c.name, SUM(p.amount) as total_paid
                         FROM customers c
                         LEFT JOIN payments p ON c.customer_id = p.customer_id AND p.status = 'Active'
                         WHERE c.agent_id = ?
                         GROUP BY c.customer_id, c.name
                         ORDER BY c.name";
$stmt = $conn->prepare($customer_totals_query);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$customer_totals_result = $stmt->get_result();
$customer_totals = $customer_totals_result->fetch_all(MYSQLI_ASSOC);

// Get selected customer's total amount if a customer is selected
$selected_customer_total = 0;
$selected_customer_name = '';
if (!empty($selected_customer_id)) {
    $selected_customer_query = "SELECT c.name, SUM(p.amount) as total_paid
                               FROM customers c
                               LEFT JOIN payments p ON c.customer_id = p.customer_id AND p.status = 'Active'
                               WHERE c.customer_id = ? AND c.agent_id = ?
                               GROUP BY c.customer_id, c.name";
    $stmt = $conn->prepare($selected_customer_query);
    $stmt->bind_param("ii", $selected_customer_id, $agent_id);
    $stmt->execute();
    $selected_customer_result = $stmt->get_result();
    if ($selected_customer_result->num_rows > 0) {
        $selected_customer_data = $selected_customer_result->fetch_assoc();
        $selected_customer_total = $selected_customer_data['total_paid'] ?? 0;
        $selected_customer_name = $selected_customer_data['name'];
    }
}
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .payment-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .payment-photo:hover {
            transform: scale(1.1);
        }
        .modal-photo {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
        }
        .filter-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 15px;
        }
        .total-amount-card {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .total-amount-card h5 {
            margin-bottom: 5px;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .total-amount-card h3 {
            margin-bottom: 0;
            font-weight: bold;
        }
        .customer-total-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 15px;
        }
        .customer-total-item {
            padding: 10px;
            border-bottom: 1px solid #e3e6f0;
        }
        .customer-total-item:last-child {
            border-bottom: none;
        }
        .customer-total-name {
            font-weight: 600;
            color: #4e73df;
        }
        .customer-total-amount {
            font-weight: bold;
            color: #1cc88a;
        }
        .selected-customer-card {
            background: linear-gradient(45deg, #1cc88a, #13855c);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .selected-customer-card h5 {
            margin-bottom: 5px;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .selected-customer-card h3 {
            margin-bottom: 0;
            font-weight: bold;
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
                    <h1 class="h3 mb-0 text-gray-800">Payment History</h1>
                </div>

                <!-- Total Amount Card -->
                <div class="row mb-4">
                    <div class="col-xl-12">
                        <div class="total-amount-card">
                            <h5>Total Amount Paid by Your Customers</h5>
                            <h3>ETB <?php echo number_format($total_amount, 2); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Customer Selection Form -->
                <div class="row mb-4">
                    <div class="col-xl-12">
                        <div class="filter-card">
                            <h5 class="mb-3">Select Customer to View Total Amount</h5>
                            <form method="GET" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="selected_customer">Select Customer</label>
                                        <select class="form-control" id="selected_customer" name="selected_customer">
                                            <option value="">Select a customer</option>
                                            <?php foreach ($customers as $customer): ?>
                                                <option value="<?php echo $customer['customer_id']; ?>" <?php echo $selected_customer_id == $customer['customer_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($customer['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary">View Total</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Selected Customer Total Card -->
                <?php if (!empty($selected_customer_id) && !empty($selected_customer_name)): ?>
                <div class="row mb-4">
                    <div class="col-xl-12">
                        <div class="selected-customer-card">
                            <h5>Total Amount Paid by <?php echo htmlspecialchars($selected_customer_name); ?></h5>
                            <h3>ETB <?php echo number_format($selected_customer_total, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filter Card -->
                <div class="filter-card">
                    <h5 class="mb-3">Filter Payments</h5>
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Expired" <?php echo $status_filter == 'Expired' ? 'selected' : ''; ?>>Expired</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="customer_id">Customer</label>
                                <select class="form-control" id="customer_id" name="customer_id">
                                    <option value="">All Customers</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['customer_id']; ?>" <?php echo $customer_filter == $customer['customer_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="policy_id">Policy</label>
                                <select class="form-control" id="policy_id" name="policy_id">
                                    <option value="">All Policies</option>
                                    <?php foreach ($policies as $policy): ?>
                                        <option value="<?php echo $policy['policy_id']; ?>" <?php echo $policy_filter == $policy['policy_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($policy['policy_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="date_from">Date From</label>
                                <input type="text" class="form-control datepicker" id="date_from" name="date_from" value="<?php echo $date_from; ?>" placeholder="Select date">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="date_to">Date To</label>
                                <input type="text" class="form-control datepicker" id="date_to" name="date_to" value="<?php echo $date_to; ?>" placeholder="Select date">
                            </div>
                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="payment_history.php" class="btn btn-secondary ml-2">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Payments Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Payment History</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Customer</th>
                                        <th>Policy</th>
                                        <th>Amount</th>
                                        <th>Bank Name</th>
                                        <th>Account Number</th>
                                        <th>Payment Date</th>
                                        <th>Status</th>
                                        <th>Proof</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($payments) > 0): ?>
                                        <?php foreach ($payments as $index => $payment): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['policy_name']); ?></td>
                                                <td>ETB <?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($payment['bank_name']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['account_number']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    switch($payment['status']) {
                                                        case 'Active':
                                                            $status_class = 'badge-success';
                                                            break;
                                                        case 'Pending':
                                                            $status_class = 'badge-warning';
                                                            break;
                                                        case 'Expired':
                                                            $status_class = 'badge-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo $payment['status']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($payment['proof_photo'])): ?>
                                                        <img src="../uploads/payments/<?php echo htmlspecialchars($payment['proof_photo']); ?>" 
                                                             alt="Payment Proof" 
                                                             class="payment-photo"
                                                             onclick="showPhoto(this.src)">
                                                    <?php else: ?>
                                                        <span class="text-muted">No proof</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No payment records found</td>
                                        </tr>
                                    <?php endif; ?>
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

<!-- Photo Modal -->
<div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalLabel">Payment Proof</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalPhoto" src="" alt="Payment Proof" class="modal-photo">
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="../style/js/jquery.min.js"></script>
<script src="../style/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="../style/js/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="../style/js/sb-admin-2.min.js"></script>

<!-- Page level plugins -->
<script src="../style/js/jquery.dataTables.min.js"></script>
<script src="../style/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Page level custom scripts -->
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "order": [[6, "desc"]], // Sort by payment date by default
            "pageLength": 10
        });
        
        // Initialize date pickers
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            allowInput: true
        });
    });
    
    // Function to show photo in modal
    function showPhoto(src) {
        $('#modalPhoto').attr('src', src);
        $('#photoModal').modal('show');
    }
</script>

</body>
</html> 