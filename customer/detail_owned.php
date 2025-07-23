<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Get policy details
$policy_id = $_GET['policy_id'];
$customer_id = $_SESSION['customer_id'];

// Get policy information with agent details
$query = "SELECT cp.*, p.*, c.name as customer_name, a.name as agent_name, a.agent_id, a.email as agent_email,
          p.price as policy_price, p.payment_interval, p.policy_term
          FROM customer_policy cp 
          JOIN policies p ON cp.policy_id = p.policy_id 
          JOIN customers c ON cp.customer_id = c.customer_id 
          JOIN agents a ON c.agent_id = a.agent_id
          WHERE cp.policy_id = ? AND cp.customer_id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("ii", $policy_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$policy = $result->fetch_assoc();

// Get family members
$query = "SELECT * FROM family_member WHERE customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$family_members = $stmt->get_result();

// Get last payment
$query = "SELECT * FROM payments 
          WHERE customer_id = ? AND policy_id = ? 
          ORDER BY payment_date DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $customer_id, $policy_id);
$stmt->execute();
$last_payment = $stmt->get_result()->fetch_assoc();

// Calculate next payment date
$last_payment_date = $last_payment ? $last_payment['payment_date'] : $policy['start_date'];
$payment_interval = $policy['payment_interval'];
$next_payment_date = '';

// Initialize amount_per_interval
$amount_per_interval = 0;
$total_amount = $policy['policy_price']; // Use the explicitly selected price
$policy_term = $policy['policy_term']; // Get the policy term in years

switch (strtolower($payment_interval)) {
    case 'monthly':
        $amount_per_interval = $total_amount / ($policy_term * 12); // Monthly payment
        break;
    case 'quarterly':
        $amount_per_interval = $total_amount / ($policy_term * 4); // Quarterly payment
        break;
    case 'half_annually':
        $amount_per_interval = $total_amount / ($policy_term * 2); // Semi-annual payment
        break;
    case 'annually':
        $amount_per_interval = $total_amount / $policy_term; // Annual payment
        break;
}

if ($last_payment_date) {
    $last_date = new DateTime($last_payment_date);
    $next_date = clone $last_date;
    
    switch (strtolower($payment_interval)) {
        case 'monthly':
            $next_date->modify('+1 month');
            break;
        case 'quarterly':
            $next_date->modify('+3 months');
            break;
        case 'half_annually':
            $next_date->modify('+6 months');
            break;
        case 'annually':
            $next_date->modify('+1 year');
            break;
    }
    $next_payment_date = $next_date->format('Y-m-d H:i:s');
}

// Calculate time remaining for countdown
$now = new DateTime();
$next_payment = new DateTime($next_payment_date);
$time_remaining = $next_payment->getTimestamp() - $now->getTimestamp();

