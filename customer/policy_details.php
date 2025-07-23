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

// Get policy information
$query = "SELECT cp.*, p.*, c.name as customer_name 
          FROM customer_policy cp 
          JOIN policies p ON cp.policy_id = p.policy_id 
          JOIN customers c ON cp.customer_id = c.customer_id 
          WHERE cp.policy_id = ? AND cp.customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $policy_id, $customer_id);
$stmt->execute();
$policy = $stmt->get_result()->fetch_assoc();

// Get family members
$query = "SELECT * FROM family_member WHERE customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$family_members = $stmt->get_result();

// Calculate next payment date
$last_payment_date = strtotime($policy['start_date']);
$interval = $policy['payment_interval'];
$next_payment_date = strtotime("+1 " . $interval, $last_payment_date);
$time_remaining = $next_payment_date - time();
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
        .policy-details-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .countdown-timer {
            font-size: 2rem;
            font-weight: bold;
            color: #4e73df;
            text-align: center;
            padding: 20px;
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .family-member-card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            padding: 15px;
        }
        .detail-label {
            font-weight: bold;
            color: #5a5c69;
        }
        .policy-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
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
                <h1 class="h3 mb-4 text-gray-800">Policy Details</h1>

                <!-- Countdown Timer -->
                <div class="countdown-timer">
                    <div>Next Payment Due In:</div>
                    <div id="countdown"></div>
                </div>

                <!-- Policy Details Card -->
                <div class="card policy-details-card">
                    <div class="policy-header">
                        <h4 class="mb-0"><?php echo htmlspecialchars($policy['policy_name']); ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><span class="detail-label">Policy Type:</span> <?php echo ucfirst($policy['policy_type']); ?></p>
                                <p><span class="detail-label">Start Date:</span> <?php echo date('F j, Y', strtotime($policy['start_date'])); ?></p>
                                <p><span class="detail-label">End Date:</span> <?php echo date('F j, Y', strtotime($policy['end_date'])); ?></p>
                                <p><span class="detail-label">Payment Interval:</span> <?php echo ucfirst($policy['payment_interval']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><span class="detail-label">Price:</span> ETB <?php echo number_format($policy['price'], 2); ?></p>
                                <p><span class="detail-label">Status:</span> <span class="badge badge-success"><?php echo $policy['status']; ?></span></p>
                                <p><span class="detail-label">Customer Name:</span> <?php echo htmlspecialchars($policy['customer_name']); ?></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h5 class="detail-label">Policy Description:</h5>
                            <p><?php echo htmlspecialchars($policy['description']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Family Members Section -->
                <div class="card policy-details-card">
                    <div class="card-header">
                        <h5 class="mb-0">Family Members Covered</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php while ($member = $family_members->fetch_assoc()): ?>
                            <div class="col-md-4">
                                <div class="family-member-card">
                                    <h6><?php echo htmlspecialchars($member['name']); ?></h6>
                                    <p class="mb-1"><span class="detail-label">Relation:</span> <?php echo $member['relation']; ?></p>
                                    <p class="mb-1"><span class="detail-label">Age:</span> <?php echo $member['age']; ?></p>
                                    <p class="mb-0"><span class="detail-label">Gender:</span> <?php echo $member['gender']; ?></p>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
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

<script>
// Countdown timer
function updateCountdown() {
    const timeRemaining = <?php echo $time_remaining; ?>;
    const days = Math.floor(timeRemaining / (24 * 60 * 60));
    const hours = Math.floor((timeRemaining % (24 * 60 * 60)) / (60 * 60));
    const minutes = Math.floor((timeRemaining % (60 * 60)) / 60);
    const seconds = Math.floor(timeRemaining % 60);

    document.getElementById('countdown').innerHTML = 
        `${days}d ${hours}h ${minutes}m ${seconds}s`;
}

updateCountdown();
setInterval(updateCountdown, 1000);
</script>

</body>
</html> 