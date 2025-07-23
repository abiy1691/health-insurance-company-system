<?php
include 'includes/config.php';

// Create company_rules table if it doesn't exist
$query = "CREATE TABLE IF NOT EXISTS company_rules (
    rule_id INT(11) NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    created_by INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (rule_id),
    KEY created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($query) === TRUE) {
    echo "Company rules table created successfully<br>";
    
    // Check if the table is empty and add some sample rules if needed
    $check_query = "SELECT COUNT(*) as count FROM company_rules";
    $result = $conn->query($check_query);
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Add sample rules
        $sample_rules = [
            [
                'title' => 'Customer Privacy Policy',
                'description' => 'All customer information must be kept confidential and secure. No employee should share customer data with unauthorized parties.',
                'category' => 'Compliance',
                'created_by' => 1 // Assuming manager_id 1 exists
            ],
            [
                'title' => 'Claims Processing Timeframe',
                'description' => 'All insurance claims must be processed within 15 business days from the date of submission. Expedited claims should be marked with high priority.',
                'category' => 'Claims',
                'created_by' => 1
            ],
            [
                'title' => 'Agent Commission Structure',
                'description' => 'Agents receive a 10% commission on all new policy sales. Renewal commissions are 5% of the premium amount.',
                'category' => 'Employee',
                'created_by' => 1
            ]
        ];
        
        $insert_query = "INSERT INTO company_rules (title, description, category, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        
        foreach ($sample_rules as $rule) {
            $stmt->bind_param("sssi", $rule['title'], $rule['description'], $rule['category'], $rule['created_by']);
            $stmt->execute();
        }
        
        echo "Sample rules added successfully<br>";
    } else {
        echo "Company rules table already contains data<br>";
    }
} else {
    echo "Error creating company rules table: " . $conn->error . "<br>";
}

echo "<a href='manager/company_rules.php'>Go to Company Rules Page</a>";
?> 