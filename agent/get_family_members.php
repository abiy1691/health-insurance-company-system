<?php
session_start();
if (!isset($_SESSION['agent_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

include '../includes/config.php';

// Get the logged-in agent's ID
$agent_id = $_SESSION['agent_id'];

// Check if customer_id is provided
if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Customer ID is required']);
    exit();
}

$customer_id = $_GET['customer_id'];

// Verify that this customer is assigned to the logged-in agent
$verify_query = "SELECT 1 FROM customer_agent WHERE customer_id = ? AND agent_id = ?";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param("ii", $customer_id, $agent_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'You do not have permission to view this customer\'s family members']);
    exit();
}

// Get family members for the customer
$query = "SELECT * FROM family_members WHERE customer_id = ? ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$family_members = [];

while ($row = $result->fetch_assoc()) {
    // Format date of birth for display
    if (!empty($row['date_of_birth'])) {
        $dob = new DateTime($row['date_of_birth']);
        $row['date_of_birth'] = $dob->format('M d, Y');
    } else {
        $row['date_of_birth'] = 'Not available';
    }
    
    $family_members[] = $row;
}

// Return the family members as JSON
header('Content-Type: application/json');
echo json_encode($family_members); 