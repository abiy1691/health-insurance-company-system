<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/config.php';  // Database connection file

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $age = $_POST['age'];
    $profile_photo = $_FILES['profile_photo'];
    $gender = $_POST['gender'];

    $errors = [];
    $phone_error = '';

    // Validate fields
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }
    if (empty($phone) || !preg_match('/^[79]\d{8}$/', $phone)) {
        $phone_error = 'Phone number must start with 7 or 9 and be 9 digits long.';
        $errors[] = $phone_error;
    }
    if (empty($address)) {
        $errors[] = 'Address is required.';
    }
    if (empty($age) || !is_numeric($age)) {
        $errors[] = 'Valid age is required.';
    }

    // Handle photo upload (optional)
    $photo_path = null;
    if ($profile_photo['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($profile_photo['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_ext)) {
            $errors[] = 'Profile photo must be in JPG or PNG format.';
        } else {
            $upload_dir = 'HEALTH INSURANCE/uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $photo_name = uniqid() . '.' . $file_ext;
            $photo_path = $upload_dir . $photo_name;
            
            if (!move_uploaded_file($profile_photo['tmp_name'], $photo_path)) {
                $errors[] = 'Failed to upload profile photo.';
                $photo_path = null;
            }
        }
    }

    // If there are no validation errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL statement
        $sql = "INSERT INTO customers (name, username, email, password, phone, address, age, profile_photo, gender) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        // Bind parameters
        $bind_result = $stmt->bind_param("sssssssss", $name, $username, $email, $hashed_password, $phone, $address, $age, $photo_path, $gender);
        
        if ($bind_result === false) {
            die("Error binding parameters: " . $stmt->error);
        }

        // Execute the statement
        if ($stmt->execute()) {
            // Registration successful
            $_SESSION['registration_success'] = true;
            header("Location: login.php");
            exit();
        } else {
            $errors[] = 'Database error: ' . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            background: linear-gradient(to right, #cfd9df, #e2ebf0);
            font-family: 'Nunito', sans-serif;
        }
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-container img {
            width: 120px;
            height: auto;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .phone-section {
            display: flex;
            align-items: center;
        }
        .phone-prefix {
            width: 80px;
            margin-right: 10px;
        }
        .phone-number {
            flex-grow: 1;
        }
        .file-upload-wrapper {
            display: flex;
            align-items: center;
        }
        .custom-file {
            flex-grow: 1;
            margin-right: 10px;
        }
        .file-preview {
            max-width: 80px;
            max-height: 80px;
            border-radius: 5px;
            display: none;
        }
        .alert {
            transition: opacity 0.5s ease-out;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="logo-container">
        <img src="image/logo.png" alt="Health Insurance Logo">
    </div>

    <h4 class="text-center mb-4">Customer Registration</h4>

    <!-- Display phone number validation error -->
    <?php if (!empty($phone_error)): ?>
        <div class="alert alert-danger" id="phone-error">
            <?= htmlspecialchars($phone_error) ?>
        </div>
    <?php endif; ?>

    <!-- Display other validation errors -->
    <?php if (!empty($errors) && empty($phone_error)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Registration form -->
    <form action="register.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <input type="text" class="form-control" name="name" placeholder="Full Name" value="<?= htmlspecialchars($name ?? '') ?>" required>
        </div>

        <div class="form-group">
            <input type="text" class="form-control" name="username" placeholder="Username" value="<?= htmlspecialchars($username ?? '') ?>" required>
        </div>

        <div class="form-group">
            <input type="email" class="form-control" name="email" placeholder="Email Address" value="<?= htmlspecialchars($email ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="gender">Gender</label>
            <select class="form-control" id="gender" name="gender" required>
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>
        </div>

        <div class="form-group">
            <input type="password" class="form-control" name="password" placeholder="Password" required>
            <small class="form-text text-muted">Minimum 8 characters</small>
        </div>

        <div class="form-group">
            <div class="phone-section">
                <input type="text" class="form-control phone-prefix" value="+251" readonly>
                <input type="text" class="form-control phone-number" name="phone" placeholder="Phone Number" value="<?= htmlspecialchars($phone ?? '') ?>" required>
            </div>
            <small class="form-text text-muted">Must start with 7 or 9 and be 9 digits</small>
        </div>

        <div class="form-group">
            <input type="text" class="form-control" name="address" placeholder="Address" value="<?= htmlspecialchars($address ?? '') ?>" required>
        </div>

        <div class="form-group">
            <input type="number" class="form-control" name="age" placeholder="Age" value="<?= htmlspecialchars($age ?? '') ?>" required>
        </div>

        <div class="form-group">
            <div class="file-upload-wrapper">
                <div class="custom-file">
                    <input type="file" class="custom-file-input" name="profile_photo" id="profile-photo-input" accept="image/png, image/jpeg">
                    <label class="custom-file-label" for="profile-photo-input">Choose profile photo</label>
                </div>
                <img id="profile-photo-preview" class="file-preview" alt="Profile Preview">
            </div>
            <small class="form-text text-muted">Optional (JPG or PNG only)</small>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg mt-4">Register</button>
    </form>

    <p class="text-center mt-4">
        Already have an account? <a href="login.php">Login here</a>
    </p>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        // Hide phone error after 3 seconds
        if ($('#phone-error').length) {
            setTimeout(function() {
                $('#phone-error').fadeOut();
            }, 3000);
        }

        // Preview profile photo
        $('#profile-photo-input').change(function(e) {
            var fileName = e.target.files[0].name;
            $('.custom-file-label').text(fileName);
            
            if (e.target.files && e.target.files[0]) {
                var reader = new FileReader();
                reader.onload = function(event) {
                    $('#profile-photo-preview').attr('src', event.target.result).show();
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Validate phone number on input
        $('input[name="phone"]').on('input', function() {
            var phone = $(this).val();
            if (!/^[79]\d{0,8}$/.test(phone) || phone.length > 9) {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
    });
</script>
</body>
</html>