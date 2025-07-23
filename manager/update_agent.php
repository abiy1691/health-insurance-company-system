<?php
session_start();
if (!isset($_SESSION['manager_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$message = '';
$message_type = '';

// Get agent ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: agents.php");
    exit();
}

$agent_id = intval($_GET['id']);

// Fetch agent data
$stmt = $conn->prepare("SELECT * FROM Agents WHERE agent_id = ?");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
$agent = $result->fetch_assoc();

if (!$agent) {
    header("Location: agents.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $commission_rate = floatval($_POST['commission_rate']);
    $expertise = trim($_POST['expertise']);
    
    // Check if username or email already exists for other agents
    $check_query = "SELECT COUNT(*) as count FROM Agents WHERE (username = ? OR email = ?) AND agent_id != ?";
    $check_stmt = $conn->prepare($check_query);
    
    if ($check_stmt) {
        $check_stmt->bind_param("ssi", $username, $email, $agent_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $message = "Username or email already exists!";
            $message_type = "danger";
        } else {
            // Update agent
            $update_query = "UPDATE Agents SET name = ?, username = ?, email = ?, commission_rate = ?, expertise = ? WHERE agent_id = ?";
            $update_stmt = $conn->prepare($update_query);
            
            if ($update_stmt) {
                $update_stmt->bind_param("sssdsi", $name, $username, $email, $commission_rate, $expertise, $agent_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['message'] = "Agent data updated successfully!";
                    $_SESSION['message_type'] = "success";
                    header("Location: agents.php");
                    exit();
                } else {
                    $message = "Error updating agent: " . $update_stmt->error;
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Agent | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .preview-photo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .preview-photo:hover {
            transform: scale(1.1);
        }
        .modal-photo {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
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
                    <h1 class="h3 mb-0 text-gray-800">Update Agent</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Update Agent Form -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Agent Information</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($agent['name']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($agent['username']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($agent['email']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="commission_rate">Commission Rate (%)</label>
                                        <input type="number" class="form-control" id="commission_rate" name="commission_rate" step="0.01" value="<?= htmlspecialchars($agent['commission_rate']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="expertise">Expertise</label>
                                        <input type="text" class="form-control" id="expertise" name="expertise" value="<?= htmlspecialchars($agent['expertise']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Update Agent</button>
                                    <a href="agents.php" class="btn btn-secondary">Cancel</a>
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

<!-- Photo Preview Modal -->
<div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalLabel">Preview Photo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalPhoto" src="" alt="Preview Photo" class="modal-photo">
            </div>
        </div>
    </div>
</div>

<script>
    function previewImage(input) {
        const preview = document.querySelector('.preview-photo');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function showPhoto(photoSrc) {
        document.getElementById('modalPhoto').src = photoSrc;
        $('#photoModal').modal('show');
    }
</script>

</body>
</html> 