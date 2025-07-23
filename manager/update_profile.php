<?php
session_start();
include '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['manager_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Get manager ID from session
$manager_id = $_SESSION['manager_id'];

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }

    // Check if username or email already exists (excluding current user)
    $check_sql = "SELECT manager_id FROM Managers WHERE (username = ? OR email = ?) AND manager_id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ssi", $username, $email, $manager_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $errors[] = 'Username or email already exists';
    }

    // If there are no errors, proceed with update
    if (empty($errors)) {
        // Prepare the SQL statement based on whether password is being updated
        if (!empty($password)) {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE Managers SET name = ?, username = ?, email = ?, password = ? WHERE manager_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $name, $username, $email, $hashed_password, $manager_id);
        } else {
            $sql = "UPDATE Managers SET name = ?, username = ?, email = ? WHERE manager_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $name, $username, $email, $manager_id);
        }

        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['manager_name'] = $name;
            $_SESSION['manager_username'] = $username;
            $_SESSION['manager_email'] = $email;

            $response = [
                'success' => true,
                'message' => 'Profile updated successfully',
                'name' => $name
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Error updating profile: ' . $conn->error
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => implode('<br>', $errors)
        ];
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Invalid request method'
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
