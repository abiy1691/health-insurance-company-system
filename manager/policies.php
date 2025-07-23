<?php
session_start();
if (!isset($_SESSION['manager_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Handle policy deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $policy_id = intval($_GET['delete']);
    
    // Delete policy from database
    $stmt = $conn->prepare("DELETE FROM Policies WHERE policy_id = ?");
    $stmt->bind_param("i", $policy_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Policy deleted successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting policy";
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: policies.php");
    exit();
}

// Get total policies count
$total_policies = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM Policies");
if ($result) {
    $total_policies = $result->fetch_assoc()['total'];
}

// Get all policies
$policies = [];
$result = $conn->query("SELECT * FROM Policies ORDER BY policy_id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $policies[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Policies | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-header {
            padding: 0.5rem 1.25rem;
        }
        .card-body {
            padding: 0.75rem 1.25rem;
        }
        .total-policies-card {
            padding: 0.5rem !important;
        }
        .total-policies-card .h5 {
            margin-bottom: 0 !important;
        }
        .total-policies-card .text-xs {
            margin-bottom: 0.25rem !important;
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
                    <h1 class="h3 mb-0 text-gray-800">Manage Policies</h1>
                    <a href="add_policy.php" class="btn btn-primary">
                        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Policy
                    </a>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
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

                <!-- Total Policies Card -->
                <div class="row mb-4">
                    <div class="col-xl-12">
                        <div class="card shadow h-100 py-1 total-policies-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Policies</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_policies ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-contract fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Policies Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-primary">Policies List</h6>
                    </div>
                    <div class="card-body p-2">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Policy Name</th>
                                        <th>Policy Type</th>
                                        <th>Description</th>
                                        <th>Price(ETB)</th>
                                        <th>Profit Rate</th>
                                        <th>Policy Term</th>
                                        <th>Payment Interval</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($policies as $index => $policy): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($policy['policy_name']) ?></td>
                                        <td><?= htmlspecialchars($policy['policy_type']) ?></td>
                                        <td><?= htmlspecialchars($policy['description']) ?></td>
                                        <td><?= number_format($policy['price'], 2) ?></td>
                                        <td><?= number_format($policy['profit_rate'], 2) ?>%</td>
                                        <td><?= $policy['policy_term'] ?> years</td>
                                        <td><?= htmlspecialchars($policy['payment_interval']) ?></td>
                                        <td>
                                            <a href="update_policy.php?id=<?= $policy['policy_id'] ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-edit"></i> Update
                                            </a>
                                            <a href="javascript:void(0)" onclick="confirmDelete(<?= $policy['policy_id'] ?>)" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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

<!-- Page level plugins -->
<script src="../style/js/jquery.dataTables.min.js"></script>
<script src="../style/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        $('#dataTable').DataTable();
    });

    function confirmDelete(policyId) {
        if (confirm('Are you sure you want to delete this policy?')) {
            window.location.href = 'policies.php?delete=' + policyId;
        }
    }
</script>
</body>
</html>
