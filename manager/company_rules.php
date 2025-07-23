<?php
session_start();
include '../includes/config.php';

// Check if user is logged in as manager
if (!isset($_SESSION['manager_id'])) {
    header("Location: ../login.php");
    exit();
}

$manager_id = $_SESSION['manager_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Add new rule
            $rule_content = $_POST['rule_content'];
            
            // Check if the table has the correct structure
            $check_columns_query = "SHOW COLUMNS FROM company_rules";
            $columns_result = $conn->query($check_columns_query);
            $columns = [];
            
            if ($columns_result) {
                while ($column = $columns_result->fetch_assoc()) {
                    $columns[] = $column['Field'];
                }
            }
            
            // Determine which field to use
            $field_to_use = 'rule_content';
            if (!in_array('rule_content', $columns)) {
                if (in_array('description', $columns)) {
                    $field_to_use = 'description';
                } else if (in_array('title', $columns)) {
                    $field_to_use = 'title';
                }
            }
            
            // Insert the rule
            $query = "INSERT INTO company_rules ($field_to_use, created_by) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            
            if ($stmt) {
                $stmt->bind_param("si", $rule_content, $manager_id);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Rule added successfully";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error adding rule: " . $conn->error;
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Error preparing statement: " . $conn->error;
                $_SESSION['message_type'] = "danger";
            }
        } elseif ($_POST['action'] === 'update' && isset($_POST['rule_id'])) {
            // Update existing rule
            $rule_id = $_POST['rule_id'];
            $rule_content = $_POST['rule_content'];
            
            // Check if the table has the correct structure
            $check_columns_query = "SHOW COLUMNS FROM company_rules";
            $columns_result = $conn->query($check_columns_query);
            $columns = [];
            
            if ($columns_result) {
                while ($column = $columns_result->fetch_assoc()) {
                    $columns[] = $column['Field'];
                }
            }
            
            // Determine which field to use
            $field_to_use = 'rule_content';
            if (!in_array('rule_content', $columns)) {
                if (in_array('description', $columns)) {
                    $field_to_use = 'description';
                } else if (in_array('title', $columns)) {
                    $field_to_use = 'title';
                }
            }
            
            // Update the rule
            $query = "UPDATE company_rules SET $field_to_use = ? WHERE rule_id = ?";
            $stmt = $conn->prepare($query);
            
            if ($stmt) {
                $stmt->bind_param("si", $rule_content, $rule_id);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Rule updated successfully";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error updating rule: " . $conn->error;
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Error preparing statement: " . $conn->error;
                $_SESSION['message_type'] = "danger";
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['rule_id'])) {
            // Delete rule
            $rule_id = $_POST['rule_id'];
            
            $query = "DELETE FROM company_rules WHERE rule_id = ?";
            $stmt = $conn->prepare($query);
            
            if ($stmt) {
                $stmt->bind_param("i", $rule_id);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Rule deleted successfully";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error deleting rule: " . $conn->error;
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Error preparing statement: " . $conn->error;
                $_SESSION['message_type'] = "danger";
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: company_rules.php");
    exit();
}

// Get rule for editing if rule_id is provided
$edit_rule = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $rule_id = $_GET['edit'];
    $query = "SELECT * FROM company_rules WHERE rule_id = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("i", $rule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $edit_rule = $result->fetch_assoc();
        }
    }
}

// Check the table structure to determine which field to use
$check_columns_query = "SHOW COLUMNS FROM company_rules";
$columns_result = $conn->query($check_columns_query);
$columns = [];
$content_field = 'rule_content'; // Default to new structure

if ($columns_result) {
    while ($column = $columns_result->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    
    if (!in_array('rule_content', $columns)) {
        if (in_array('description', $columns)) {
            $content_field = 'description';
        } else if (in_array('title', $columns)) {
            $content_field = 'title';
        }
    }
} else {
    // If the query failed, we'll use a default field
    $content_field = 'rule_content';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Rules - Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .rule-card {
            transition: transform 0.3s;
        }
        .rule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
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
                        <h1 class="page-title">Company Rules</h1>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addRuleModal">
                            <i class="fas fa-plus"></i> Add New Rule
                        </button>
                    </div>

                    <!-- Rules List -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Company Rules List</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get all rules
                            $query = "SELECT * FROM company_rules ORDER BY created_at DESC";
                            $result = $conn->query($query);
                            
                            if ($result && $result->num_rows > 0) {
                                echo "<div class='row'>";
                                
                                while ($rule = $result->fetch_assoc()) {
                                    echo "<div class='col-md-6 col-lg-4 mb-4'>";
                                    echo "<div class='card rule-card h-100'>";
                                    echo "<div class='card-body'>";
                                    echo "<p class='card-text'>" . nl2br(htmlspecialchars($rule[$content_field])) . "</p>";
                                    echo "</div>";
                                    echo "<div class='card-footer'>";
                                    echo "<div class='d-flex justify-content-between'>";
                                    echo "<small class='text-muted'>Created: " . date('M d, Y', strtotime($rule['created_at'])) . "</small>";
                                    echo "<div>";
                                    echo "<a href='?edit=" . $rule['rule_id'] . "' class='btn btn-sm btn-info mr-2'><i class='fas fa-edit'></i></a>";
                                    echo "<button type='button' class='btn btn-sm btn-danger delete-rule' data-rule-id='" . $rule['rule_id'] . "'><i class='fas fa-trash'></i></button>";
                                    echo "</div>";
                                    echo "</div>";
                                    echo "</div>";
                                    echo "</div>";
                                    echo "</div>";
                                }
                                
                                echo "</div>";
                            } else {
                                echo "<div class='alert alert-info'>No company rules found. Add your first rule using the button above.</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Add Rule Modal -->
    <div class="modal fade" id="addRuleModal" tabindex="-1" role="dialog" aria-labelledby="addRuleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRuleModalLabel">Add New Company Rule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="company_rules.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label for="rule_content">Rule Content</label>
                            <textarea class="form-control" id="rule_content" name="rule_content" rows="10" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Rule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Rule Modal -->
    <?php if ($edit_rule): ?>
    <div class="modal fade" id="editRuleModal" tabindex="-1" role="dialog" aria-labelledby="editRuleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRuleModalLabel">Edit Company Rule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="company_rules.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="rule_id" value="<?php echo $edit_rule['rule_id']; ?>">
                        
                        <div class="form-group">
                            <label for="rule_content">Rule Content</label>
                            <textarea class="form-control" id="rule_content" name="rule_content" rows="10" required><?php echo htmlspecialchars($edit_rule[$content_field]); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Rule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Delete Rule Modal -->
    <div class="modal fade" id="deleteRuleModal" tabindex="-1" role="dialog" aria-labelledby="deleteRuleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteRuleModalLabel">Delete Company Rule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this rule?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form action="company_rules.php" method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="rule_id" id="deleteRuleId">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="../style/js/jquery.min.js"></script>
    <script src="../style/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../style/js/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../style/js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="../style/js/jquery.dataTables.min.js"></script>
    <script src="../style/js/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script>
        $(document).ready(function() {
            // Show edit modal if edit_rule is set
            <?php if ($edit_rule): ?>
            $('#editRuleModal').modal('show');
            <?php endif; ?>
            
            // Handle delete rule button click
            $('.delete-rule').on('click', function() {
                var ruleId = $(this).data('rule-id');
                $('#deleteRuleId').val(ruleId);
                $('#deleteRuleModal').modal('show');
            });
            
            // Auto-hide alert after 5 seconds
            setTimeout(function() {
                $('#messageAlert').alert('close');
            }, 5000);
        });
    </script>
</body>
</html> 