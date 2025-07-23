<?php
/**
 * Health Insurance System - Common Functions
 * 
 * This file contains common utility functions used throughout the application.
 */

/**
 * Sanitize user input to prevent XSS attacks
 * 
 * @param string $input The input string to sanitize
 * @return string The sanitized string
 */
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency amount with the peso symbol
 * 
 * @param float $amount The amount to format
 * @return string The formatted amount with peso symbol
 */
function format_currency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Format date to a readable format
 * 
 * @param string $date The date string to format
 * @param string $format The desired format (default: M d, Y)
 * @return string The formatted date
 */
function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Generate a random string
 * 
 * @param int $length The length of the random string
 * @return string The random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $random_string;
}

/**
 * Check if a user has permission to access a resource
 * 
 * @param string $required_role The required role to access the resource
 * @return bool True if the user has permission, false otherwise
 */
function check_permission($required_role) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    if ($_SESSION['role'] === 'admin') {
        return true; // Admin has access to everything
    }
    
    return $_SESSION['role'] === $required_role;
}

/**
 * Redirect to a URL
 * 
 * @param string $url The URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Display a flash message
 * 
 * @param string $message The message to display
 * @param string $type The type of message (success, error, warning, info)
 * @return void
 */
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get and clear the flash message
 * 
 * @return array|null The flash message or null if none exists
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Calculate commission amount based on payment and rate
 * 
 * @param float $payment_amount The payment amount
 * @param float $commission_rate The commission rate (percentage)
 * @return float The calculated commission amount
 */
function calculate_commission($payment_amount, $commission_rate) {
    return $payment_amount * ($commission_rate / 100);
}

/**
 * Validate date format
 * 
 * @param string $date The date string to validate
 * @param string $format The expected format (default: Y-m-d)
 * @return bool True if the date is valid, false otherwise
 */
function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Get the current date and time in the database format
 * 
 * @return string The current date and time in Y-m-d H:i:s format
 */
function get_current_datetime() {
    return date('Y-m-d H:i:s');
}

/**
 * Check if a string is a valid email address
 * 
 * @param string $email The email address to validate
 * @return bool True if the email is valid, false otherwise
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if a string is a valid phone number
 * 
 * @param string $phone The phone number to validate
 * @return bool True if the phone number is valid, false otherwise
 */
function is_valid_phone($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if the phone number has at least 10 digits
    return strlen($phone) >= 10;
}

/**
 * Get the status badge HTML based on the status
 * 
 * @param string $status The status value
 * @return string The HTML for the status badge
 */
function get_status_badge($status) {
    $badge_class = '';
    
    switch (strtolower($status)) {
        case 'active':
            $badge_class = 'badge-success';
            break;
        case 'inactive':
            $badge_class = 'badge-danger';
            break;
        case 'pending':
            $badge_class = 'badge-warning';
            break;
        case 'completed':
            $badge_class = 'badge-info';
            break;
        default:
            $badge_class = 'badge-secondary';
    }
    
    return '<span class="badge ' . $badge_class . '">' . ucfirst($status) . '</span>';
}

/**
 * Get the user's full name from the session
 * 
 * @return string The user's full name or empty string if not set
 */
function get_user_full_name() {
    if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
        return $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    }
    return '';
}

/**
 * Check if the current user is logged in
 * 
 * @return bool True if the user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Get the current user's role
 * 
 * @return string The user's role or empty string if not set
 */
function get_user_role() {
    return $_SESSION['role'] ?? '';
}

/**
 * Check if the current user is an admin
 * 
 * @return bool True if the user is an admin, false otherwise
 */
function is_admin() {
    return get_user_role() === 'admin';
}

/**
 * Check if the current user is an agent
 * 
 * @return bool True if the user is an agent, false otherwise
 */
function is_agent() {
    return get_user_role() === 'agent';
}

/**
 * Check if the current user is a customer
 * 
 * @return bool True if the user is a customer, false otherwise
 */
function is_customer() {
    return get_user_role() === 'customer';
}

/**
 * Log an activity
 * 
 * @param string $activity The activity description
 * @param string $user_id The user ID (default: current user)
 * @param string $user_role The user role (default: current user's role)
 * @return bool True if the activity was logged successfully, false otherwise
 */
function log_activity($activity, $user_id = null, $user_role = null) {
    global $conn;
    
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? 0;
    }
    
    if ($user_role === null) {
        $user_role = $_SESSION['role'] ?? '';
    }
    
    $activity = sanitize_input($activity);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $timestamp = get_current_datetime();
    
    $query = "INSERT INTO activity_logs (user_id, user_role, activity, ip_address, user_agent, timestamp) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssss", $user_id, $user_role, $activity, $ip_address, $user_agent, $timestamp);
    
    return $stmt->execute();
} 