<?php
session_start();
if (!isset($_SESSION['manager_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Handle customer deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $customer_id = intval($_GET['delete']);
    
    // Delete customer from database
    $stmt = $conn->prepare("DELETE FROM Customers WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Customer deleted successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting customer";
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: customers.php");
    exit();
}

// Get total customers count
$total_customers = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM Customers");
if ($result) {
    $total_customers = $result->fetch_assoc()['total'];
}

// Get all customers with their assigned agents
$customers = [];
$result = $conn->query("SELECT c.*, a.name as agent_name 
                       FROM Customers c 
                       LEFT JOIN Agents a ON c.agent_id = a.agent_id 
                       ORDER BY c.customer_id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .customer-photo {
            width: 25px;
            height: 25px;
            object-fit: cover;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .customer-photo:hover {
            transform: scale(1.1);
        }
        .modal-photo {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
        }
        .card-header {
            padding: 0.5rem 1.25rem;
        }
        .card-body {
            padding: 0.75rem 1.25rem;
        }
        .total-customers-card {
            padding: 0.5rem !important;
        }
        .total-customers-card .h5 {
            margin-bottom: 0 !important;
        }
        .total-customers-card .text-xs {
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
                    <h1 class="h3 mb-0 text-gray-800">Manage Customers</h1>
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

                <!-- Total Customers Card -->
                <div class="row mb-4">
                    <div class="col-xl-12">
                        <div class="card shadow h-100 py-1 total-customers-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Customers</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_customers ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customers Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-primary">Customers List</h6>
                    </div>
                    <div class="card-body p-2">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Age</th>
                                        <th>Date Registered</th>
                                        <th>Assigned Agent</th>
                                        <th>Photo</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customers as $index => $customer): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($customer['name']) ?></td>
                                        <td><?= htmlspecialchars($customer['email']) ?></td>
                                        <td>+251<?= htmlspecialchars($customer['phone']) ?></td>
                                        <td><?= htmlspecialchars($customer['address']) ?></td>
                                        <td>
                                            <?php
                                            if (isset($customer['age']) && !empty($customer['age'])) {
                                                echo $customer['age'] . " years";
                                            } else {
                                                echo '<span class="text-muted">Not available</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?= date('M d, Y', strtotime($customer['created_at'])) ?>
                                        </td>
                                        <td><?= $customer['agent_name'] ? htmlspecialchars($customer['agent_name']) : '<span class="text-muted">Not assigned</span>' ?></td>
                                        <td>
                                            <?php if (!empty($customer['photo'])): ?>
                                                <img src="../uploads/customers/<?= htmlspecialchars($customer['photo']) ?>" 
                                                     alt="Customer Photo" 
                                                     class="customer-photo"
                                                     onclick="showPhoto(this.src)">
                                            <?php else: ?>
                                                <img src="../uploads/default-customer.jpg" 
                                                     alt="Default Photo" 
                                                     class="customer-photo"
                                                     onclick="showPhoto(this.src)">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="update_customer.php?id=<?= $customer['customer_id'] ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-edit"></i> Update
                                            </a>
                                            <a href="javascript:void(0)" onclick="confirmDelete(<?= $customer['customer_id'] ?>)" class="btn btn-danger btn-sm">
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

<!-- Photo Modal -->
<div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalLabel">Customer Photo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalPhoto" src="" alt="Enlarged Photo" class="modal-photo">
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#dataTable').DataTable();
    });

    function confirmDelete(customerId) {
        if (confirm('Are you sure you want to delete this customer?')) {
            window.location.href = 'customers.php?delete=' + customerId;
        }
    }

    function showPhoto(photoSrc) {
        $('#modalPhoto').attr('src', photoSrc);
        $('#photoModal').modal('show');
    }
</script>
</body>
</html>
