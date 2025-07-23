<?php
session_start();
include '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get customer's assigned agent
$customer_id = $_SESSION['customer_id'];
$query = "SELECT a.*, c.agent_id 
          FROM customers c 
          LEFT JOIN agents a ON c.agent_id = a.agent_id 
          WHERE c.customer_id = ?";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $customer_id);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$result = $stmt->get_result();
$agent = $result->fetch_assoc();

// If no agent is assigned
if (!$agent || !$agent['agent_id']) {
    $error = "No agent has been assigned to you yet.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Agent - Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .agent-profile-card {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
            color: white;
            position: relative;
            overflow: hidden;
            max-width: 500px;
            margin: 0 auto;
        }
        .agent-profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px 15px 0 0;
        }
        .agent-profile-card:hover {
            transform: translateY(-5px);
        }
        .photo-container {
            width: 100%;
            max-width: 180px;
            height: 130px;
            margin: 10px auto;
            position: relative;
            cursor: pointer;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        .agent-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
            opacity: 0.9;
        }
        .photo-container:hover .agent-photo {
            transform: scale(1.05);
            opacity: 1;
        }
        .agent-info {
            padding: 8px;
            background: rgba(255, 255, 255, 0.1);
            margin: 10px;
            border-radius: 10px;
            backdrop-filter: blur(5px);
            max-width: 450px;
            margin: 0 auto;
        }
        .info-item {
            margin-bottom: 6px;
            padding: 6px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .info-item:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateX(5px);
        }
        .info-label {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            margin-bottom: 2px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-value {
            color: white;
            font-size: 0.85rem;
        }
        .expertise-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
            margin: 2px;
            font-size: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .no-agent-message {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            color: white;
            max-width: 500px;
            margin: 0 auto;
        }
        .agent-name {
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 6px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        .card-body {
            position: relative;
            z-index: 1;
            padding: 12px;
        }
        .page-title {
            text-align: center;
            margin: 15px 0;
            color: #4e73df;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 1.6rem;
        }
        /* Modal styles for enlarged photo */
        .modal-content {
            background: transparent;
            border: none;
        }
        .modal-body {
            padding: 0;
            text-align: center;
        }
        .enlarged-photo {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
        }
        .container-fluid {
            padding: 15px;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'includes/header.php'; ?>
                
                <div class="container-fluid">
                    <h1 class="page-title">My Agent</h1>

                    <div class="row justify-content-center">
                        <div class="col-xl-5 col-lg-6">
                            <?php if (isset($error)): ?>
                                <div class="no-agent-message">
                                    <i class="fas fa-user-tie fa-4x mb-3 text-gray-400"></i>
                                    <h3 class="text-gray-800"><?php echo $error; ?></h3>
                                    <p class="text-gray-600">Please contact the manager to get an agent assigned to you.</p>
                                </div>
                            <?php else: ?>
                                <div class="card agent-profile-card">
                                    <div class="card-body text-center">
                                        <div class="photo-container" data-toggle="modal" data-target="#photoModal">
                                            <img src="<?php echo htmlspecialchars($agent['photo'] ?? '../style/img/agent-default.jpg'); ?>" 
                                                 alt="Agent Photo" 
                                                 class="agent-photo">
                                        </div>
                                        
                                        <h2 class="agent-name"><?php echo htmlspecialchars($agent['name']); ?></h2>
                                        
                                        <div class="agent-info">
                                            <div class="info-item">
                                                <div class="info-label">Email</div>
                                                <div class="info-value">
                                                    <i class="fas fa-envelope mr-2 text-primary"></i>
                                                    <?php echo htmlspecialchars($agent['email']); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="info-item">
                                                <div class="info-label">Expertise</div>
                                                <div class="info-value">
                                                    <?php 
                                                    if (!empty($agent['expertise'])) {
                                                        $expertise = explode(',', $agent['expertise']);
                                                        foreach ($expertise as $skill) {
                                                            echo '<span class="expertise-badge">' . htmlspecialchars(trim($skill)) . '</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted">No expertise specified</span>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            
                                            <div class="info-item">
                                                <div class="info-label">Contact Information</div>
                                                <div class="info-value">
                                                    <i class="fas fa-phone mr-2 text-primary"></i>
                                                    <?php echo htmlspecialchars($agent['phone'] ?? 'Not provided'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <img src="<?php echo htmlspecialchars($agent['photo'] ?? '../style/img/agent-default.jpg'); ?>" 
                         alt="Agent Photo" 
                         class="enlarged-photo">
                </div>
            </div>
        </div>
    </div>

    <script src="../style/js/bootstrap.bundle.min.js"></script>
    <script src="../style/js/sb-admin-2.min.js"></script>
</body>
</html>
