<?php
session_start();
include '../includes/config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in as agent
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../login.php");
    exit();
}

$agent_id = $_SESSION['agent_id'];

// Get customer ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: customers.php");
    exit();
}

$customer_id = intval($_GET['id']);

// Verify that this customer belongs to the logged-in agent
$verify_query = "SELECT * FROM customers WHERE customer_id = ? AND agent_id = ?";
$stmt = $conn->prepare($verify_query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error . "<br>Query: " . $verify_query);
}
$stmt->bind_param("ii", $customer_id, $agent_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: customers.php");
    exit();
}

$customer = $result->fetch_assoc();

// Get family members
$family_query = "SELECT * FROM families WHERE customer_id = ? ORDER BY name ASC";
$stmt = $conn->prepare($family_query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error . "<br>Query: " . $family_query);
}
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$family_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family Members - Health Insurance</title>
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
                        <h1 class="page-title">Family Members of <?php echo htmlspecialchars($customer['name']); ?></h1>
                        <a href="customers.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Customers
                        </a>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Family Members List</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Relationship</th>
                                            <th>Age</th>
                                            <th>Gender</th>
                                            <th>Phone</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($family_result->num_rows > 0) {
                                            while ($member = $family_result->fetch_assoc()) {
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['relationship']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['age']); ?> years</td>
                                                    <td><?php echo htmlspecialchars($member['gender']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                                    <td>
                                                        <a href="view_family_member.php?id=<?php echo $member['member_id']; ?>" class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No family members found</td>
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