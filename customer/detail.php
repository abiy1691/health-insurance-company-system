<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Get bank name and policy ID from URL
$bank_name = isset($_GET['bank']) ? $_GET['bank'] : '';
$policy_id = isset($_GET['policy_id']) ? intval($_GET['policy_id']) : 0;

// Validate inputs
if (empty($bank_name) || $policy_id <= 0) {
    header("Location: process_payment.php");
    exit();
}

// Fetch policy details
$stmt = $conn->prepare("SELECT * FROM Policies WHERE policy_id = ?");
$stmt->bind_param("i", $policy_id);
$stmt->execute();
$result = $stmt->get_result();
$policy = $result->fetch_assoc();

if (!$policy) {
    header("Location: process_payment.php");
    exit();
}

// Calculate payment amount
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

// Get bank details
$bank_logo = "../image/banks/" . strtolower(str_replace(' ', '_', $bank_name)) . ".png";

// Get customer details
$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT * FROM Customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

// Get customer's full name
$customer_name = '';
if (isset($customer['first_name']) && isset($customer['last_name'])) {
    $customer_name = $customer['first_name'] . ' ' . $customer['last_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Details | Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .bank-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e3e6f0;
        }
        .bank-logo {
            height: 80px;
            width: auto;
            margin-bottom: 1rem;
            object-fit: contain;
        }
        .bank-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2e59d9;
            margin-bottom: 0.5rem;
        }
        .payment-details {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .payment-details p {
            margin-bottom: 0.75rem;
            color: #666;
            font-size: 1.1rem;
        }
        .payment-details strong {
            color: #2e59d9;
        }
        .payment-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2e59d9;
            text-align: center;
            margin: 1.5rem 0;
            padding: 1rem;
            background: #f8f9fc;
            border-radius: 10px;
            border: 2px dashed #2e59d9;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e3e6f0;
        }
        .form-control:focus {
            border-color: #2e59d9;
            box-shadow: 0 0 0 0.2rem rgba(46, 89, 217, 0.25);
        }
        .upload-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .upload-btn {
            background: #2e59d9;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .upload-btn:hover {
            background: #1e3a8a;
        }
        .file-name-display {
            flex: 1;
            padding: 0.5rem 1rem;
            background: #f8f9fc;
            border-radius: 5px;
            color: #666;
            font-size: 0.9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .preview-container {
            margin-top: 1rem;
            text-align: center;
        }
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            display: none;
        }
        .submit-btn {
            background: #2e59d9;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            margin-top: 1.5rem;
        }
        .submit-btn:hover {
            background: #2e59d9;
            opacity: 0.9;
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

                <!-- Payment Form -->
                <div class="payment-container">
                    <div class="bank-header">
                        <h3 class="bank-name"><?php echo $bank_name; ?></h3>
                    </div>

                    <div class="payment-details">
                        <p><strong>Account Holder:</strong> Health Insurance Company</p>
                        <p><strong>Policy Name:</strong> <?php echo htmlspecialchars($policy['policy_name']); ?></p>
                        <p><strong>Payment Interval:</strong> <?php echo ucfirst($policy['payment_interval']); ?></p>
                    </div>

                    <div class="payment-amount">
                        Amount to be paid: ETB <?php echo number_format($payment_amount, 2); ?>
                    </div>

                    <form action="process_payment.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="bank_name" value="<?php echo htmlspecialchars($bank_name); ?>">
                        <input type="hidden" name="policy_id" value="<?php echo $policy_id; ?>">
                        <input type="hidden" name="payment_amount" value="<?php echo $payment_amount; ?>">

                        <div class="form-group">
                            <label for="sender_name">Sender Name</label>
                            <input type="text" class="form-control" id="sender_name" name="sender_name" value="<?php echo htmlspecialchars($customer_name); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Payment Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Payment Proof</label>
                            <div class="upload-container">
                                <button type="button" class="upload-btn" onclick="document.getElementById('payment_proof').click()">
                                    <i class="fas fa-upload"></i> Choose File
                                </button>
                                <div class="file-name-display">No file chosen</div>
                            </div>
                            <input type="file" id="payment_proof" name="payment_proof" accept=".jpg,.jpeg,.png" style="display: none;" required onchange="previewImage(this)">
                            <div class="preview-container mt-3" style="display: none;">
                                <img id="preview" class="preview-image" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 8px;">
                            </div>
                        </div>

                        <button type="submit" class="btn submit-btn">
                            <i class="fas fa-check-circle"></i> Submit Payment
                        </button>
                    </form>
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
    // Handle file upload display
    document.getElementById('payment_proof').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('preview');
        const fileNameDisplay = document.querySelector('.file-name-display');
        
        if (file) {
            // Check file size (2MB limit)
            if (file.size > 2 * 1024 * 1024) {
                alert('File size exceeds 2MB limit');
                this.value = '';
                fileNameDisplay.textContent = 'No file chosen';
                return;
            }
            
            // Check file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please upload a JPG or PNG file');
                this.value = '';
                fileNameDisplay.textContent = 'No file chosen';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                fileNameDisplay.textContent = file.name;
            }
            reader.readAsDataURL(file);
        } else {
            fileNameDisplay.textContent = 'No file chosen';
        }
    });

    function previewImage(input) {
        const previewContainer = document.querySelector('.preview-container');
        const preview = document.getElementById('preview');
        const fileNameDisplay = document.querySelector('.file-name-display');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                previewContainer.style.display = 'block';
                fileNameDisplay.textContent = input.files[0].name;
            }
            
            reader.readAsDataURL(input.files[0]);
        } else {
            previewContainer.style.display = 'none';
            fileNameDisplay.textContent = 'No file chosen';
        }
    }
</script>
</body>
</html> 