<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Get payment details from POST data
$bank_name = isset($_POST['bank_name']) ? $_POST['bank_name'] : '';
$policy_id = isset($_POST['policy_id']) ? intval($_POST['policy_id']) : 0;
$payment_amount = isset($_POST['payment_amount']) ? floatval($_POST['payment_amount']) : 0;
$description = isset($_POST['description']) ? $_POST['description'] : '';
$sender_name = isset($_POST['sender_name']) ? $_POST['sender_name'] : '';

// Define bank account numbers
$bank_accounts = [
    'Commercial Bank of Ethiopia' => '1000000000001',
    'Dashen Bank' => '1000000000002',
    'Zemen Bank' => '1000000000003',
    'Bank of Abyssinia' => '1000000000004',
    'Awash Bank' => '1000000000005'
];

// Get the account number for the selected bank
$account_number = isset($bank_accounts[$bank_name]) ? $bank_accounts[$bank_name] : '';

// Get customer details
$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT name FROM customers WHERE customer_id = ?");
if (!$stmt) {
    die("Error preparing customer query: " . $conn->error);
}
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if (!$customer) {
    die("Customer not found with ID: " . $customer_id);
}

// Set default sender name to customer's name
if (empty($sender_name)) {
    $sender_name = $customer['name'];
}

// Get policy details
$stmt = $conn->prepare("SELECT * FROM policies WHERE policy_id = ?");
if (!$stmt) {
    die("Error preparing policy query: " . $conn->error);
}
$stmt->bind_param("i", $policy_id);
$stmt->execute();
$result = $stmt->get_result();
$policy = $result->fetch_assoc();

if (!$policy) {
    die("Policy not found with ID: " . $policy_id);
}

// Check if customer already has this policy
$stmt = $conn->prepare("SELECT * FROM customer_policy WHERE customer_id = ? AND policy_id = ?");
if (!$stmt) {
    die("Error preparing customer_policy check query: " . $conn->error);
}
$stmt->bind_param("ii", $customer_id, $policy_id);
$stmt->execute();
$result = $stmt->get_result();
$existing_policy = $result->fetch_assoc();

// Calculate next payment date
$next_payment_date = date('Y-m-d');
switch ($policy['payment_interval']) {
    case 'monthly':
        $next_payment_date = date('Y-m-d', strtotime('+1 month'));
        break;
    case 'quarterly':
        $next_payment_date = date('Y-m-d', strtotime('+3 months'));
        break;
    case 'half_annually':
        $next_payment_date = date('Y-m-d', strtotime('+6 months'));
        break;
    case 'annually':
        $next_payment_date = date('Y-m-d', strtotime('+1 year'));
        break;
}

// Handle file upload for display
$payment_proof = '';
if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png'];
    $filename = $_FILES['payment_proof']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $new_filename = uniqid() . '.' . $ext;
        $upload_path = '../uploads/payment_proofs/' . $new_filename;
        
        if (!file_exists('../uploads/payment_proofs/')) {
            mkdir('../uploads/payment_proofs/', 0777, true);
        }
        
        if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path)) {
            $payment_proof = $new_filename;
        }
    }
}

// Only insert into Customer_Policy if the policy doesn't exist
if (!$existing_policy) {
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+' . $policy['policy_term'] . ' years'));
    
    // Use the customer's name from the database
    $customer_name = $customer['name'];

    $stmt = $conn->prepare("INSERT INTO customer_policy (
        customer_id, 
        policy_id, 
        start_date, 
        end_date, 
        policy_name, 
        customer_name, 
        status
    ) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    
    if (!$stmt) {
        die("Error preparing customer_policy insert query: " . $conn->error);
    }
    
    $stmt->bind_param(
        "iissss", 
        $customer_id, 
        $policy_id, 
        $start_date, 
        $end_date, 
        $policy['policy_name'], 
        $customer_name
    );
    
    if (!$stmt->execute()) {
        die("Error executing customer_policy insert: " . $stmt->error);
    }
}

// Insert into Payments table
$payment_date = date('Y-m-d');
$payment_status = 'Pending';
$payment_type = $existing_policy ? 'Renewal Payment' : 'Initial Payment';

// Get customer and policy details for the payment record
$customer_name = $customer['name'];
$policy_name = $policy['policy_name'];

// Insert into Payments table with all required fields
$stmt = $conn->prepare("INSERT INTO payments (
    customer_id, 
    policy_id, 
    amount, 
    customer_name, 
    policy_name, 
    bank_name, 
    account_number, 
    status, 
    proof_photo, 
    payment_date
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    die("Error preparing payments insert query: " . $conn->error);
}

