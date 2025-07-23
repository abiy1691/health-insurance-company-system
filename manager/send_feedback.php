<?php
session_start();
include '../includes/config.php';

// Check if user is logged in as manager
if (!isset($_SESSION['manager_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manager_id = $_SESSION['manager_id'];
    $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
    $feedback_text = isset($_POST['feedback_text']) ? trim($_POST['feedback_text']) : '';

    if ($agent_id && $feedback_text) {
        $query = "INSERT INTO Feedback (manager_id, agent_id, feedback_text, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            $_SESSION['message'] = "Error preparing statement: " . $conn->error;
            $_SESSION['message_type'] = 'danger';
        } else {
            $stmt->bind_param("iis", $manager_id, $agent_id, $feedback_text);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Message sent successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Error sending message: " . $stmt->error;
                $_SESSION['message_type'] = 'danger';
            }
            
            $stmt->close();
        }
    } else {
        $_SESSION['message'] = "Please fill in all required fields.";
        $_SESSION['message_type'] = 'danger';
    }
}

// Redirect back to feedback page
header("Location: feedback.php" . ($agent_id ? "?agent_id=" . $agent_id : ""));
exit(); 