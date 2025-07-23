<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Get policy ID from URL
$policy_id = isset($_GET['policy_id']) ? intval($_GET['policy_id']) : 0;

// Fetch policy details
$policy = null;
if ($policy_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM Policies WHERE policy_id = ?");
    $stmt->bind_param("i", $policy_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $policy = $result->fetch_assoc();
    
    if (!$policy) {
        header("Location: policy_plans.php");
        exit();
    }
} else {
    header("Location: policy_plans.php");
    exit();
}

// Calculate payment amount based on interval
$total_price = $policy['price'];
$term = $policy['policy_term'];
$payment_amount = 0;
$payment_period = '';

switch ($policy['payment_interval']) {
    case 'monthly':
        $months = $term * 12;
        $payment_amount = $total_price / $months;
        $payment_period = '/month';
        break;
        
    case 'quarterly':
        $quarters = $term * 4;
        $payment_amount = $total_price / $quarters;
        $payment_period = '/3 months';
        break;
        
    case 'half_annually':
        $periods = $term * 2;
        $payment_amount = $total_price / $periods;
        $payment_period = '/6 months';
        break;
        
    case 'annually':
        $payment_amount = $total_price / $term;
        $payment_period = '/year';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enroll in Policy | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .policy-details-card {
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            max-width: 600px;
            margin: 0 auto;
        }
        .policy-details-card-1 {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        }
        .policy-details-card-2 {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
        }
        .policy-details-card-3 {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .policy-details-card-4 {
            background: linear-gradient(135deg, #f46b45 0%, #eea849 100%);
        }
        .policy-details-card-5 {
            background: linear-gradient(135deg, #8e2de2 0%, #4a00e0 100%);
        }
        .policy-details-card-6 {
            background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
        }
        .policy-details-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        .policy-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .policy-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        .policy-price {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0.75rem 0;
            color: rgba(255, 255, 255, 0.95);
        }
        .policy-features {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem;
            border-radius: 10px;
            margin: 0.75rem 0;
        }
        .policy-features p {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .policy-features i {
            margin-right: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .agreement-checkbox {
            margin: 1.5rem 0;
        }
        .agreement-checkbox label {
            font-size: 0.9rem;
            color: white;
        }
        .pay-now-btn {
            background: #00c853;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 1rem;
            width: 100%;
            max-width: 250px;
            margin: 0 auto;
            display: block;
        }
        .pay-now-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            background: #00b34a;
            color: white;
        }
        .pay-now-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Policy Enrollment</h1>
                </div>

                <!-- Policy Details Card -->
                <div class="row justify-content-center">
                    <div class="col-xl-8 col-lg-10">
                        <?php
                        // Determine which color class to use based on policy_id
                        $color_index = ($policy['policy_id'] - 1) % 6 + 1;
                        $card_class = "policy-details-card policy-details-card-{$color_index}";
                        ?>
                        <div class="card shadow <?php echo $card_class; ?>">
                            <div class="card-body p-4">
                                <div class="text-center">
                                    <i class="fas fa-shield-alt policy-icon"></i>
                                    <h3 class="policy-title"><?php echo htmlspecialchars($policy['policy_name']); ?></h3>
                                </div>
                                
                                <div class="policy-price text-center">
                                    Total Price: ETB <?php echo number_format($policy['price'], 2); ?>
                                </div>

                                <div class="policy-features">
                                    <p><i class="fas fa-check-circle"></i> Type: <?php echo ucfirst(htmlspecialchars($policy['policy_type'])); ?></p>
                                    <p><i class="fas fa-percentage"></i> Profit Rate: <?php echo number_format($policy['profit_rate'], 2); ?>%</p>
                                    <p><i class="fas fa-clock"></i> Term: <?php echo $policy['policy_term']; ?> years</p>
                                    <p><i class="fas fa-calendar-alt"></i> Payment Interval: <?php echo ucfirst($policy['payment_interval']); ?></p>
                                    <p><i class="fas fa-calculator"></i> Payment Amount: ETB <?php echo number_format($payment_amount, 2); ?> <?php echo $payment_period; ?></p>
                                    <p><i class="fas fa-info-circle"></i> Description: <?php echo htmlspecialchars($policy['description']); ?></p>
                                </div>

                                <div class="agreement-checkbox text-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="policyAgreement">
                                        <label class="form-check-label" for="policyAgreement">
                                            I have read and understood the policy details carefully and agree to the terms and conditions
                                        </label>
                                    </div>
                                </div>

                                <div class="text-center">
                                    <a href="bank_accounts.php?policy_id=<?php echo $policy['policy_id']; ?>" 
                                       class="btn pay-now-btn">
                                        <i class="fas fa-credit-card"></i> Pay Now
                                    </a>
                                </div>
                            </div>
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
    $(document).ready(function() {
        // Enable/disable Pay Now button based on checkbox
        $('#policyAgreement').change(function() {
            $('#payNowBtn').prop('disabled', !this.checked);
        });

        // Handle Pay Now button click
        $('#payNowBtn').click(function() {
            if ($('#policyAgreement').is(':checked')) {
                // Here you can add the payment processing logic
                alert('Payment processing will be implemented here');
            }
        });
    });
</script>
</body>
</html> 