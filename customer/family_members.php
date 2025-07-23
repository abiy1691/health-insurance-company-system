<?php
session_start();
include '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get customer's family members
$customer_id = $_SESSION['customer_id'];
$query = "SELECT * FROM Family_Member WHERE customer_id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $customer_id);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}
$result = $stmt->get_result();
$family_members = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family Members - Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .family-card {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            color: white;
            margin-bottom: 20px;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .add-member-btn {
            background: #1cc88a;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .add-member-btn:hover {
            background: #17a673;
            transform: translateY(-2px);
        }
        .table thead th {
            background: #f8f9fc;
            color: #4e73df;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }
        .table tbody tr:hover {
            background: #f8f9fc;
        }
        .member-number {
            font-weight: 600;
            color: #4e73df;
        }
        .page-title {
            color: #4e73df;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .total-members {
            font-size: 1.2rem;
            color: white;
            font-weight: 600;
        }
        .gender-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 5px;
        }
        .male-icon {
            background: #4e73df;
            color: white;
        }
        .female-icon {
            background: #e83e8c;
            color: white;
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
                        <h1 class="page-title">Family Members</h1>
                        <button class="add-member-btn" data-toggle="modal" data-target="#addMemberModal">
                            <i class="fas fa-plus"></i> Add Family Member
                        </button>
                    </div>

                    <div class="family-card p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="total-members">
                                    <i class="fas fa-users mr-2"></i>
                                    Total Family Members: <?php echo count($family_members); ?>
                                </h3>
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Full Name</th>
                                        <th>Gender</th>
                                        <th>Age</th>
                                        <th>Relation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($family_members)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No family members added yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($family_members as $index => $member): ?>
                                            <tr>
                                                <td class="member-number"><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($member['name']); ?></td>
                                                <td>
                                                    <?php if ($member['gender'] == 'Male'): ?>
                                                        <span class="gender-icon male-icon">
                                                            <i class="fas fa-mars"></i>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="gender-icon female-icon">
                                                            <i class="fas fa-venus"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($member['gender']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($member['age']); ?></td>
                                                <td><?php echo htmlspecialchars($member['relation']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1" role="dialog" aria-labelledby="addMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMemberModalLabel">Add Family Member</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="add_family_member.php" method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" class="form-control" id="age" name="age" min="0" max="120" required>
                        </div>
                        <div class="form-group">
                            <label for="relation">Relation</label>
                            <select class="form-control" id="relation" name="relation" required>
                                <option value="">Select Relation</option>
                                <option value="Husband">Husband</option>
                                <option value="Wife">Wife</option>
                                <option value="Son">Son</option>
                                <option value="Daughter">Daughter</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Member</button>
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
        });
    </script>
</body>
</html> 