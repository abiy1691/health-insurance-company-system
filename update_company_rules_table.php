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
    // Table exists, check if it needs to be updated
    $check_columns_query = "SHOW COLUMNS FROM company_rules";
    $columns_result = $conn->query($check_columns_query);
    $columns = [];
    
    while ($column = $columns_result->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    
    // Check if we need to update the table structure
    if (!in_array('rule_content', $columns)) {
        // Create a temporary table with the new structure
        $create_temp_table_query = "CREATE TABLE company_rules_temp (
            rule_id INT(11) NOT NULL AUTO_INCREMENT,
            rule_content TEXT NOT NULL,
            created_by INT(11) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (rule_id),
            KEY created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if ($conn->query($create_temp_table_query) === TRUE) {
            // Copy data from old table to new table
            $copy_data_query = "INSERT INTO company_rules_temp (rule_id, rule_content, created_by, created_at, updated_at)
                               SELECT rule_id, 
                                      CASE 
                                          WHEN 'description' IN (" . implode(',', array_map(function($col) { return "'$col'"; }, $columns)) . ") 
                                          THEN description 
                                          ELSE title 
                                      END AS rule_content,
                                      created_by, created_at, updated_at
                               FROM company_rules";
            
            if ($conn->query($copy_data_query) === TRUE) {
                // Drop the old table
                $conn->query("DROP TABLE company_rules");
                
                // Rename the temporary table to the original name
                $conn->query("RENAME TABLE company_rules_temp TO company_rules");
                
                echo "Company rules table updated successfully<br>";
            } else {
                echo "Error copying data: " . $conn->error . "<br>";
                $conn->query("DROP TABLE company_rules_temp");
            }
        } else {
            echo "Error creating temporary table: " . $conn->error . "<br>";
        }
    } else {
        echo "Company rules table already has the correct structure<br>";
    }
    
    // Check if the table has any records
    $check_records_query = "SELECT COUNT(*) as count FROM company_rules";
    $result = $conn->query($check_records_query);
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Table is empty, add sample rules
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
        echo "Company rules table already contains " . $row['count'] . " records<br>";
    }
}

echo "<a href='manager/company_rules.php'>Go to Company Rules Page</a>";
?> 