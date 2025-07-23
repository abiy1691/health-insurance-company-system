<?php
session_start();
include '../includes/config.php';

// Check if user is logged in as agent
if (!isset($_SESSION['agent_id'])) {
    echo "Please log in as an agent first.";
    exit();
}

$agent_id = $_SESSION['agent_id'];

// Get a sample customer ID for testing
$customer_query = "SELECT customer_id FROM customers WHERE agent_id = ? LIMIT 1";
$stmt = $conn->prepare($customer_query);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No customers found for this agent.";
    exit();
}

$customer = $result->fetch_assoc();
$customer_id = $customer['customer_id'];

echo "<h2>Testing Family Members Functionality</h2>";
echo "<p>Agent ID: " . $agent_id . "</p>";
echo "<p>Customer ID: " . $customer_id . "</p>";

// Check if customer_agent relationship exists
$verify_query = "SELECT 1 FROM customer_agent WHERE customer_id = ? AND agent_id = ?";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param("ii", $customer_id, $agent_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo "<p style='color:red;'>Warning: No customer_agent relationship found. This might be the issue.</p>";
    
    // Try to insert the relationship
    $insert_query = "INSERT INTO customer_agent (customer_id, agent_id) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ii", $customer_id, $agent_id);
    
    if ($insert_stmt->execute()) {
        echo "<p style='color:green;'>Successfully created customer_agent relationship.</p>";
    } else {
        echo "<p style='color:red;'>Failed to create customer_agent relationship: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green;'>Customer-agent relationship exists.</p>";
}

// Get family members for the customer
$query = "SELECT * FROM family_members WHERE customer_id = ? ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Family Members:</h3>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Name</th><th>Relationship</th><th>Date of Birth</th><th>Gender</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['relationship']) . "</td>";
        echo "<td>" . htmlspecialchars($row['date_of_birth']) . "</td>";
        echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No family members found for this customer.</p>";
    
    // Check if the family_members table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'family_members'");
    if ($table_check->num_rows === 0) {
        echo "<p style='color:red;'>The family_members table does not exist!</p>";
    } else {
        echo "<p>The family_members table exists but has no records for this customer.</p>";
    }
}

// Test the AJAX endpoint
echo "<h3>Testing AJAX Endpoint:</h3>";
echo "<p>Click the button below to test the get_family_members.php endpoint:</p>";
echo "<button id='testAjax'>Test AJAX</button>";
echo "<div id='ajaxResult'></div>";

echo "<script src='../style/js/jquery.min.js'></script>";
echo "<script>
    $(document).ready(function() {
        $('#testAjax').on('click', function() {
            $('#ajaxResult').html('Loading...');
            $.ajax({
                url: 'get_family_members.php',
                type: 'GET',
                data: { customer_id: $customer_id },
                dataType: 'json',
                success: function(data) {
                    $('#ajaxResult').html('<pre>' + JSON.stringify(data, null, 2) + '</pre>');
                },
                error: function(xhr, status, error) {
                    $('#ajaxResult').html('Error: ' + error + '<br>Status: ' + status + '<br>Response: ' + xhr.responseText);
                }
            });
        });
    });
</script>";
?> 