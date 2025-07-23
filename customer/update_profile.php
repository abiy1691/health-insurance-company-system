<?php
session_start();
include '../includes/config.php';

// Check if user is logged in as customer
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Get customer ID from session
$customer_id = $_SESSION['customer_id'];

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $age = intval($_POST['age'] ?? 0);

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

    if (!empty($phone) && !preg_match('/^[79]\d{8}$/', $phone)) {
        $errors[] = 'Phone must start with 7 or 9 and be 9 digits';
    }

    if ($age < 0 || $age > 120) {
        $errors[] = 'Age must be between 0 and 120';
    }

    // Check if username or email already exists (excluding current customer)
    $check_sql = "SELECT customer_id FROM Customers WHERE (username = ? OR email = ?) AND customer_id != ?
                 UNION
                 SELECT agent_id FROM Agents WHERE username = ? OR email = ?
                 UNION
                 SELECT manager_id FROM Managers WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ssissss", $username, $email, $customer_id, $username, $email, $username, $email);
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
            $sql = "UPDATE Customers SET name = ?, username = ?, email = ?, password = ?, phone = ?, address = ?, age = ? WHERE customer_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssii", $name, $username, $email, $hashed_password, $phone, $address, $age, $customer_id);
        } else {
            $sql = "UPDATE Customers SET name = ?, username = ?, email = ?, phone = ?, address = ?, age = ? WHERE customer_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssii", $name, $username, $email, $phone, $address, $age, $customer_id);
        }

        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_username'] = $username;
            $_SESSION['customer_email'] = $email;
            $_SESSION['customer_phone'] = $phone;
            $_SESSION['customer_address'] = $address;
            $_SESSION['customer_age'] = $age;

            $response = [
                'success' => true,
                'message' => 'Profile updated successfully',
                'name' => $name,
                'username' => $username,
                'email' => $email
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