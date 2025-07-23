<?php
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}
?>

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-heartbeat"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Health Insurance</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Insurance
    </div>

    <!-- Nav Item - Policy Plans -->
    <li class="nav-item">
        <a class="nav-link" href="policy_plan.php">
            <i class="fas fa-fw fa-file-contract"></i>
            <span>Policy Plans</span>
        </a>
    </li>

    <!-- Nav Item - Policy Owned -->
    <li class="nav-item">
        <a class="nav-link" href="policy_owned.php">
            <i class="fas fa-fw fa-file-invoice"></i>
            <span>My Policies</span>
        </a>
    </li>

    <!-- Nav Item - Payments -->
    <li class="nav-item">
        <a class="nav-link" href="payments.php">
            <i class="fas fa-fw fa-credit-card"></i>
            <span>Payments</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Account
    </div>

    <!-- Nav Item - Profile -->
    <li class="nav-item">
        <a class="nav-link" href="profile.php">
            <i class="fas fa-fw fa-user"></i>
            <span>Profile</span>
        </a>
    </li>

    <!-- Nav Item - Family Members -->
    <li class="nav-item">
        <a class="nav-link" href="family_members.php">
            <i class="fas fa-fw fa-users"></i>
            <span>Family Members</span>
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