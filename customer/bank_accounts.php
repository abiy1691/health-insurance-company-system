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

// Bank account details
$banks = [
    [
        'name' => 'Commercial Bank of Ethiopia',
        'account_name' => 'Health Insurance Company',
        'account_number' => '1000000000001',
        'logo' => '../image/banks/cbe.png'
    ],
    [
        'name' => 'Dashen Bank',
        'account_name' => 'Health Insurance Company',
        'account_number' => '1000000000002',
        'logo' => '../image/banks/dashen.png'
    ],
    [
        'name' => 'Zemen Bank',
        'account_name' => 'Health Insurance Company',
        'account_number' => '1000000000003',
        'logo' => '../image/banks/zemen.png'
    ],
    [
        'name' => 'Bank of Abyssinia',
        'account_name' => 'Health Insurance Company',
        'account_number' => '1000000000004',
        'logo' => '../image/banks/abyssinia.png'
    ],
    [
        'name' => 'Awash Bank',
        'account_name' => 'Health Insurance Company',
        'account_number' => '1000000000005',
        'logo' => '../image/banks/awash.png'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Accounts | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .bank-card {
            background: #dda15e;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            overflow: hidden;
            position: relative;
            border-top: 8px solid;
        }
        .bank-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        .bank-card.cbe {
            border-top-color: #1a237e;
        }
        .bank-card.dashen {
            border-top-color: #c62828;
        }
        .bank-card.zemen {
            border-top-color: #2e7d32;
        }
        .bank-card.abyssinia {
            border-top-color: #6a1b9a;
        }
        .bank-card.awash {
            border-top-color: #e65100;
        }
        .bank-logo {
            height: 80px;
            width: auto;
            object-fit: contain;
            margin: 1.5rem auto;
            display: block;
            transition: transform 0.3s ease;
        }
        .bank-card:hover .bank-logo {
            transform: scale(1.05);
        }
        .bank-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .account-details {
            background: #495057;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 10px;
           
        }
        .account-details p {
            margin-bottom: 0.5rem;
            color: #666;
        }
        .account-details strong {
            color: #333;
        }
        .payment-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2e59d9;
            margin: 1rem 0;
            text-align: center;
        }
        .pay-btn {
            background: #2e59d9;
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
            margin-top: 1rem;
        }
        .pay-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            background: #1e3a8a;
            color: white;
        }
        .policy-info {
            background: #dda15e;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 1px solid #e3e6f0;
        }
        .policy-info h4 {
            color: #2e59d9;
            margin-bottom: 1rem;
        }
        .policy-info p {
            margin-bottom: 0.5rem;
            color: #666;
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
                    <h1 class="h3 mb-0 text-gray-800">Payment Options</h1>
                </div>

                <!-- Policy Information -->
                <div class="policy-info">
                    <h4>Policy Details</h4>
                    <p><strong>Policy Name:</strong> <?php echo htmlspecialchars($policy['policy_name']); ?></p>
                    <p><strong>Payment Amount:</strong> ETB <?php echo number_format($payment_amount, 2); ?> <?php echo $payment_period; ?></p>
                    <p><strong>Payment Interval:</strong> <?php echo ucfirst($policy['payment_interval']); ?></p>
                </div>

                <!-- Bank Cards -->
                <div class="row">
                    <?php foreach ($banks as $bank): ?>
                    <div class="col-xl-4 col-md-6">
                        <div class="bank-card <?php echo strtolower(str_replace(' ', '', $bank['name'])); ?>">
                            <div class="card-body p-4">
                                <div class="text-center">
                                    <img src="<?php echo $bank['logo']; ?>" alt="<?php echo $bank['name']; ?>" class="bank-logo">
                                    <h5 class="bank-name"><?php echo $bank['name']; ?></h5>
                                </div>
                                
                                <div class="account-details">
                                    <p><strong>Account Name:</strong> <?php echo $bank['account_name']; ?></p>
                                    <p><strong>Account Number:</strong> <?php echo $bank['account_number']; ?></p>
                                </div>

                                <div class="payment-amount">
                                    ETB <?php echo number_format($payment_amount, 2); ?>
                                </div>

                                <button class="btn pay-btn" onclick="processPayment('<?php echo $bank['name']; ?>')">
                                    <i class="fas fa-credit-card"></i> Pay Now
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
    function processPayment(bankName) {
        // Redirect to detail payment page with bank name and policy ID
        window.location.href = `detail_payment.php?bank=${encodeURIComponent(bankName)}&policy_id=<?php echo $policy_id; ?>`;
    }
</script>
</body>
</html> 