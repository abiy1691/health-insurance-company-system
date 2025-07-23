<?php
session_start();
include '../includes/config.php';

// Check if user is logged in as agent
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../login.php");
    exit();
}

$agent_id = $_SESSION['agent_id'];

// Get manager details
$manager_query = "SELECT manager_id, name, email FROM managers LIMIT 1";
$manager_result = $conn->query($manager_query);
$manager = $manager_result->fetch_assoc();

// Get assigned customers
$customers_query = "SELECT c.customer_id, c.name, c.email 
                   FROM customers c 
                   WHERE c.agent_id = ? 
                   ORDER BY c.name ASC";
$stmt = $conn->prepare($customers_query);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$customers_result = $stmt->get_result();
$customers = $customers_result->fetch_all(MYSQLI_ASSOC);

// Get selected conversation (manager or customer)
$selected_id = isset($_GET['id']) ? $_GET['id'] : null;
$selected_type = isset($_GET['type']) ? $_GET['type'] : null;

// Get messages for selected conversation
$messages = [];
if ($selected_id && $selected_type) {
    if ($selected_type === 'manager') {
        $query = "SELECT f.*, 
                 CASE 
                     WHEN f.agent_id = ? THEN 'sent'
                     ELSE 'received'
                 END as message_type
                 FROM feedback f
                 WHERE (f.agent_id = ? AND f.manager_id = ?)
                 ORDER BY f.created_at ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $agent_id, $agent_id, $selected_id);
    } else {
        $query = "SELECT f.*, 
                 CASE 
                     WHEN f.agent_id = ? THEN 'sent'
                     ELSE 'received'
                 END as message_type
                 FROM feedback f
                 WHERE (f.agent_id = ? AND f.customer_id = ?)
                 ORDER BY f.created_at ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $agent_id, $agent_id, $selected_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Feedback - Health Insurance</title>
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
            gap: 20px;
            margin-top: auto;
            width: 100%;
            padding: 10px;
        }
        .message {
            margin-bottom: 0;
            max-width: 70%;
            position: relative;
            display: flex;
            flex-direction: column;
            clear: both;
        }
        .sent-message {
            background: #1cc88a;
            color: white;
            border-radius: 15px;
            padding: 10px 15px;
            border-bottom-left-radius: 0;
            margin-right: auto;
            margin-left: 0;
            align-self: flex-start;
            float: none;
        }
        .received-message {
            background: #4e73df;
            color: white;
            border-radius: 15px;
            padding: 10px 15px;
            border-bottom-right-radius: 0;
            margin-left: auto;
            margin-right: 0;
            align-self: flex-end;
            float: none;
        }
        .message-header {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .message-sender {
            font-weight: 600;
        }
        .message-time {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 5px;
            display: block;
        }
        .sent-message .message-time {
            text-align: left;
        }
        .received-message .message-time {
            text-align: right;
        }
        .message-content {
            word-wrap: break-word;
            display: block;
            line-height: 1.4;
        }
        .send-feedback-btn {
            background: #4e73df;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .send-feedback-btn:hover {
            background: #2e59d9;
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
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .contact-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .contact-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .contact-item:hover {
            background: #f8f9fc;
        }
        .contact-item.active {
            background: #4e73df;
            color: white;
        }
        .contact-name {
            font-weight: 600;
        }
        .contact-email {
            font-size: 0.8rem;
            color: #6c757d;
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

                    <div class="row">
                        <!-- Contact List -->
                        <div class="col-md-4">
                            <div class="contact-list">
                                <h4>Manager</h4>
                                <div class="contact-item <?php echo ($selected_type === 'manager') ? 'active' : ''; ?>" 
                                     onclick="window.location.href='feedback.php?type=manager&id=<?php echo $manager['manager_id']; ?>'">
                                    <div class="contact-name"><?php echo htmlspecialchars($manager['name']); ?></div>
                                    <div class="contact-email"><?php echo htmlspecialchars($manager['email']); ?></div>
                                </div>

                                <h4 class="mt-4">Assigned Customers</h4>
                                <?php if (count($customers) > 0): ?>
                                    <?php foreach ($customers as $customer): ?>
                                        <div class="contact-item <?php echo ($selected_type === 'customer' && $selected_id == $customer['customer_id']) ? 'active' : ''; ?>" 
                                             onclick="window.location.href='feedback.php?type=customer&id=<?php echo $customer['customer_id']; ?>'">
                                            <div class="contact-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                                            <div class="contact-email"><?php echo htmlspecialchars($customer['email']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-muted">No assigned customers</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Chat Area -->
                        <div class="col-md-8">
                            <?php if ($selected_id && $selected_type): ?>
                                <div class="chat-container">
                                    <div class="chat-header">
                                        <i class="fas fa-comments"></i> 
                                        <?php 
                                        if ($selected_type === 'manager') {
                                            echo "Chat with Manager";
                                        } else {
                                            $customer_name = '';
                                            foreach ($customers as $customer) {
                                                if ($customer['customer_id'] == $selected_id) {
                                                    $customer_name = $customer['name'];
                                                    break;
                                                }
                                            }
                                            echo "Chat with " . htmlspecialchars($customer_name);
                                        }
                                        ?>
                                    </div>
                                    <?php if (empty($messages)): ?>
                                        <div class="text-center text-muted">
                                            No messages yet. Start the conversation!
                                        </div>
                                    <?php else: ?>
                                        <div class="messages-container">
                                            <?php foreach ($messages as $message): ?>
                                                <div class="message <?php echo $message['message_type'] === 'sent' ? 'sent-message' : 'received-message'; ?>">
                                                    <div class="message-header">
                                                        <span class="message-sender">
                                                            <?php 
                                                            if ($message['message_type'] === 'sent') {
                                                                echo "You";
                                                            } else {
                                                                if ($selected_type === 'manager') {
                                                                    echo htmlspecialchars($manager['name']);
                                                                } else {
                                                                    foreach ($customers as $customer) {
                                                                        if ($customer['customer_id'] == $selected_id) {
                                                                            echo htmlspecialchars($customer['name']);
                                                                            break;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <div class="message-content">
                                                        <?php echo nl2br(htmlspecialchars($message['feedback_text'])); ?>
                                                    </div>
                                                    <div class="message-time">
                                                        <?php echo date('g:i A', strtotime($message['created_at'])); ?>
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
                                <div class="alert alert-info">
                                    Select a contact to start chatting
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Send Feedback Modal -->
    <?php if ($selected_id && $selected_type): ?>
    <div class="modal fade" id="sendFeedbackModal" tabindex="-1" role="dialog" aria-labelledby="sendFeedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendFeedbackModalLabel">Send Message</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="send_feedback.php" method="POST">
                    <input type="hidden" name="receiver_id" value="<?php echo $selected_id; ?>">
                    <input type="hidden" name="receiver_type" value="<?php echo $selected_type; ?>">
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
    <?php endif; ?>

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
