<?php
session_start();
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Get policy and customer details
$policy_id = $_GET['policy_id'];
$customer_id = $_GET['customer_id'];

// Get policy details
$query = "SELECT cp.*, p.policy_name, p.policy_type, p.price, p.payment_interval, p.profit_rate, p.policy_term,
          c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
          c.address as customer_address, c.gender as customer_gender, c.age as customer_age
          FROM customer_policy cp 
          JOIN policies p ON cp.policy_id = p.policy_id 
          JOIN customers c ON cp.customer_id = c.customer_id 
          WHERE cp.policy_id = ? AND cp.customer_id = ?";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("ii", $policy_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$policy = $result->fetch_assoc();

if (!$policy) {
    header("Location: customer_policies.php");
    exit();
}

// Get last payment date
$last_payment_query = "SELECT payment_date FROM payments 
                      WHERE customer_id = ? AND policy_id = ? 
                      ORDER BY payment_date DESC LIMIT 1";
$stmt = $conn->prepare($last_payment_query);
$stmt->bind_param("ii", $customer_id, $policy_id);
$stmt->execute();
$last_payment = $stmt->get_result()->fetch_assoc();

// Get family members
$family_query = "SELECT * FROM family_member WHERE customer_id = ?";
$stmt = $conn->prepare($family_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$family_members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate next payment date based on payment interval
$next_payment_date = null;
if ($last_payment) {
    $last_date = new DateTime($last_payment['payment_date']);
    $interval = $policy['payment_interval'];
    
    switch($interval) {
        case 'monthly':
            $next_payment_date = $last_date->modify('+1 month');
            break;
        case 'quarterly':
            $next_payment_date = $last_date->modify('+3 months');
            break;
        case 'half_annually':
            $next_payment_date = $last_date->modify('+6 months');
            break;
        case 'annually':
            $next_payment_date = $last_date->modify('+1 year');
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy Details | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .policy-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .policy-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        .policy-body {
            padding: 25px;
        }
        .info-label {
            font-weight: 600;
            color: #4e73df;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        .status-active {
            background-color: #28a745;
            color: white;
        }
        .back-btn {
            background: linear-gradient(45deg, #4e73df, #224abe);
            border: none;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .countdown {
            font-size: 1.2em;
            font-weight: bold;
            color: #4e73df;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .family-member-card {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .feedback-icon {
            font-size: 24px;
            color: #4e73df;
            transition: transform 0.3s ease;
        }
        .feedback-icon:hover {
            transform: scale(1.2);
        }
    </style>
</head>
<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

    <?php include 'includes/sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <?php include 'includes/header.php'; ?>

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <!-- Page Heading -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 text-gray-800">Policy Details</h1>
                    <a href="customer_policies.php" class="btn btn-primary back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Policies
                    </a>
                </div>

                <!-- Policy Details Card -->
                <div class="policy-card">
                    <div class="policy-header">
                        <h4 class="mb-0"><?php echo htmlspecialchars($policy['policy_name']); ?></h4>
                        <small>Policy ID: <?php echo $policy['policy_id']; ?></small>
                    </div>
                    <div class="policy-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-4">Policy Information</h5>
                                <p><span class="info-label">Type:</span> <?php echo ucfirst($policy['policy_type']); ?></p>
                                <p><span class="info-label">Start Date:</span> <?php echo date('F j, Y', strtotime($policy['start_date'])); ?></p>
                                <p><span class="info-label">End Date:</span> <?php echo date('F j, Y', strtotime($policy['end_date'])); ?></p>
                                <p><span class="info-label">Price:</span> ETB <?php echo number_format($policy['price'], 2); ?></p>
                                <p><span class="info-label">Payment Interval:</span> <?php echo ucfirst($policy['payment_interval']); ?></p>
                                <p><span class="info-label">Amount per Payment:</span> ETB <?php 
                                    $segments_per_year = 1;
                                    switch($policy['payment_interval']) {
                                        case 'monthly':
                                            $segments_per_year = 12;
                                            break;
                                        case 'quarterly':
                                            $segments_per_year = 4;
                                            break;
                                        case 'half_annually':
                                            $segments_per_year = 2;
                                            break;
                                        case 'annually':
                                            $segments_per_year = 1;
                                            break;
                                    }
                                    $total_payments = isset($policy['policy_term']) && $policy['policy_term'] > 0 ? 
                                        $policy['policy_term'] * $segments_per_year : 1;
                                    echo number_format($policy['price'] / $total_payments, 2);
                                ?></p>
                                <p><span class="info-label">Profit Rate:</span> <?php echo number_format($policy['profit_rate'], 2); ?>%</p>
                                <p>
                                    <span class="info-label">Status:</span>
                                    <span class="status-badge status-active">Active</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-4">Customer Information</h5>
                                <p><span class="info-label">Name:</span> <?php echo htmlspecialchars($policy['customer_name']); ?></p>
                                <p><span class="info-label">Email:</span> <?php echo htmlspecialchars($policy['customer_email']); ?></p>
                                <p><span class="info-label">Phone:</span> +251<?php echo htmlspecialchars($policy['customer_phone']); ?></p>
                                <p><span class="info-label">Address:</span> <?php echo htmlspecialchars($policy['customer_address']); ?></p>
                                <p><span class="info-label">Gender:</span> <?php echo ucfirst($policy['customer_gender']); ?></p>
                                <p><span class="info-label">Age:</span> <?php echo $policy['customer_age']; ?> years</p>
                                <p>
                                    <span class="info-label">Contact:</span>
                                    <a href="feedback.php?customer_id=<?php echo $customer_id; ?>" class="ml-2">
                                        <i class="fas fa-comments feedback-icon"></i>
                                    </a>
                                </p>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="mb-4">Payment Information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><span class="info-label">Last Payment Date:</span> 
                                            <?php echo $last_payment ? date('F j, Y', strtotime($last_payment['payment_date'])) : 'No payments yet'; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><span class="info-label">Next Payment Date:</span> 
                                            <?php if ($next_payment_date): ?>
                                                <?php echo $next_payment_date->format('F j, Y'); ?>
                                                <div class="countdown mt-2" id="countdown"></div>
                                            <?php else: ?>
                                                Not available
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Family Members -->
                        <?php if (!empty($family_members)): ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="mb-4">Family Members</h5>
                                <div class="row">
                                    <?php foreach ($family_members as $member): ?>
                                    <div class="col-md-6">
                                        <div class="family-member-card">
                                            <p><span class="info-label">Name:</span> <?php echo htmlspecialchars($member['name']); ?></p>
                                            <p><span class="info-label">Relation:</span> <?php echo htmlspecialchars($member['relation']); ?></p>
                                            <p><span class="info-label">Gender:</span> <?php echo htmlspecialchars($member['gender']); ?></p>
                                            <p><span class="info-label">Age:</span> <?php echo $member['age']; ?> years</p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- End of Main Content -->

        <?php include 'includes/footer.php'; ?>

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Bootstrap core JavaScript-->
<script src="../style/js/jquery.min.js"></script>
<script src="../style/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="../style/js/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="../style/js/sb-admin-2.min.js"></script>

<!-- Countdown Script -->
<script>
<?php if ($next_payment_date): ?>
function updateCountdown() {
    const nextPayment = new Date('<?php echo $next_payment_date->format('Y-m-d H:i:s'); ?>');
    const now = new Date();
    const diff = nextPayment - now;

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    document.getElementById('countdown').innerHTML = 
        `${days}d ${hours}h ${minutes}m ${seconds}s until next payment`;
}

// Update countdown every second
setInterval(updateCountdown, 1000);
updateCountdown(); // Initial call
<?php endif; ?>
</script>

</body>
</html> 