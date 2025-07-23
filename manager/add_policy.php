<?php
session_start();
if (!isset($_SESSION['manager_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $policy_name = trim($_POST['policy_name']);
    $policy_type = trim($_POST['policy_type']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $profit_rate = floatval($_POST['profit_rate']);
    $policy_term = intval($_POST['policy_term']);
    $payment_interval = trim($_POST['payment_interval']);
    $manager_id = $_SESSION['manager_id'];
    
    // Validate input
    if (empty($policy_name) || empty($policy_type) || empty($description) || 
        empty($price) || empty($profit_rate) || empty($policy_term) || 
        empty($payment_interval)) {
        $message = "All fields are required!";
        $message_type = "danger";
    } else {
        // Check if policy name already exists
        $check_query = "SELECT COUNT(*) as count FROM Policies WHERE policy_name = ?";
        $check_stmt = $conn->prepare($check_query);
        
        if ($check_stmt) {
            $check_stmt->bind_param("s", $policy_name);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $message = "Policy name already exists!";
                $message_type = "danger";
            } else {
                // Insert new policy
                $insert_query = "INSERT INTO Policies (policy_name, policy_type, description, price, 
                                profit_rate, policy_term, payment_interval, manager_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                
                if ($insert_stmt) {
                    $insert_stmt->bind_param("sssddiss", $policy_name, $policy_type, $description, 
                                           $price, $profit_rate, $policy_term, $payment_interval, 
                                           $manager_id);
                    
                    if ($insert_stmt->execute()) {
                        $_SESSION['message'] = "Policy added successfully!";
                        $_SESSION['message_type'] = "success";
                        header("Location: policies.php");
                        exit();
                    } else {
                        $message = "Error adding policy: " . $insert_stmt->error;
                        $message_type = "danger";
                    }
                } else {
                    $message = "Error preparing statement: " . $conn->error;
                    $message_type = "danger";
                }
            }
        } else {
            $message = "Error preparing check statement: " . $conn->error;
            $message_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Policy | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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
                    <h1 class="h3 mb-0 text-gray-800">Add New Policy</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Add Policy Form -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Policy Information</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="policy_name">Policy Name</label>
                                        <input type="text" class="form-control" id="policy_name" name="policy_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="policy_type">Policy Type</label>
                                        <select class="form-control" id="policy_type" name="policy_type" required>
                                            <option value="">Select Policy Type</option>
                                            <option value="personal">personal</option>
                                            <option value="with family">with family</option>
                                            
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="price">Price</label>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="profit_rate">Profit Rate (%)</label>
                                        <input type="number" class="form-control" id="profit_rate" name="profit_rate" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="policy_term">Policy Term (in years)</label>
                                        <input type="number" class="form-control" id="policy_term" name="policy_term" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_interval">Payment Interval</label>
                                        <select class="form-control" id="payment_interval" name="payment_interval" required>
                                            <option value="">Select Payment Interval</option>
                                            <option value="monthly">Monthly</option>
                                            <option value="quarterly">Quarterly</option>
                                            <option value="half_annually">Half Annually</option>
                                            <option value="annually">Annually</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Add Policy</button>
                                    <a href="policies.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </div>
                        </form>
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