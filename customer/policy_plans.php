<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Insurance Plans | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .policy-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            max-width: 350px;
            margin: 0 auto;
        }
        .policy-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .policy-card .card-body {
            padding: 1.25rem;
        }
        .policy-card-1 {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
        }
        .policy-card-2 {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            color: white;
        }
        .policy-card-3 {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        .policy-card-4 {
            background: linear-gradient(135deg, #f46b45 0%, #eea849 100%);
            color: white;
        }
        .policy-card-5 {
            background: linear-gradient(135deg, #8e2de2 0%, #4a00e0 100%);
            color: white;
        }
        .policy-card-6 {
            background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
            color: white;
        }
        .policy-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .policy-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .policy-price {
            font-size: 1.25rem;
            font-weight: bold;
            margin: 0.75rem 0;
            color: rgba(255, 255, 255, 0.95);
        }
        .policy-features {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem;
            border-radius: 8px;
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
        .payment-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }
        .payment-info i {
            margin-right: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .enroll-btn {
            background: #ffffff;
            color: #000000;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            font-size: 0.9rem;
        }
        .enroll-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            background: #f8f9fc;
            color: #000000;
        }
        .enroll-btn:active {
            transform: translateY(0);
        }
        .enroll-btn i {
            margin-right: 0.5rem;
            transition: transform 0.3s ease;
        }
        .enroll-btn:hover i {
            transform: scale(1.1);
        }
        .policy-card-1 .enroll-btn {
            background: #ffffff;
            color: #000000;
        }
        .policy-card-1 .enroll-btn:hover {
            background: #f8f9fc;
            color: #000000;
        }
        .policy-card-2 .enroll-btn {
            background: #ffffff;
            color: #000000;
        }
        .policy-card-2 .enroll-btn:hover {
            background: #f8f9fc;
            color: #000000;
        }
        .policy-card-3 .enroll-btn {
            background: #ffffff;
            color: #000000;
        }
        .policy-card-3 .enroll-btn:hover {
            background: #f8f9fc;
            color: #000000;
        }
        .policy-card-4 .enroll-btn {
            background: #ffffff;
            color: #000000;
        }
        .policy-card-4 .enroll-btn:hover {
            background: #f8f9fc;
            color: #000000;
        }
        .policy-card-5 .enroll-btn {
            background: #ffffff;
            color: #000000;
        }
        .policy-card-5 .enroll-btn:hover {
            background: #f8f9fc;
            color: #000000;
        }
        .policy-card-6 .enroll-btn {
            background: #ffffff;
            color: #000000;
        }
        .policy-card-6 .enroll-btn:hover {
            background: #f8f9fc;
            color: #000000;
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
                    <h1 class="h3 mb-0 text-gray-800">Available Insurance Plans</h1>
                </div>

                <!-- Content Row -->
                <div class="row">
                    <?php
                    // Fetch all policies from the database
                    $query = "SELECT * FROM Policies ORDER BY policy_id DESC";
                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0) {
                        $card_colors = ['policy-card-1', 'policy-card-2', 'policy-card-3', 'policy-card-4', 'policy-card-5', 'policy-card-6'];
                        $color_index = 0;
                        
                        while ($policy = $result->fetch_assoc()) {
                            $card_class = $card_colors[$color_index % count($card_colors)];
                            $color_index++;
                            ?>
                            <div class="col-xl-4 col-md-6 mb-4">
                                <div class="card shadow h-100 policy-card <?php echo $card_class; ?>">
                                    <div class="card-body">
                                        <div class="text-center">
                                            <i class="fas fa-shield-alt policy-icon"></i>
                                            <h5 class="policy-title"><?php echo htmlspecialchars($policy['policy_name']); ?></h5>
                                        </div>
                                        
                                        <div class="policy-price text-center">
                                            ETB <?php echo number_format($policy['price'], 2); ?>
                                        </div>

                                        <div class="payment-info">
                                            <i class="fas fa-clock"></i>
                                            <?php 
                                            $interval = ucfirst($policy['payment_interval']);
                                            $term = $policy['policy_term'];
                                            $total_price = $policy['price'];
                                            
                                            // Calculate payment amount based on interval
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
                                                    $payment_amount = $total_price / $term;  // Divide total price by number of years
                                                    $payment_period = '/year';
                                                    break;
                                            }
                                            
                                            echo "{$interval} payments for {$term} years";
                                            ?>
                                        </div>

                                        <div class="policy-features">
                                            <p><i class="fas fa-check-circle"></i> Type: <?php echo ucfirst(htmlspecialchars($policy['policy_type'])); ?></p>
                                            <p><i class="fas fa-percentage"></i> Profit Rate: <?php echo number_format($policy['profit_rate'], 2); ?>%</p>
                                            <p><i class="fas fa-calculator"></i> Payment Amount: ETB <?php echo number_format($payment_amount, 2); ?> <?php echo $payment_period; ?></p>
                                            <p><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($policy['description']); ?></p>
                                        </div>

                                        <div class="text-center">
                                            <a href="enrollment_details.php?policy_id=<?php echo $policy['policy_id']; ?>" 
                                               class="btn enroll-btn">
                                                <i class="fas fa-file-signature"></i> Enroll Now
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No insurance plans are currently available.
                            </div>
                        </div>
                        <?php
                    }
                    ?>
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
</body>
</html>
