<?php
session_start();
if (!isset($_SESSION['manager_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Handle status update
if (isset($_POST['update_status'])) {
    $customer_id = $_POST['customer_id'];
    $policy_id = $_POST['policy_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE customer_policy SET status = ? WHERE customer_id = ? AND policy_id = ?");
    $stmt->bind_param("sii", $new_status, $customer_id, $policy_id);
    $stmt->execute();
    $stmt->close();
}

// Handle deletion
if (isset($_POST['delete_policy'])) {
    $customer_id = $_POST['customer_id'];
    $policy_id = $_POST['policy_id'];
    
    $stmt = $conn->prepare("DELETE FROM customer_policy WHERE customer_id = ? AND policy_id = ?");
    $stmt->bind_param("ii", $customer_id, $policy_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch all customer policies
$query = "SELECT cp.*, c.name as customer_name, p.policy_name 
          FROM customer_policy cp 
          JOIN customers c ON cp.customer_id = c.customer_id 
          JOIN policies p ON cp.policy_id = p.policy_id 
          ORDER BY cp.start_date DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Policies | Health Insurance</title>
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
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-delete:hover {
            background-color: #c82333;
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
                <h1 class="h3 mb-4 text-gray-800">Customer Policies</h1>

                <!-- DataTales Example -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Policy List</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Customer Name</th>
                                        <th>Policy Name</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
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
                                        <td><?php echo date('F j, Y', strtotime($row['start_date'])); ?></td>
                                        <td><?php echo date('F j, Y', strtotime($row['end_date'])); ?></td>
                                        <td>
                                            <span class="status-<?php echo strtolower($row['status']); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="customer_id" value="<?php echo $row['customer_id']; ?>">
                                                <input type="hidden" name="policy_id" value="<?php echo $row['policy_id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $row['status'] == 'Active' ? 'Pending' : 'Active'; ?>">
                                                <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                                    <?php echo $row['status'] == 'Active' ? 'Set Pending' : 'Set Active'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this policy?');">
                                                <input type="hidden" name="customer_id" value="<?php echo $row['customer_id']; ?>">
                                                <input type="hidden" name="policy_id" value="<?php echo $row['policy_id']; ?>">
                                                <button type="submit" name="delete_policy" class="btn btn-sm btn-delete">
                                                    <i class="fas fa-trash"></i> Delete
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

