<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $data = json_decode(file_get_contents('php://input'), true);
    
    $age = intval($data['age']);
    $coverage = floatval($data['coverage']);
    $term = intval($data['term']);
    $payment_interval = $data['payment_interval'];
    $health_status = $data['health_status'];
    $occupation = $data['occupation'];
    
    // Validate inputs
    if ($age < 18 || $age > 65) {
        echo json_encode(['error' => 'Age must be between 18 and 65']);
        exit;
    }
    
    // Get base policy rate from database
    $stmt = $conn->prepare("SELECT profit_rate FROM policies WHERE policy_term = ? ORDER BY price ASC LIMIT 1");
    $stmt->bind_param("i", $term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'No policy found for the selected term']);
        exit;
    }
    
    $policy = $result->fetch_assoc();
    $base_rate = $policy['profit_rate'] / 100; // Convert percentage to decimal
    
    // Calculate base premium
    $base_premium = $coverage * $base_rate;
    
    // Apply age factor
    $age_factor = 1.0;
    if ($age > 50) {
        $age_factor = 1.5;
    } elseif ($age > 40) {
        $age_factor = 1.3;
    } elseif ($age > 30) {
        $age_factor = 1.2;
    }
    
    // Apply health status factor
    $health_factor = 1.0;
    switch ($health_status) {
        case 'excellent':
            $health_factor = 0.9;
            break;
        case 'good':
            $health_factor = 1.0;
            break;
        case 'fair':
            $health_factor = 1.2;
            break;
    }
    
    // Apply occupation factor
    $occupation_factor = 1.0;
    switch ($occupation) {
        case 'professional':
            $occupation_factor = 0.9;
            break;
        case 'business':
            $occupation_factor = 1.0;
            break;
        case 'employee':
            $occupation_factor = 1.0;
            break;
        case 'self-employed':
            $occupation_factor = 1.1;
            break;
    }
    
    // Calculate final premium
    $yearly_premium = $base_premium * $age_factor * $health_factor * $occupation_factor;
    
    // Calculate different payment intervals
    $monthly_premium = $yearly_premium / 12;
    $quarterly_premium = $yearly_premium / 4;
    
    // Round all premiums to nearest whole number
    $yearly_premium = round($yearly_premium);
    $quarterly_premium = round($quarterly_premium);
    $monthly_premium = round($monthly_premium);
    
    // Store quote in session for later use
    $_SESSION['quote'] = [
        'age' => $age,
        'coverage' => $coverage,
        'term' => $term,
        'payment_interval' => $payment_interval,
        'health_status' => $health_status,
        'occupation' => $occupation,
        'yearly_premium' => $yearly_premium,
        'quarterly_premium' => $quarterly_premium,
        'monthly_premium' => $monthly_premium
    ];
    
    // Return the calculated premiums
    echo json_encode([
        'success' => true,
        'premiums' => [
            'yearly' => $yearly_premium,
            'quarterly' => $quarterly_premium,
            'monthly' => $monthly_premium
        ],
        'details' => [
            'base_rate' => $base_rate * 100,
            'age_factor' => $age_factor,
            'health_factor' => $health_factor,
            'occupation_factor' => $occupation_factor
        ]
    ]);
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?> 