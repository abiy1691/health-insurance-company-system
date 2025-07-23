<?php
session_start();
if (!isset($_SESSION['manager_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$message = '';
$message_type = '';

// Get customer ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: customers.php");
    exit();
}

$customer_id = intval($_GET['id']);

// Fetch customer data
$stmt = $conn->prepare("SELECT c.*, a.name as agent_name, a.agent_id as assigned_agent_id 
                       FROM Customers c 
                       LEFT JOIN Agents a ON c.agent_id = a.agent_id 
                       WHERE c.customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if (!$customer) {
    header("Location: customers.php");
    exit();
}

// Fetch all available agents
$agents = [];
$result = $conn->query("SELECT agent_id, name FROM Agents ORDER BY name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $agents[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : null;
    $password = trim($_POST['password']);
    
    // Validate phone number
    if (!preg_match('/^[79]\d{8}$/', $phone)) {
        $message = "Phone number must start with 7 or 9 and be 9 digits long";
        $message_type = "danger";
    } else {
        // Check uniqueness of username, email, and phone across all tables
        $check_query = "SELECT 
            (SELECT COUNT(*) FROM Customers WHERE (username = ? OR email = ? OR phone = ?) AND customer_id != ?) +
            (SELECT COUNT(*) FROM Managers WHERE username = ? OR email = ?) +
            (SELECT COUNT(*) FROM Agents WHERE username = ? OR email = ?) as total";
        
        $check_stmt = $conn->prepare($check_query);
        if ($check_stmt) {
            $check_stmt->bind_param("sssissss", 
                $username, $email, $phone, $customer_id,
                $username, $email,
                $username, $email
            );
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total'] > 0) {
                $message = "Username, email, or phone number already exists in the system!";
                $message_type = "danger";
            } else {
                // Update customer
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE Customers SET name = ?, username = ?, email = ?, phone = ?, address = ?, agent_id = ?, password = ? WHERE customer_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("sssssisi", $name, $username, $email, $phone, $address, $agent_id, $hashed_password, $customer_id);
                } else {
                    $update_query = "UPDATE Customers SET name = ?, username = ?, email = ?, phone = ?, address = ?, agent_id = ? WHERE customer_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("sssssii", $name, $username, $email, $phone, $address, $agent_id, $customer_id);
                }
                
                if ($update_stmt->execute()) {
                    $_SESSION['message'] = "Customer data updated successfully!";
                    $_SESSION['message_type'] = "success";
                    header("Location: customers.php");
                    exit();
                } else {
                    $message = "Error updating customer: " . $update_stmt->error;
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
    <title>Update Customer | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .phone-input-group {
            display: flex;
            align-items: center;
        }
        .phone-prefix {
            width: 80px;
            text-align: center;
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-radius: 0.25rem 0 0 0.25rem;
            padding: 0.375rem 0.75rem;
        }
        .phone-number {
            border-radius: 0 0.25rem 0.25rem 0;
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
                    <h1 class="h3 mb-0 text-gray-800">Update Customer</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Update Customer Form -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Customer Information</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($customer['name']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($customer['username']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <div class="phone-input-group">
                                            <span class="phone-prefix">+251</span>
                                            <input type="text" class="form-control phone-number" id="phone" name="phone" 
                                                   value="<?= substr(htmlspecialchars($customer['phone']), 4) ?>" 
                                                   pattern="[79]\d{8}" 
                                                   title="Phone number must start with 7 or 9 and be 9 digits long"
                                                   required>
                                        </div>
                                        <small class="form-text text-muted">Enter 9 digits starting with 7 or 9</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($customer['address']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="agent_id">Assign Agent</label>
                                        <select class="form-control" id="agent_id" name="agent_id">
                                            <option value="">-- Select Agent --</option>
                                            <?php foreach ($agents as $agent): ?>
                                                <option value="<?= $agent['agent_id'] ?>" <?= ($customer['assigned_agent_id'] == $agent['agent_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($agent['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">New Password (Leave blank to keep current password)</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Update Customer</button>
                                    <a href="customers.php" class="btn btn-secondary">Cancel</a>
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