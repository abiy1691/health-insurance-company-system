<?php
session_start();
include 'includes/config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $_POST['login'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM Managers WHERE (email = ? OR username = ?)");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['manager_id'] = $user['manager_id'];
            header("Location: manager/dashboard.php");
            exit();
        } else {
            $error = "Incorrect email or password.";
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM Agents WHERE (email = ? OR username = ?)");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['agent_id'] = $user['agent_id'];
                $_SESSION['agent_name'] = $user['name'];
                $_SESSION['agent_username'] = $user['username'];
                $_SESSION['agent_email'] = $user['email'];
                $_SESSION['agent_phone'] = $user['phone'] ?? '';
                $_SESSION['agent_address'] = $user['address'] ?? '';
                header("Location: agent/dashboard.php");
                exit();
            } else {
                $error = "Incorrect email or password.";
            }
        } else {
            $stmt = $conn->prepare("SELECT * FROM Customers WHERE (email = ? OR username = ?)");
            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Fetch complete customer data
                    $customer_stmt = $conn->prepare("SELECT * FROM Customers WHERE customer_id = ?");
                    $customer_stmt->bind_param("i", $user['customer_id']);
                    $customer_stmt->execute();
                    $customer_result = $customer_stmt->get_result();
                    $customer_data = $customer_result->fetch_assoc();
                    
                    // Set all session variables
                    $_SESSION['customer_id'] = $customer_data['customer_id'];
                    $_SESSION['customer_name'] = $customer_data['name'];
                    $_SESSION['customer_username'] = $customer_data['username'];
                    $_SESSION['customer_email'] = $customer_data['email'];
                    $_SESSION['customer_phone'] = $customer_data['phone'];
                    $_SESSION['customer_address'] = $customer_data['address'];
                    
                    header("Location: customer/dashboard.php");
                    exit();
                } else {
                    $error = "Incorrect email or password.";
                }
            } else {
                $error = "User not found.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Health Insurance</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(to right, #cfd9df, #e2ebf0);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin: 0 auto;
            display: block;
        }

        .form-control:focus {
            box-shadow: none;
        }
        
        .form-group {
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .btn-block {
            max-width: 400px;
            margin: 0 auto;
            display: block;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-container text-center">
            <img src="image/logo.png" class="logo mb-3" alt="Logo"> 
            <h2 class="text-gray-900 mb-4">Welcome</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="max-width: 400px; margin: 0 auto 20px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group mb-3">
                    <input type="text" name="login" class="form-control" placeholder="Email or Username" required>
                </div>
                <div class="form-group mb-4">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-info btn-block">Login</button>
            </form>

            <hr style="max-width: 400px; margin: 20px auto;">
            <div class="text-center">
                <a class="small text-primary" href="forgot_password.php">Forgot Password?</a>
            </div>
            <div class="text-center mt-2">
                <p class="small">Still not registered?</p>
                <a class="small text-primary" href="register.php">Register as a Customer</a>
            </div>
        </div>
    </div>

<script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>