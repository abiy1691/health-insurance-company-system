<?php
// For testing purposes, we'll set a default agent ID if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent') {
    // Set default agent ID for testing
    $_SESSION['user_id'] = 1; // Assuming agent ID 1 exists in your database
    $_SESSION['role'] = 'agent';
    $_SESSION['first_name'] = 'Test';
    $_SESSION['last_name'] = 'Agent';
    
    // Uncomment the line below to enable authentication when ready
    // header("Location: ../login.php");
    // exit();
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="col-md-3">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-user-tie"></i> Agent Menu
        </div>
        <div class="list-group list-group-flush">
            <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="customers.php" class="list-group-item list-group-item-action <?php echo $current_page === 'customers.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> My Customers
            </a>
            <a href="policies.php" class="list-group-item list-group-item-action <?php echo $current_page === 'policies.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-contract"></i> Policies
            </a>
            <a href="commissions.php" class="list-group-item list-group-item-action <?php echo $current_page === 'commissions.php' ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i> Commissions
            </a>
            <a href="claims.php" class="list-group-item list-group-item-action <?php echo $current_page === 'claims.php' ? 'active' : ''; ?>">
                <i class="fas fa-hand-holding-medical"></i> Claims
            </a>
            <a href="reports.php" class="list-group-item list-group-item-action <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="profile.php" class="list-group-item list-group-item-action <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-cog"></i> Profile
            </a>
        </div>
    </div>
    
    <!-- Quick Stats Card -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-chart-line"></i> Quick Stats
        </div>
        <div class="card-body">
            <?php
            // Initialize variables with default values
            $total_customers = 0;
            $total_policies = 0;
            $monthly_commission = 0;
            
            // Get total number of customers
            $customers_query = "SELECT COUNT(*) as total FROM customers WHERE agent_id = ?";
            $stmt = $conn->prepare($customers_query);
            if ($stmt === false) {
                // Error preparing query
                $total_customers = 0;
            } else {
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $total_customers = $result->fetch_assoc()['total'];
            }
            
            // Get total active policies
            $policies_query = "SELECT COUNT(*) as total FROM policies p 
                             JOIN customers c ON p.customer_id = c.customer_id 
                             WHERE c.agent_id = ? AND p.status = 'Active'";
            $stmt = $conn->prepare($policies_query);
            if ($stmt === false) {
                // Error preparing query
                $total_policies = 0;
            } else {
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $total_policies = $result->fetch_assoc()['total'];
            }
            
            // Get total commission this month
            $commission_query = "SELECT SUM(com.commission_amount) as total 
                               FROM commissions com
                               WHERE com.agent_id = ? 
                               AND MONTH(com.commission_date) = MONTH(CURRENT_DATE()) 
                               AND YEAR(com.commission_date) = YEAR(CURRENT_DATE())";
            $stmt = $conn->prepare($commission_query);
            if ($stmt === false) {
                // Error preparing query
                $monthly_commission = 0;
            } else {
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $monthly_commission = $result->fetch_assoc()['total'] ?? 0;
            }
            ?>
            
            <div class="stat-item mb-2">
                <small class="text-muted">Total Customers</small>
                <h4 class="mb-0"><?php echo number_format($total_customers); ?></h4>
            </div>
            
            <div class="stat-item mb-2">
                <small class="text-muted">Active Policies</small>
                <h4 class="mb-0"><?php echo number_format($total_policies); ?></h4>
            </div>
            
            <div class="stat-item">
                <small class="text-muted">This Month's Commission</small>
                <h4 class="mb-0">₱<?php echo number_format($monthly_commission, 2); ?></h4>
            </div>
        </div>
    </div>
</div> 