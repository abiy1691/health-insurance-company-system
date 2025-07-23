<?php
session_start();
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Get agent's customers' policies
$agent_id = $_SESSION['agent_id'];
$query = "SELECT cp.*, p.policy_name, p.policy_type, p.price, p.payment_interval, p.profit_rate,
          c.name as customer_name, c.email as customer_email, c.phone as customer_phone
          FROM customer_policy cp 
          JOIN policies p ON cp.policy_id = p.policy_id 
          JOIN customers c ON cp.customer_id = c.customer_id 
          WHERE c.agent_id = ? AND cp.status = 'Active'
          ORDER BY cp.start_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
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
        .table-responsive {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .table thead th {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border: none;
        }
        .table td {
            vertical-align: middle;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }
        .status-active {
            background-color: #28a745;
            color: white;
        }
        .view-details-btn {
            background: linear-gradient(45deg, #4e73df, #224abe);
            border: none;
            transition: all 0.3s ease;
        }
        .view-details-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .no-policies {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .no-policies i {
            font-size: 4rem;
            color: #4e73df;
            margin-bottom: 20px;
        }
        .customer-info {
            font-weight: 500;
            color: #4e73df;
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

                <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer Name</th>
                                <th>Policy Name</th>
                                <th>Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Price</th>
                                <th>Payment Interval</th>
                                <th>Profit Rate</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $row_number = 1;
                            while ($policy = $result->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $row_number++; ?></td>
                                <td class="customer-info">
                                    <?php echo htmlspecialchars($policy['customer_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($policy['policy_name']); ?></td>
                                <td><?php echo ucfirst($policy['policy_type']); ?></td>
                                <td><?php echo date('F j, Y', strtotime($policy['start_date'])); ?></td>
                                <td><?php echo date('F j, Y', strtotime($policy['end_date'])); ?></td>
                                <td>ETB <?php echo number_format($policy['price'], 2); ?></td>
                                <td><?php echo ucfirst($policy['payment_interval']); ?></td>
                                <td><?php echo number_format($policy['profit_rate'], 2); ?>%</td>
                                <td>
                                    <span class="status-badge status-active">Active</span>
                                </td>
                                <td>
                                    <a href="customer_policy_detail.php?policy_id=<?php echo $policy['policy_id']; ?>&customer_id=<?php echo $policy['customer_id']; ?>" 
                                       class="btn btn-primary view-details-btn">
                                        <i class="fas fa-info-circle"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-policies">
                    <i class="fas fa-file-invoice"></i>
                    <h3>No Active Policies</h3>
                    <p class="text-muted">None of your customers have active policies at the moment.</p>
                </div>
                <?php endif; ?>

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