// Format dates for display
$next_payment_formatted = date('F j, Y', strtotime($next_payment_date));
$start_date_formatted = date('F j, Y', strtotime($policy['start_date']));
$end_date_formatted = date('F j, Y', strtotime($policy['end_date']));
$last_payment_formatted = $last_payment ? date('F j, Y', strtotime($last_payment['payment_date'])) : 'No payments yet';
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
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .policy-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 20px;
        }
        .policy-body {
            padding: 20px;
            background-color: white;
        }
        .countdown-timer {
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .payment-button {
            margin-bottom: 20px;
            text-align: right;
        }
        .payment-button .btn {
            padding: 10px 20px;
            font-size: 1.1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .countdown-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 5px;
        }
        .countdown-label {
            font-size: 1rem;
            text-transform: uppercase;
            margin: 0 5px;
        }
        .countdown-separator {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 5px;
            color: white;
        }
        .agent-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .family-member-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .info-label {
            font-weight: bold;
            color: #5a5c69;
        }
        .info-value {
            color: #858796;
        }
    </style>
</head>
<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
            <?php include 'includes/header.php'; ?>

            <!-- Begin Page Content -->
            <div class="container-fluid">
            <!-- Payment Button -->
            <div class="payment-button">
                <a href="bank_accounts.php?policy_id=<?php echo $policy_id; ?>" class="btn btn-primary">
                    <i class="fas fa-credit-card"></i> Make Payment
                </a>
            </div>

                <!-- Page Heading -->
                <h1 class="h3 mb-4 text-gray-800">Policy Details</h1>

                <!-- Countdown Timer -->
                <div class="countdown-timer">
                    <div class="mb-2">Next Payment Due: <?php echo $next_payment_formatted; ?></div>
                    <div class="mb-2">Time Remaining:</div>
                    <div id="countdown" class="d-flex justify-content-center align-items-center">
                        <div class="text-center">
                            <div class="countdown-number" id="days">00</div>
                            <div class="countdown-label">Days</div>
                        </div>
                        <div class="countdown-separator">:</div>
                        <div class="text-center">
                            <div class="countdown-number" id="hours">00</div>
                            <div class="countdown-label">Hours</div>
                        </div>
                        <div class="countdown-separator">:</div>
                        <div class="text-center">
                            <div class="countdown-number" id="minutes">00</div>
                            <div class="countdown-label">Minutes</div>
                        </div>
                        <div class="countdown-separator">:</div>
                        <div class="text-center">
                            <div class="countdown-number" id="seconds">00</div>
                            <div class="countdown-label">Seconds</div>
                        </div>
                    </div>
                </div>

                <!-- Policy Details Card -->
            <div class="policy-card">
                <div class="policy-header">
                        <h4 class="mb-0"><?php echo htmlspecialchars($policy['policy_name']); ?></h4>
                    </div>
                <div class="policy-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <span class="info-label">Policy Type:</span>
                                <span class="info-value"><?php echo htmlspecialchars($policy['policy_type']); ?></span>
                            </div>
                            <div class="mb-3">
                                <span class="info-label">Start Date:</span>
                                <span class="info-value"><?php echo $start_date_formatted; ?></span>
                            </div>
                            <div class="mb-3">
                                <span class="info-label">End Date:</span>
                                <span class="info-value"><?php echo $end_date_formatted; ?></span>
                            </div>
                            <div class="mb-3">
                                <span class="info-label">Total Amount:</span>
                                <span class="info-value">$<?php echo number_format($policy['policy_price'], 2); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <span class="info-label">Payment Interval:</span>
                                <span class="info-value"><?php echo htmlspecialchars($payment_interval); ?></span>
                            </div>
                            <div class="mb-3">
                                <span class="info-label">Amount per <?php echo htmlspecialchars($payment_interval); ?> Payment:</span>
                                <span class="info-value">$<?php echo number_format($amount_per_interval, 2); ?></span>
                            </div>
                            <div class="mb-3">
                                <span class="info-label">Last Payment Date:</span>
                                <span class="info-value"><?php echo $last_payment_formatted; ?></span>
                        </div>
                            <div class="mb-3">
                                <span class="info-label">Next Payment Date:</span>
                                <span class="info-value"><?php echo $next_payment_formatted; ?></span>
                                        </div>
                            <div class="mb-3">
                                <span class="info-label">Profit Rate:</span>
                                <span class="info-value"><?php echo $policy['profit_rate']; ?>%</span>
                                            </div>
                            <div class="mb-3">
                                <span class="info-label">Status:</span>
                                <span class="badge badge-success">Active</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

            <!-- Agent Information Card -->
            <div class="agent-card">
                <h5 class="mb-3">Your Insurance Agent</h5>
                            <div class="row">
                    <div class="col-md-8">
                        <div class="mb-2">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($policy['agent_name']); ?></span>
                                </div>
                        <div class="mb-2">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($policy['agent_email']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="agent.php?id=<?php echo $policy['agent_id']; ?>" class="btn btn-info mr-2">
                            <i class="fas fa-user-tie"></i> View Profile
                        </a>
                        <a href="feedback.php?agent_id=<?php echo $policy['agent_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-comments"></i> Contact
                        </a>
                        </div>
                    </div>
                </div>

                <!-- Family Members Section -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Family Members</h6>
                    </div>
                <div class="card-body">
                    <?php if ($family_members->num_rows > 0): ?>
                        <div class="row">
                            <?php while ($member = $family_members->fetch_assoc()): ?>
                            <div class="col-md-4">
                                <div class="family-member-card">
                                    <h6><?php echo htmlspecialchars($member['name']); ?></h6>
                                        <div class="mb-1">
                                            <span class="info-label">Relation:</span>
                                            <span class="info-value"><?php echo htmlspecialchars($member['relation']); ?></span>
                                        </div>
                                        <div class="mb-1">
                                            <span class="info-label">Age:</span>
                                            <span class="info-value"><?php echo htmlspecialchars($member['age']); ?></span>
                                        </div>
                                        <div class="mb-1">
                                            <span class="info-label">Gender:</span>
                                            <span class="info-value"><?php echo htmlspecialchars($member['gender']); ?></span>
                                        </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No family members registered.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->

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

<script>
// Get the initial time remaining from PHP
const initialTimeRemaining = <?php echo $time_remaining; ?>;
let timeRemaining = initialTimeRemaining;

function updateCountdown() {
    if (timeRemaining <= 0) {
        document.getElementById('countdown').innerHTML = '<div class="text-danger">Payment Due!</div>';
        return;
    }

    const days = Math.floor(timeRemaining / (24 * 60 * 60));
    const hours = Math.floor((timeRemaining % (24 * 60 * 60)) / (60 * 60));
    const minutes = Math.floor((timeRemaining % (60 * 60)) / 60);
    const seconds = Math.floor(timeRemaining % 60);

    document.getElementById('days').textContent = days.toString().padStart(2, '0');
    document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
    document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
    document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');

    timeRemaining--;
}

// Update countdown every second
updateCountdown(); // Initial call
setInterval(updateCountdown, 1000);
</script>

</body>
</html> 