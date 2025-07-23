<?php
include 'includes/config.php';

// Check if company_rules table exists
$check_table_query = "SHOW TABLES LIKE 'company_rules'";
$table_exists = $conn->query($check_table_query);

if ($table_exists->num_rows == 0) {
    // Table doesn't exist, create it
    $create_table_query = "CREATE TABLE company_rules (
        rule_id INT(11) NOT NULL AUTO_INCREMENT,
        rule_content TEXT NOT NULL,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (rule_id),
        KEY created_by (created_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($create_table_query) === TRUE) {
        echo "Company rules table created successfully<br>";
        
        // Add sample rules
        $sample_rules = [
            [
                'rule_content' => 'All customer information must be kept confidential and secure. No employee should share customer data with unauthorized parties.',
                'created_by' => 1 // Assuming manager_id 1 exists
            ],
            [
                'rule_content' => 'All insurance claims must be processed within 15 business days from the date of submission. Expedited claims should be marked with high priority.',
                'created_by' => 1
            ],
            [
                'rule_content' => 'Agents receive a 10% commission on all new policy sales. Renewal commissions are 5% of the premium amount.',
                'created_by' => 1
            ]
        ];
        
        $insert_query = "INSERT INTO company_rules (rule_content, created_by) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        
        foreach ($sample_rules as $rule) {
            $stmt->bind_param("si", $rule['rule_content'], $rule['created_by']);
            $stmt->execute();
        }
        
        echo "Sample rules added successfully<br>";
    } else {
        echo "Error creating company rules table: " . $conn->error . "<br>";
    }
} else {
    echo "Company rules table already exists<br>";
    
    // Check if the table has the correct structure
    $check_columns_query = "SHOW COLUMNS FROM company_rules";
    $columns_result = $conn->query($check_columns_query);
    $columns = [];
    
    while ($column = $columns_result->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    
    if (!in_array('rule_content', $columns)) {
        echo "Table structure needs to be updated. Please run update_company_rules_table.php to update the structure.<br>";
    } else {
        echo "Table structure is correct.<br>";
    }
    
    // Check if the table has any records
    $check_records_query = "SELECT COUNT(*) as count FROM company_rules";
    $result = $conn->query($check_records_query);
    $row = $result->fetch_assoc();
    
    echo "Company rules table contains " . $row['count'] . " records<br>";
}

echo "<a href='manager/company_rules.php'>Go to Company Rules Page</a>";
?> 