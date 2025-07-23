<?php
session_start();
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Get the logged-in agent's ID
$agent_id = $_SESSION['agent_id'];

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : '';

// Build the query with filters - only for the logged-in agent
$query = "SELECT c.*, p.policy_name, cu.name as customer_name, cu.customer_id 
          FROM commissions c 
          LEFT JOIN policies p ON c.policy_id = p.policy_id 
          LEFT JOIN customers cu ON c.customer_id = cu.customer_id 
          WHERE c.agent_id = ?";

$params = array($agent_id);
$types = "i";

if (!empty($date_from)) {
    $query .= " AND DATE(c.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND DATE(c.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if (!empty($customer_id)) {
    $query .= " AND c.customer_id = ?";
    $params[] = $customer_id;
    $types .= "i";
}

$query .= " ORDER BY c.created_at DESC";

// Prepare and execute the query with filters
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();

// Get agent name for display
$agent_query = "SELECT name FROM agents WHERE agent_id = ?";
$agent_stmt = $conn->prepare($agent_query);
$agent_stmt->bind_param("i", $agent_id);
$agent_stmt->execute();
$agent_result = $agent_stmt->get_result();
$agent_name = $agent_result->fetch_assoc()['name'];

// Get all customers assigned to this agent for the filter dropdown
$customers_query = "SELECT DISTINCT cu.customer_id, cu.name 
                   FROM customers cu 
                   INNER JOIN commissions c ON cu.customer_id = c.customer_id 
                   WHERE c.agent_id = ? 
                   ORDER BY cu.name";
$customers_stmt = $conn->prepare($customers_query);
$customers_stmt->bind_param("i", $agent_id);
$customers_stmt->execute();
$customers_result = $customers_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Commissions | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
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
                <h1 class="h3 mb-4 text-gray-800">My Commissions</h1>

                <!-- Summary Card -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Commission Amount</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">ETB <?php 
                                            $result->data_seek(0);
                                            $total = 0;
                                            while ($row = $result->fetch_assoc()) {
                                                $total += $row['commission_amount'];
                                            }
                                            echo number_format($total, 2);
                                            $result->data_seek(0);
                                        ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row">
                            <div class="col-md-4 mb-3">
                                <label for="customer_id">Customer</label>
                                <select class="form-control" id="customer_id" name="customer_id">
                                    <option value="">All Customers</option>
                                    <?php while ($customer = $customers_result->fetch_assoc()): ?>
                                        <option value="<?php echo $customer['customer_id']; ?>" 
                                                <?php echo $customer_id == $customer['customer_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="date_from">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="date_to">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-12 mb-3 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <a href="commissions.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Commission List -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">My Commission List</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Customer Name</th>
                                        <th>Policy Name</th>
                                        <th>Commission Amount</th>
                                        <th>Date</th>
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
                                        <td>ETB <?php echo number_format($row['commission_amount'], 2); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td colspan="3" class="text-right">Total Commission Amount:</td>
                                        <td>ETB <?php 
                                            $result->data_seek(0);
                                            $total = 0;
                                            while ($row = $result->fetch_assoc()) {
                                                $total += $row['commission_amount'];
                                            }
                                            echo number_format($total, 2);
                                        ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
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
<script src="../style/vendor/jquery/jquery.min.js"></script>
<script src="../style/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="../style/vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="../style/js/sb-admin-2.min.js"></script>

<!-- Page level plugins -->
<script src="../style/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../style/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable();
});
</script>

</body>
</html> 