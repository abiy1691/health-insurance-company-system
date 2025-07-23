<?php
session_start();
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $commission_rate = floatval($_POST['commission_rate']);
    $expertise = trim($_POST['expertise']);

    // Handle file upload
    $photo_url = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $_SESSION['message'] = "Only JPG, JPEG, PNG files allowed";
            $_SESSION['message_type'] = "danger";
            header("Location: ag.php");
            exit();
        }

        $upload_dir = 'uploads/agents/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $photo_name = uniqid() . '.' . $ext;
        $photo_url = $upload_dir . $photo_name;
        
        if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $photo_url)) {
            $_SESSION['message'] = "Failed to upload photo";
            $_SESSION['message_type'] = "danger";
            header("Location: ag.php");
            exit();
        }
    }

    // Validate inputs
    if (empty($name) || empty($username) || empty($email) || empty($password) || empty($expertise)) {
        $_SESSION['message'] = "All fields are required";
        $_SESSION['message_type'] = "danger";
        header("Location: ag.php");
        exit();
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT agent_id FROM Agents WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['message'] = "Username or email already exists";
        $_SESSION['message_type'] = "danger";
        header("Location: ag.php");
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new agent
    $stmt = $conn->prepare("INSERT INTO Agents (name, username, email, password, commission_rate, expertise, photo_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdss", $name, $username, $email, $hashed_password, $commission_rate, $expertise, $photo_url);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Agent registered successfully";
        $_SESSION['message_type'] = "success";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['message'] = "Error registering agent: " . $conn->error;
        $_SESSION['message_type'] = "danger";
        header("Location: ag.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Registration | Health Insurance</title>
    
    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .file-upload-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .custom-file {
            flex-grow: 1;
            margin-right: 10px;
        }
        .file-preview {
            max-width: 100px;
            max-height: 100px;
            border-radius: 5px;
            display: none;
            margin-top: 10px;
        }
    </style>
</head>
<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Agent Registration</h1>
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

                    <!-- Add Agent Form -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Agent Information</h6>
                        </div>
                        <div class="card-body">
                            <form action="ag.php" method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="form-group">
                                    <label for="commission_rate">Commission Rate (%)</label>
                                    <input type="number" class="form-control" id="commission_rate" name="commission_rate" min="0" max="100" required>
                                </div>
                                <div class="form-group">
                                    <label for="expertise">Expertise</label>
                                    <input type="text" class="form-control" id="expertise" name="expertise" required>
                                </div>
                                <div class="form-group">
                                    <label>Profile Photo</label>
                                    <div class="file-upload-wrapper">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="profile_photo" name="profile_photo" accept="image/jpeg, image/png">
                                            <label class="custom-file-label" for="profile_photo">Choose file</label>
                                        </div>
                                    </div>
                                    <img id="photo-preview" class="file-preview" alt="Profile Preview">
                                </div>
                                <button type="submit" class="btn btn-primary">Register Agent</button>
                                <a href="login.php" class="btn btn-secondary">Back to Login</a>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End of Page Content -->
            </div>
            <!-- End of Main Content -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <script>
        $(document).ready(function() {
            // File input preview
            $('#profile_photo').change(function(e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#photo-preview').attr('src', e.target.result).show();
                    }
                    reader.readAsDataURL(file);
                    $('.custom-file-label').text(file.name);
                }
            });
        });
    </script>
</body>
</html>