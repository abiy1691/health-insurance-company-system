<?php
// Start the session to store error or success messages
session_start();

// Include the database configuration file
include 'includes/config.php';  // Database connection file

// Initialize variables for form data and error messages
$name = $username = $email = $password = "";
$errors = [];

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate form data
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

    // Check if there are no validation errors
    if (empty($errors)) {
        // Check for existing username or email in managers, customers, or agents tables
        $sql_check = "SELECT username, email FROM Managers WHERE username = ? OR email = ?
                      UNION
                      SELECT username, email FROM Customers WHERE username = ? OR email = ?
                      UNION
                      SELECT username, email FROM Agents WHERE username = ? OR email = ?";

        $stmt_check = $conn->prepare($sql_check);

        if ($stmt_check === false) {
            die('MySQL prepare failed: ' . $conn->error);  // Output MySQL error if prepare fails
        }

        $stmt_check->bind_param("ssssss", $username, $email, $username, $email, $username, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $errors[] = 'Username or email is already in use.';
        }

        // If no errors, proceed with inserting the manager into the database
        if (empty($errors)) {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert the manager into the Managers table
            $sql_insert = "INSERT INTO Managers (name, username, email, password) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);

            if ($stmt_insert === false) {
                die('MySQL prepare failed: ' . $conn->error);  // Output MySQL error if prepare fails
            }

            $stmt_insert->bind_param("ssss", $name, $username, $email, $hashed_password);

            if ($stmt_insert->execute()) {
                // Success: Set a success message in session
                $_SESSION['success'] = 'Manager registered successfully!';
                header('Location: login.php');
                exit();
            } else {
                // Error: Database insertion failed
                $errors[] = 'Error occurred while registering the manager. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Manager</title>
    <link rel="stylesheet" href="css/style.css"> <!-- Optional: include custom CSS -->
</head>
<body>
    <div class="container">
        <h2>Register Manager</h2>

        <?php
        // Display error messages
        if (!empty($errors)) {
            echo '<div class="alert alert-danger">';
            foreach ($errors as $error) {
                echo "<p>$error</p>";
            }
            echo '</div>';
        }
        ?>

        <form action="reg.php" method="POST">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <button type="submit">Register</button>
            </div>
        </form>
    </div>
</body>
</html>
