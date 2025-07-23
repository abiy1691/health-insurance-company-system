<?php
session_start();
include '../includes/config.php';

// Check if user is logged in as customer
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get customer's assigned agent
$customer_id = $_SESSION['customer_id'];
$query = "SELECT a.agent_id, a.name, a.email 
          FROM Customers c 
          JOIN Agents a ON c.agent_id = a.agent_id 
          WHERE c.customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$assigned_agent = $result->fetch_assoc();

// Get feedback messages between customer and their assigned agent
$feedbacks = [];
if ($assigned_agent) {
    $query = "SELECT f.*, 
              CASE 
                  WHEN f.customer_id = ? THEN 'sent'
                  ELSE 'received'
              END as feedback_type
              FROM Feedback f
              WHERE (f.customer_id = ? AND f.agent_id = ?)
              ORDER BY f.created_at ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $customer_id, $customer_id, $assigned_agent['agent_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $feedbacks = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Feedback - Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .chat-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            height: 600px;
            overflow-y: auto;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }
        .messages-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: auto;
        }
        .message {
            margin-bottom: 0;
            max-width: 70%;
            position: relative;
        }
        .customer-message {
            background: #1cc88a;
            color: white;
            border-radius: 15px;
            padding: 10px 15px;
            border-bottom-left-radius: 0;
        }
        .agent-message {
            margin-left: auto;
            background: #4e73df;
            color: white;
            border-radius: 15px;
            padding: 10px 15px;
            border-bottom-right-radius: 0;
        }
        .message-time {
            font-size: 0.7rem;
            color: #6c757d;
            margin-top: 5px;
        }
        .customer-message .message-time {
            text-align: left;
        }
        .agent-message .message-time {
            text-align: right;
        }
        .message-content {
            word-wrap: break-word;
        }
        .send-feedback-btn {
            background: #1cc88a;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .send-feedback-btn:hover {
            background: #17a673;
            transform: translateY(-2px);
        }
        .page-title {
            color: #4e73df;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .chat-header {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            color: #4e73df;
        }
        .agent-info {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
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
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert" id="messageAlert">
                            <?php 
                                echo $_SESSION['message'];
                                unset($_SESSION['message']);
                                unset($_SESSION['message_type']);
                            ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="page-title">Feedback</h1>
                    </div>

                    <?php if ($assigned_agent): ?>
                        <div class="agent-info">
                            <h4>Your Assigned Agent</h4>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($assigned_agent['name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($assigned_agent['email']); ?></p>
                        </div>

                        <div class="chat-container">
                            <div class="chat-header">
                                <i class="fas fa-comments"></i> Chat with Your Agent
                            </div>
                            <?php if (empty($feedbacks)): ?>
                                <div class="text-center text-muted">
                                    No messages yet. Start the conversation!
                                </div>
                            <?php else: ?>
                                <div class="messages-container">
                                    <?php foreach ($feedbacks as $feedback): ?>
                                        <div class="message <?php echo $feedback['feedback_type'] === 'sent' ? 'customer-message' : 'agent-message'; ?>">
                                            <div class="message-content">
                                                <?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?>
                                            </div>
                                            <div class="message-time">
                                                <?php echo date('g:i A', strtotime($feedback['created_at'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="text-center">
                            <button class="send-feedback-btn" data-toggle="modal" data-target="#sendFeedbackModal">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            You don't have an assigned agent yet. Please contact the manager for assistance.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Send Feedback Modal -->
    <div class="modal fade" id="sendFeedbackModal" tabindex="-1" role="dialog" aria-labelledby="sendFeedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendFeedbackModalLabel">Send Message to Your Agent</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="send_feedback.php" method="POST">
                    <input type="hidden" name="agent_id" value="<?php echo $assigned_agent['agent_id']; ?>">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="feedback_text">Your Message</label>
                            <textarea class="form-control" id="feedback_text" name="feedback_text" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../style/js/bootstrap.bundle.min.js"></script>
    <script src="../style/js/sb-admin-2.min.js"></script>
    <script>
        // Auto-hide alert after 3 seconds
        $(document).ready(function() {
            if ($('#messageAlert').length) {
                setTimeout(function() {
                    $('#messageAlert').alert('close');
                }, 3000);
            }
            
            // Scroll to bottom of chat container
            var chatContainer = document.querySelector('.chat-container');
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        });
    </script>
</body>
</html>