// Bind parameters
$stmt->bind_param(
    "iidsssssss", 
    $customer_id, 
    $policy_id, 
    $payment_amount, 
    $customer_name, 
    $policy_name, 
    $bank_name, 
    $account_number, 
    $payment_status, 
    $payment_proof, 
    $payment_date
);

if (!$stmt->execute()) {
    die("Error executing payments insert: " . $stmt->error);
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
        .payment-success-container {
            max-width: 500px;
            margin: 2rem auto;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            border: 1px solid #e3e6f0;
        }
        .success-message {
            text-align: center;
            color: #28a745;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.2rem;
            padding: 0.5rem;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 8px;
        }
        .review-notice {
            text-align: center;
            color: #666;
            font-size: 1rem;
            margin-bottom: 1.2rem;
            padding: 0.8rem;
            background: rgba(78, 115, 223, 0.1);
            border-radius: 8px;
            border-left: 4px solid #4e73df;
        }
        .payment-details {
            background: #ffffff;
            border-radius: 10px;
            padding: 1.2rem;
            margin-bottom: 1.2rem;
            border: 1px solid #e3e6f0;
        }
        .payment-details p {
            margin-bottom: 0.6rem;
            color: #666;
            font-size: 1rem;
            padding: 0.3rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .payment-details p:last-child {
            border-bottom: none;
        }
        .payment-details strong {
            color: #4e73df;
            min-width: 150px;
            display: inline-block;
        }
        .payment-proof {
            margin: 1rem 0;
            text-align: center;
            background: #ffffff;
            padding: 0.8rem;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
            cursor: pointer;
            max-height: 180px;
            overflow: hidden;
        }
        .payment-proof h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
            color: #4e73df;
        }
        .payment-proof img {
            max-width: 100%;
            max-height: 120px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            object-fit: contain;
        }
        .payment-proof img:hover {
            transform: scale(1.05);
        }
        .ok-btn {
            background: #4e73df;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            width: 120px;
            margin: 1.2rem auto;
            display: block;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
        }
        .ok-btn:hover {
            background: #2e59d9;
            color: white;
            transform: translateX(-50%) translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal.show {
            opacity: 1;
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90vh;
            margin-top: 5vh;
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }
        .modal.show .modal-content {
            transform: scale(1);
        }
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal.show .close {
            opacity: 1;
        }
        .close:hover {
            color: #fff;
            text-shadow: 0 0 5px rgba(255,255,255,0.5);
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

                <div class="payment-success-container">
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> Payment Submitted Successfully
                    </div>

                    <div class="review-notice">
                        This payment will be reviewed and approved within 24 hours.
                    </div>

                    <div class="payment-details">
                        <p><strong>Sender Name:</strong> <?php 
                            if (!empty($sender_name)) {
                                echo htmlspecialchars($sender_name);
                            } else {
                                $customer_name = 'Unknown';
                                if (isset($customer['name'])) {
                                    $customer_name = htmlspecialchars($customer['name']);
                                }
                                echo $customer_name;
                            }
                        ?></p>
                        <p><strong>Receiver Name:</strong> Health Insurance Company</p>
                        <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($bank_name); ?></p>
                        <p><strong>Payment Date:</strong> <?php echo date('F j, Y'); ?></p>
                        <p><strong>Next Payment Date:</strong> <?php echo date('F j, Y', strtotime($next_payment_date)); ?></p>
                        <p><strong>Payment Status:</strong> <span style="color: #ffc107;">Pending</span></p>
                        <p><strong>Payment Amount:</strong> ETB <?php echo number_format($payment_amount, 2); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($description); ?></p>
                    </div>

                    <?php if ($payment_proof): ?>
                    <div class="payment-proof" onclick="openModal(this)">
                        <h4>Payment Proof</h4>
                        <img src="../uploads/payment_proofs/<?php echo htmlspecialchars($payment_proof); ?>" alt="Payment Proof">
                    </div>
                    <?php endif; ?>

                    <a href="policy_owned.php" class="btn ok-btn">
                        <i class="fas fa-check"></i> OK
                    </a>
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

<!-- Modal for enlarged image -->
<div id="imageModal" class="modal">
    <span class="close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

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
// Get the modal
var modal = document.getElementById("imageModal");

// Get the image and insert it inside the modal
function openModal(element) {
    var img = element.querySelector('img');
    var modalImg = document.getElementById("modalImage");
    modal.style.display = "block";
    // Trigger reflow
    modal.offsetHeight;
    modal.classList.add("show");
    modalImg.src = img.src;
}

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.classList.remove("show");
    setTimeout(function() {
        modal.style.display = "none";
    }, 300); // Match this with the transition duration
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.classList.remove("show");
        setTimeout(function() {
            modal.style.display = "none";
        }, 300); // Match this with the transition duration
    }
}
</script>

</body>
</html> 