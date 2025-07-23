<?php
session_start();
include '../includes/config.php';

// Check if user is logged in as agent
if (!isset($_SESSION['agent_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Get agent ID from session
$agent_id = $_SESSION['agent_id'];

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
    
    // Check if username or email already exists (excluding current agent)
    $check_sql = "SELECT agent_id FROM agents WHERE (username = ? OR email = ?) AND agent_id != ?
                 UNION
                 SELECT customer_id FROM Customers WHERE username = ? OR email = ?
                 UNION
                 SELECT manager_id FROM Managers WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ssissss", $username, $email, $agent_id, $username, $email, $username, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $errors[] = 'Username or email already exists';
    }

    // If there are no errors, proceed with update
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->begin_transaction();

            // Prepare the SQL statement based on whether password is being updated
            if (!empty($password)) {
                // Hash the new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE agents SET name = ?, username = ?, email = ?, password = ? WHERE agent_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $name, $username, $email, $hashed_password, $agent_id);
            } else {
                $sql = "UPDATE agents SET name = ?, username = ?, email = ? WHERE agent_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $name, $username, $email, $agent_id);
            }

            if ($stmt->execute()) {
                // Update session variables
                $_SESSION['agent_name'] = $name;
                $_SESSION['agent_username'] = $username;
                $_SESSION['agent_email'] = $email;

                // Commit transaction
                $conn->commit();

                $response = [
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'name' => $name,
                    'username' => $username,
                    'email' => $email
                ];
            } else {
                throw new Exception('Error updating profile: ' . $conn->error);
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $response = [
                'success' => false,
                'message' => $e->getMessage()
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