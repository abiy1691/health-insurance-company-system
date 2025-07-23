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
    $feedback_text = trim($_POST['feedback_text']);
    $customer_id = $_SESSION['customer_id'];
    
    // Get customer's agent ID
    $query = "SELECT agent_id FROM Customers WHERE customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    
    if ($customer && $customer['agent_id']) {
        // Prepare and execute insert query
        $query = "INSERT INTO Feedback (feedback_text, customer_id, agent_id, receiver) 
                  VALUES (?, ?, ?, 'agent')";
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            $_SESSION['message'] = 'Error preparing statement: ' . $conn->error;
            $_SESSION['message_type'] = 'error';
        } else {
            $stmt->bind_param("sii", $feedback_text, $customer_id, $customer['agent_id']);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Feedback sent successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error sending feedback: ' . $stmt->error;
                $_SESSION['message_type'] = 'error';
            }
            
            $stmt->close();
        }
    } else {
        $_SESSION['message'] = 'No agent assigned to your account.';
        $_SESSION['message_type'] = 'error';
    }
} else {
    $_SESSION['message'] = 'Invalid request method.';
    $_SESSION['message_type'] = 'error';
}

// Redirect back to feedback page
header("Location: feedback.php");
exit();
?> 