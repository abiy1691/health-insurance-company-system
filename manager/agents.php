<?php
session_start();
if (!isset($_SESSION['manager_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Handle agent deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $agent_id = intval($_GET['delete']);
    
    // Delete agent from database
    $stmt = $conn->prepare("DELETE FROM Agents WHERE agent_id = ?");
    $stmt->bind_param("i", $agent_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Agent deleted successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting agent";
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: agents.php");
    exit();
}

// Get total agents count
$total_agents = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM Agents");
if ($result) {
    $total_agents = $result->fetch_assoc()['total'];
}

// Get all agents
$agents = [];
$result = $conn->query("SELECT * FROM Agents ORDER BY agent_id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $agents[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Agents | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .agent-photo {
            width: 25px;
            height: 25px;
            object-fit: cover;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .agent-photo:hover {
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
        .total-agents-card {
            padding: 0.5rem !important;
        }
        .total-agents-card .h5 {
            margin-bottom: 0 !important;
        }
        .total-agents-card .text-xs {
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
                    <h1 class="h3 mb-0 text-gray-800">Manage Agents</h1>
                    <a href="add_agent.php" class="btn btn-primary">
                        <i class="fas fa-plus fa-sm text-white-50"></i> Add New Agent
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

                <!-- Total Agents Card -->
                <div class="row mb-4">
                    <div class="col-xl-12">
                        <div class="card shadow h-100 py-1 total-agents-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Agents</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_agents ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Agents Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-primary">Agents List</h6>
                    </div>
                    <div class="card-body p-2">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Commission Rate</th>
                                        <th>Expertise</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($agents as $index => $agent): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($agent['name']) ?></td>
                                        <td><?= htmlspecialchars($agent['username']) ?></td>
                                        <td><?= htmlspecialchars($agent['email']) ?></td>
                                        <td><?= htmlspecialchars($agent['commission_rate']) ?>%</td>
                                        <td><?= htmlspecialchars($agent['expertise']) ?></td>
                                        <td>
                                            <a href="update_agent.php?id=<?= $agent['agent_id'] ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-edit"></i> Update
                                            </a>
                                            <a href="javascript:void(0)" onclick="confirmDelete(<?= $agent['agent_id'] ?>)" class="btn btn-danger btn-sm">
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
                <h5 class="modal-title" id="photoModalLabel">Agent Photo</h5>
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

    function confirmDelete(agentId) {
        if (confirm('Are you sure you want to delete this agent?')) {
            window.location.href = 'agents.php?delete=' + agentId;
        }
    }

    function showPhoto(photoSrc) {
        $('#modalPhoto').attr('src', photoSrc);
        $('#photoModal').modal('show');
    }
</script>
</body>
</html>
