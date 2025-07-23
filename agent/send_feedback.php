<?php
session_start();
include '../includes/config.php';

// Check if user is logged in as agent
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../login.php");
    exit();
}

$agent_id = $_SESSION['agent_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = $_POST['receiver_id'];
    $receiver_type = $_POST['receiver_type'];
    $feedback_text = trim($_POST['feedback_text']);

    if (empty($feedback_text)) {
        $_SESSION['message'] = "Message cannot be empty";
        $_SESSION['message_type'] = "danger";
        header("Location: feedback.php");
        exit();
    }

    try {
        $conn->begin_transaction();

        if ($receiver_type === 'manager') {
            $query = "INSERT INTO feedback (agent_id, manager_id, feedback_text) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iis", $agent_id, $receiver_id, $feedback_text);
        } else {
            $query = "INSERT INTO feedback (agent_id, customer_id, feedback_text) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iis", $agent_id, $receiver_id, $feedback_text);
        }

        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['message'] = "Message sent successfully";
            $_SESSION['message_type'] = "success";
        } else {
            throw new Exception("Failed to send message");
        }

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }

    // Redirect back to feedback page with the same conversation
    header("Location: feedback.php?type=" . $receiver_type . "&id=" . $receiver_id);
    exit();
} else {
    header("Location: feedback.php");
    exit();
}
?> 