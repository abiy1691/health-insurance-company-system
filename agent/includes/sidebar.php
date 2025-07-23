<?php
// Check if user is logged in as agent
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-user-tie"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Agent Panel</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

  
    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Customer Management
    </div>

    <!-- Nav Item - Customers -->
    <li class="nav-item">
        <a class="nav-link" href="customers.php">
            <i class="fas fa-fw fa-users"></i>
            <span>Customers</span>
        </a>
    </li>

    <!-- Nav Item - Customer Policy Relation -->
    <li class="nav-item">
        <a class="nav-link" href="customer_policies.php">
            <i class="fas fa-fw fa-link"></i>
            <span>Customer Policy Relation</span>
        </a>
    </li>

    <!-- Nav Item - Payment History -->
    <li class="nav-item">
        <a class="nav-link" href="payment_history.php">
            <i class="fas fa-fw fa-history"></i>
            <span>Payment History</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Policy Management
    </div>

    <!-- Nav Item - Policy Plans -->
    <li class="nav-item">
        <a class="nav-link" href="policy_plans.php">
            <i class="fas fa-fw fa-file-contract"></i>
            <span>Policy Plans</span>
        </a>
    </li>

    <!-- Nav Item - Commissions -->
    <li class="nav-item">
        <a class="nav-link" href="commissions.php">
            <i class="fas fa-fw fa-money-bill-wave"></i>
            <span>Commissions</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Communication
    </div>

    <!-- Nav Item - Feedback -->
    <li class="nav-item">
        <a class="nav-link" href="feedback.php">
            <i class="fas fa-fw fa-comments"></i>
            <span>Feedback</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->
