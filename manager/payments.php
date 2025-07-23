<?php
session_start();
if (!isset($_SESSION['manager_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Handle status update
if (isset($_POST['update_status'])) {
    $payment_number = $_POST['payment_number'];
    $new_status = $_POST['new_status'];
    
    // Log the status update attempt
    error_log("Attempting to update payment #$payment_number to status: $new_status");
    
    // Update payment status
    $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE payment_number = ?");
    if (!$stmt) {
        error_log("Error preparing payment status update: " . $conn->error);
    } else {
    $stmt->bind_param("si", $new_status, $payment_number);
        if (!$stmt->execute()) {
            error_log("Error updating payment status: " . $stmt->error);
        } else {
            error_log("Successfully updated payment #$payment_number to status: $new_status");
        }
    $stmt->close();
    }

    // If payment status is changed to "Active", calculate and store commission
    if ($new_status == 'Active') {
        error_log("Payment #$payment_number status is Active, attempting to calculate commission");
        
        // Get payment details with agent information
        $payment_query = "SELECT p.*, c.agent_id, a.commission_rate 
                         FROM payments p 
                         LEFT JOIN customers c ON p.customer_id = c.customer_id 
                         LEFT JOIN agents a ON c.agent_id = a.agent_id 
                         WHERE p.payment_number = ?";
        
        $stmt = $conn->prepare($payment_query);
        if (!$stmt) {
            error_log("Error preparing payment details query: " . $conn->error);
        } else {
            $stmt->bind_param("i", $payment_number);
            if (!$stmt->execute()) {
                error_log("Error executing payment details query: " . $stmt->error);
            } else {
                $result = $stmt->get_result();
                $payment_data = $result->fetch_assoc();
                
                // Log payment data for debugging
                error_log("Payment data for commission calculation: " . json_encode($payment_data));
                
                if (!$payment_data) {
                    error_log("No payment data found for payment #$payment_number");
                } else if (!$payment_data['agent_id']) {
                    error_log("No agent_id found for payment #$payment_number in the first query, trying direct query");
                    
                    // Try to get agent_id directly from customers table
                    $customer_id = $payment_data['customer_id'];
                    $agent_query = "SELECT agent_id FROM customers WHERE customer_id = ?";
                    $agent_stmt = $conn->prepare($agent_query);
                    
                    if (!$agent_stmt) {
                        error_log("Error preparing agent query: " . $conn->error);
                    } else {
                        $agent_stmt->bind_param("i", $customer_id);
                        if (!$agent_stmt->execute()) {
                            error_log("Error executing agent query: " . $agent_stmt->error);
                        } else {
                            $agent_result = $agent_stmt->get_result();
                            $agent_data = $agent_result->fetch_assoc();
                            
                            if ($agent_data && $agent_data['agent_id']) {
                                error_log("Found agent_id: " . $agent_data['agent_id'] . " for customer_id: " . $customer_id);
                                
                                // Get commission rate from agents table
                                $rate_query = "SELECT commission_rate FROM agents WHERE agent_id = ?";
                                $rate_stmt = $conn->prepare($rate_query);
                                
                                if (!$rate_stmt) {
                                    error_log("Error preparing commission rate query: " . $conn->error);
                                } else {
                                    $rate_stmt->bind_param("i", $agent_data['agent_id']);
                                    if (!$rate_stmt->execute()) {
                                        error_log("Error executing commission rate query: " . $rate_stmt->error);
                                    } else {
                                        $rate_result = $rate_stmt->get_result();
                                        $rate_data = $rate_result->fetch_assoc();
                                        
                                        if ($rate_data && $rate_data['commission_rate']) {
                                            error_log("Found commission rate: " . $rate_data['commission_rate'] . " for agent_id: " . $agent_data['agent_id']);
                                            
                                            // Now we have all the data needed, calculate commission
                                            $commission_rate = floatval($rate_data['commission_rate']);
                                            $payment_amount = floatval($payment_data['amount']);
                                            $commission_amount = ($payment_amount * $commission_rate) / 100;
                                            
                                            error_log("Calculated commission: $commission_amount for payment #$payment_number (amount: $payment_amount, rate: $commission_rate%)");
                                            
                                            // Check if commission already exists
                                            $check_stmt = $conn->prepare("SELECT * FROM Commissions WHERE payment_number = ?");
                                            if (!$check_stmt) {
                                                error_log("Error preparing commission check query: " . $conn->error);
                                            } else {
                                                $check_stmt->bind_param("i", $payment_number);
                                                if (!$check_stmt->execute()) {
                                                    error_log("Error executing commission check query: " . $check_stmt->error);
                                                } else {
                                                    $check_result = $check_stmt->get_result();
                                                    
                                                    // Only insert if commission doesn't exist
                                                    if ($check_result->num_rows > 0) {
                                                        error_log("Commission already exists for payment #$payment_number");
                                                    } else {
                                                        error_log("No existing commission found for payment #$payment_number, proceeding with insertion");
                                                        
                                                        // Insert commission record
                                                        $insert_stmt = $conn->prepare("INSERT INTO Commissions (
                                                            customer_id,
                                                            policy_id,
                                                            payment_number,
                                                            commission_amount,
                                                            agent_id
                                                        ) VALUES (?, ?, ?, ?, ?)");
                                                        
                                                        if (!$insert_stmt) {
                                                            error_log("Error preparing commission insert query: " . $conn->error);
                                                        } else {
                                                            $insert_stmt->bind_param(
                                                                "iiidi",
                                                                $payment_data['customer_id'],
                                                                $payment_data['policy_id'],
                                                                $payment_number,
                                                                $commission_amount,
                                                                $agent_data['agent_id']
                                                            );
                                                            
                                                            if (!$insert_stmt->execute()) {
                                                                error_log("Error inserting commission: " . $insert_stmt->error);
                                                            } else {
                                                                error_log("Successfully inserted commission for payment #$payment_number");
                                                            }
                                                            $insert_stmt->close();
                                                        }
                                                    }
                                                }
                                                $check_stmt->close();
                                            }
                                        } else {
                                            error_log("No commission rate found for agent_id: " . $agent_data['agent_id']);
                                        }
                                    }
                                    $rate_stmt->close();
                                }
                            } else {
                                error_log("No agent_id found for customer_id: " . $customer_id);
                            }
                        }
                        $agent_stmt->close();
                    }
                } else if (!$payment_data['commission_rate']) {
                    error_log("No commission_rate found for payment #$payment_number");
                } else {
                    // Calculate commission amount
                    $commission_rate = floatval($payment_data['commission_rate']);
                    $payment_amount = floatval($payment_data['amount']);
                    $commission_amount = ($payment_amount * $commission_rate) / 100;
                    
                    error_log("Calculated commission: $commission_amount for payment #$payment_number (amount: $payment_amount, rate: $commission_rate%)");

                    // Check if commission already exists
                    $check_stmt = $conn->prepare("SELECT * FROM Commissions WHERE payment_number = ?");
                    if (!$check_stmt) {
                        error_log("Error preparing commission check query: " . $conn->error);
                    } else {
                        $check_stmt->bind_param("i", $payment_number);
                        if (!$check_stmt->execute()) {
                            error_log("Error executing commission check query: " . $check_stmt->error);
                        } else {
                            $check_result = $check_stmt->get_result();
                            
                            // Only insert if commission doesn't exist
                            if ($check_result->num_rows > 0) {
                                error_log("Commission already exists for payment #$payment_number");
                            } else {
                                error_log("No existing commission found for payment #$payment_number, proceeding with insertion");
                                
                                // Insert commission record
                                $insert_stmt = $conn->prepare("INSERT INTO Commissions (
                                    customer_id,
                                    policy_id,
                                    payment_number,
                                    commission_amount,
                                    agent_id
                                ) VALUES (?, ?, ?, ?, ?)");

                                if (!$insert_stmt) {
                                    error_log("Error preparing commission insert query: " . $conn->error);
                                } else {
                                    $insert_stmt->bind_param(
                                        "iiidi",
                                        $payment_data['customer_id'],
                                        $payment_data['policy_id'],
                                        $payment_number,
                                        $commission_amount,
                                        $payment_data['agent_id']
                                    );
                                    
                                    if (!$insert_stmt->execute()) {
                                        error_log("Error inserting commission: " . $insert_stmt->error);
                                    } else {
                                        error_log("Successfully inserted commission for payment #$payment_number");
                                    }
                                    $insert_stmt->close();
                                }
                            }
                        }
                        $check_stmt->close();
                    }
                }
            }
            $stmt->close();
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build the query with filters
$query = "SELECT p.*, c.name as customer_name 
          FROM payments p 
          JOIN customers c ON p.customer_id = c.customer_id 
          WHERE 1=1";

$params = array();
$types = "";

if (!empty($status_filter)) {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= "s";
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

// Prepare and execute the query with filters
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-pending {
            background-color: #ffc107;
            color: #000;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-active {
            background-color: #28a745;
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-declined {
            background-color: #dc3545;
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        .proof-photo {
            max-width: 100px;
            max-height: 100px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .proof-photo:hover {
            transform: scale(1.1);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90vh;
            margin-top: 5vh;
        }
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        .filter-section {
            background-color: #f8f9fc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filter-section .form-group {
            margin-bottom: 0;
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
                <h1 class="h3 mb-4 text-gray-800">Payment Management</h1>

                <!-- Filter Section -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row">
                            <div class="col-md-3 mb-3">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Declined" <?php echo $status_filter == 'Declined' ? 'selected' : ''; ?>>Declined</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="date_from">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="date_to">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <a href="payments.php" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- DataTales Example -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Payment List</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Customer Name</th>
                                        <th>Policy Name</th>
                                        <th>Amount</th>
                                        <th>Bank Name</th>
                                        <th>Account Number</th>
                                        <th>Payment Date</th>
                                        <th>Proof Photo</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $row_number = 1;
                                    while ($row = $result->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td><?php echo $row_number++; ?></td>
                                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['policy_name']); ?></td>
                                        <td>ETB <?php echo number_format($row['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['bank_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['account_number']); ?></td>
                                        <td><?php echo date('F j, Y', strtotime($row['payment_date'])); ?></td>
                                        <td>
                                            <?php if ($row['proof_photo']): ?>
                                            <img src="../uploads/payment_proofs/<?php echo htmlspecialchars($row['proof_photo']); ?>" 
                                                 alt="Payment Proof" 
                                                 class="proof-photo"
                                                 onclick="openModal(this)">
                                            <?php else: ?>
                                            No proof uploaded
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-<?php echo strtolower($row['status']); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="payment_number" value="<?php echo $row['payment_number']; ?>">
                                                <input type="hidden" name="new_status" value="Active">
                                                <button type="submit" name="update_status" class="btn btn-sm btn-success" <?php echo $row['status'] == 'Active' ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="payment_number" value="<?php echo $row['payment_number']; ?>">
                                                <input type="hidden" name="new_status" value="Declined">
                                                <button type="submit" name="update_status" class="btn btn-sm btn-danger" <?php echo $row['status'] == 'Declined' ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-times"></i> Decline
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
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

<!-- Modal for proof photo -->
<div id="proofModal" class="modal">
    <span class="close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

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

<script>
// Get the modal
var modal = document.getElementById("proofModal");

// Get the image and insert it inside the modal
function openModal(img) {
    var modalImg = document.getElementById("modalImage");
    modal.style.display = "block";
    modalImg.src = img.src;
}

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Date validation
document.getElementById('date_from').addEventListener('change', function() {
    var dateTo = document.getElementById('date_to');
    if (this.value && dateTo.value && this.value > dateTo.value) {
        dateTo.value = this.value;
    }
});

document.getElementById('date_to').addEventListener('change', function() {
    var dateFrom = document.getElementById('date_from');
    if (this.value && dateFrom.value && this.value < dateFrom.value) {
        dateFrom.value = this.value;
    }
});
</script>

</body>
</html>
