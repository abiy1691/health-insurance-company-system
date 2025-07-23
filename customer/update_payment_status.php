<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Get payment ID and new status from POST data
$payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
$new_status = isset($_POST['status']) ? $_POST['status'] : '';

// Validate inputs
if ($payment_id <= 0 || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

// Update payment status
$stmt = $conn->prepare("UPDATE payments SET status = ? WHERE payment_number = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error preparing payment update: ' . $conn->error]);
    exit();
}

$stmt->bind_param("si", $new_status, $payment_id);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error updating payment status: ' . $stmt->error]);
    exit();
}

// If payment status is changed to "Active", calculate and store commission
if ($new_status == 'Active') {
    // Get payment details
    $payment_query = "SELECT p.*, c.agent_id, a.commission_rate 
                     FROM payments p 
                     JOIN customers c ON p.customer_id = c.customer_id 
                     JOIN agents a ON c.agent_id = a.agent_id 
                     WHERE p.payment_number = ?";
    
    $stmt = $conn->prepare($payment_query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error preparing payment details query: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment_data = $result->fetch_assoc();

    // Log payment data for debugging
    error_log("Payment data for commission calculation: " . json_encode($payment_data));

    if ($payment_data && $payment_data['agent_id'] && $payment_data['commission_rate']) {
        // Calculate commission amount
        $commission_rate = floatval($payment_data['commission_rate']);
        $payment_amount = floatval($payment_data['amount']);
        $commission_amount = ($payment_amount * $commission_rate) / 100;

        // Check if commission already exists for this payment
        $check_query = "SELECT * FROM commissions WHERE payment_number = ?";
        $check_stmt = $conn->prepare($check_query);
        if (!$check_stmt) {
            echo json_encode(['success' => false, 'message' => 'Error preparing commission check: ' . $conn->error]);
            exit();
        }

        $check_stmt->bind_param("i", $payment_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        // Only insert commission if it doesn't already exist
        if ($check_result->num_rows == 0) {
            // Insert into commissions table
            $insert_query = "INSERT INTO commissions (
                customer_id,
                policy_id,
                payment_number,
                commission_amount,
                agent_id
            ) VALUES (?, ?, ?, ?, ?)";
            
            $insert_stmt = $conn->prepare($insert_query);
            if (!$insert_stmt) {
                echo json_encode(['success' => false, 'message' => 'Error preparing commission insert: ' . $conn->error]);
                exit();
            }

            $insert_stmt->bind_param(
                "iiidi",
                $payment_data['customer_id'],
                $payment_data['policy_id'],
                $payment_id,
                $commission_amount,
                $payment_data['agent_id']
            );

            if (!$insert_stmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Error inserting commission: ' . $insert_stmt->error]);
                exit();
            }
            
            // Log successful commission insertion
            error_log("Commission successfully inserted for payment_id: " . $payment_id . 
                     ", amount: " . $commission_amount . 
                     ", agent_id: " . $payment_data['agent_id']);
        } else {
            // Log that commission already exists
            error_log("Commission already exists for payment_id: " . $payment_id);
        }
    } else {
        // Log the reason why commission wasn't calculated
        $reason = "Missing data: ";
        if (!$payment_data) $reason .= "Payment data not found. ";
        if ($payment_data && !$payment_data['agent_id']) $reason .= "Agent ID not found. ";
        if ($payment_data && !$payment_data['commission_rate']) $reason .= "Commission rate not found. ";
        
        error_log("Commission not calculated: " . $reason);
    }
}

// Return success response
echo json_encode(['success' => true, 'message' => 'Payment status updated successfully']);
?> 