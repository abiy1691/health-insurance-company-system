<?php
session_start();
if (!isset($_SESSION['manager_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Check if table exists and get its structure
$check_table = "SHOW TABLES LIKE 'commissions'";
$table_exists = $conn->query($check_table)->num_rows > 0;

if ($table_exists) {
    // Check if the column needs to be renamed
    $check_column = "SHOW COLUMNS FROM commissions LIKE 'amount'";
    $column_exists = $conn->query($check_column)->num_rows > 0;
    
    if ($column_exists) {
        // Rename the column from 'amount' to 'commission_amount'
        $alter_query = "ALTER TABLE commissions CHANGE amount commission_amount DECIMAL(10,2) NOT NULL";
        $conn->query($alter_query);
    }
    
    // Get column names
    $columns_query = "SHOW COLUMNS FROM commissions";
    $columns_result = $conn->query($columns_query);
    $date_column = '';
    while ($column = $columns_result->fetch_assoc()) {
        if (in_array($column['Field'], ['created_at', 'date_created', 'timestamp'])) {
            $date_column = $column['Field'];
            break;
        }
    }
    
    if (empty($date_column)) {
        // Add date column if it doesn't exist
        $alter_query = "ALTER TABLE commissions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $conn->query($alter_query);
        $date_column = 'created_at';
    }
} else {
    // Create table if it doesn't exist
    $create_table_query = "CREATE TABLE commissions (
        commission_id INT PRIMARY KEY AUTO_INCREMENT,
        agent_id INT NOT NULL,
        customer_id INT NOT NULL,
        policy_id INT NOT NULL,
        commission_amount DECIMAL(10,2) NOT NULL,
        status ENUM('Pending', 'Paid', 'Cancelled') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (agent_id) REFERENCES agents(agent_id),
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
        FOREIGN KEY (policy_id) REFERENCES policies(policy_id)
    )";
    $conn->query($create_table_query);
    $date_column = 'created_at';
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['commission_id'])) {
        $commission_id = $_POST['commission_id'];
        
        if (isset($_POST['mark_paid'])) {
            $update_query = "UPDATE commissions SET status = 'Paid' WHERE commission_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $commission_id);
            $stmt->execute();
        } elseif (isset($_POST['mark_cancelled'])) {
            $update_query = "UPDATE commissions SET status = 'Cancelled' WHERE commission_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $commission_id);
            $stmt->execute();
        }
    }
}

// Get filter parameters
$agent_filter = isset($_GET['agent']) ? $_GET['agent'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build the query with filters
$query = "SELECT c.*, a.name as agent_name, p.policy_name, cu.name as customer_name 
          FROM commissions c 
          LEFT JOIN agents a ON c.agent_id = a.agent_id 
          LEFT JOIN policies p ON c.policy_id = p.policy_id 
          LEFT JOIN customers cu ON c.customer_id = cu.customer_id 
          WHERE 1=1";

$params = array();
$types = "";

if (!empty($agent_filter)) {
    $query .= " AND c.agent_id = ?";
    $params[] = $agent_filter;
    $types .= "i";
}

if (!empty($date_from)) {
    $query .= " AND DATE(c.$date_column) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND DATE(c.$date_column) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY c.$date_column DESC";

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

// Debug information
if ($result->num_rows === 0) {
    echo "No commissions found in the database.";
} else {
    // Get the first row to check its structure
    $first_row = $result->fetch_assoc();
    echo "<!-- Debug: Available keys in result: " . implode(", ", array_keys($first_row)) . " -->";
    // Reset the result pointer
    $result->data_seek(0);
}

// Get all agents for the filter dropdown
$agents_query = "SELECT agent_id, name FROM agents ORDER BY name";
$agents_result = $conn->query($agents_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission Management | Health Insurance</title>
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
        .status-paid {
            background-color: #28a745;
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
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
                <h1 class="h3 mb-4 text-gray-800">Commission Management</h1>

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
                                <label for="agent">Agent</label>
                                <select class="form-control" id="agent" name="agent">
                                    <option value="">All Agents</option>
                                    <?php while ($agent = $agents_result->fetch_assoc()): ?>
                                        <option value="<?php echo $agent['agent_id']; ?>" 
                                                <?php echo $agent_filter == $agent['agent_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($agent['name']); ?>
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
                        <h6 class="m-0 font-weight-bold text-primary">Commission List</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Agent Name</th>
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
                                        <td><?php echo htmlspecialchars($row['agent_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['policy_name']); ?></td>
                                        <td>ETB <?php echo number_format($row['commission_amount'], 2); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($row[$date_column])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td colspan="4" class="text-right">Total Commission Amount:</td>
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