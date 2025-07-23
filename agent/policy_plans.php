<?php
session_start();
include '../includes/config.php';

// Check if user is logged in as agent
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get all policies using prepared statement
$query = "SELECT policy_name, policy_type, description, price, profit_rate, policy_term, payment_interval 
          FROM policies";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy Plans - Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="page-title">Policy Plans</h1>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Available Policy Plans</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Policy Name</th>
                                            <th>Policy Type</th>
                                            <th>Description</th>
                                            <th>Price (ETB)</th>
                                            <th>Profit Rate</th>
                                            <th>Policy Term</th>
                                            <th>Payment Interval</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result->num_rows > 0) {
                                            while ($policy = $result->fetch_assoc()) {
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($policy['policy_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($policy['policy_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($policy['description']); ?></td>
                                                    <td><?php echo number_format($policy['price'], 2); ?> ETB</td>
                                                    <td><?php echo htmlspecialchars($policy['profit_rate']); ?>%</td>
                                                    <td><?php echo htmlspecialchars($policy['policy_term']); ?> years</td>
                                                    <td><?php echo htmlspecialchars($policy['payment_interval']); ?></td>
                                                </tr>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No policy plans available</td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'includes/footer.php'; ?>
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

    <!-- Page level custom scripts -->
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": [[0, "asc"]],
                "pageLength": 10
            });
        });
    </script>
</body>
</html>
