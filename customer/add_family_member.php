<?php
session_start();
include '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $gender = trim($_POST['gender']);
    $age = intval($_POST['age']);
    $relation = trim($_POST['relation']);
    $customer_id = $_SESSION['customer_id'];

    // Validate input
    if (empty($name) || empty($gender) || empty($relation) || $age <= 0) {
        $_SESSION['message'] = 'Please fill in all fields with valid data.';
        $_SESSION['message_type'] = 'error';
    } else {
        // Prepare and execute insert query
        $query = "INSERT INTO Family_Member (customer_id, name, gender, age, relation) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            $_SESSION['message'] = 'Error preparing statement: ' . $conn->error;
            $_SESSION['message_type'] = 'error';
        } else {
            $stmt->bind_param("issis", $customer_id, $name, $gender, $age, $relation);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Family member added successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error adding family member: ' . $stmt->error;
                $_SESSION['message_type'] = 'error';
            }
            
            $stmt->close();
        }
    }
} else {
    $_SESSION['message'] = 'Invalid request method.';
    $_SESSION['message_type'] = 'error';
}

// Redirect back to family members page
header("Location: family_members.php");
exit();
?>
