<?php
session_start();
include '../includes/config.php';

// Check if user is logged in as agent
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../login.php");
    exit();
}

$agent_id = $_SESSION['agent_id'];

// Get total customers count
$total_customers_query = "SELECT COUNT(*) as total FROM customers WHERE agent_id = ?";
$stmt = $conn->prepare($total_customers_query);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
$total = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Health Insurance</title>
    <link href="../style/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../style/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .family-modal .modal-dialog {
            max-width: 800px;
        }
        .family-member-row:hover {
            background-color: #f8f9fc;
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
                        <h1 class="page-title">Customers</h1>
                    </div>

                    <!-- Total Customers Card -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Assigned Customers</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $total['total']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Assigned Customers List</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Address</th>
                                            <th>Age</th>
                                            <th>Date Assigned</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Get all assigned customers
                                        $customers_query = "SELECT * FROM customers WHERE agent_id = ? ORDER BY name ASC";
                                        $stmt = $conn->prepare($customers_query);
                                        $stmt->bind_param("i", $agent_id);
                                        $stmt->execute();
                                        $customers_result = $stmt->get_result();

                                        if ($customers_result->num_rows > 0) {
                                            while ($customer = $customers_result->fetch_assoc()) {
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                                    <td><?php echo htmlspecialchars($customer['address']); ?></td>
                                                    <td>
                                                        <?php
                                                        if (isset($customer['age']) && !empty($customer['age'])) {
                                                            echo $customer['age'] . " years";
                                                        } else {
                                                            echo '<span class="text-muted">Not available</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-info btn-sm view-families" 
                                                                data-toggle="modal" data-target="#familyModal" 
                                                                data-customer-id="<?php echo $customer['customer_id']; ?>"
                                                                data-customer-name="<?php echo htmlspecialchars($customer['name']); ?>">
                                                            <i class="fas fa-users"></i> Families
                                                        </button>
                                                        <a href="feedback.php?type=customer&id=<?php echo $customer['customer_id']; ?>" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-comments"></i> Chat
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No customers assigned yet</td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Family Members Modal -->
    <div class="modal fade family-modal" id="familyModal" tabindex="-1" role="dialog" aria-labelledby="familyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="familyModalLabel">Family Members</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="loadingFamilyMembers" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p>Loading family members...</p>
                    </div>
                    <div id="familyMembersList" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Relationship</th>
                                        <th>Date of Birth</th>
                                        <th>Gender</th>
                                    </tr>
                                </thead>
                                <tbody id="familyMembersTableBody">
                                    <!-- Family members will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="noFamilyMembers" style="display: none;">
                        <div class="alert alert-info">
                            No family members found for this customer.
                        </div>
                    </div>
                    <div id="errorLoadingFamilyMembers" style="display: none;">
                        <div class="alert alert-danger">
                            Error loading family members. Please try again.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
            $('#dataTable').DataTable({
                "order": [[0, "asc"]],
                "pageLength": 10
            });
            
            // Handle family members modal
            $('.view-families').on('click', function() {
                var customerId = $(this).data('customer-id');
                var customerName = $(this).data('customer-name');
                
                // Update modal title
                $('#familyModalLabel').text('Family Members - ' + customerName);
                
                // Reset modal state
                $('#loadingFamilyMembers').show();
                $('#familyMembersList').hide();
                $('#noFamilyMembers').hide();
                $('#errorLoadingFamilyMembers').hide();
                
                // Fetch family members via AJAX
                $.ajax({
                    url: 'get_family_members.php',
                    type: 'GET',
                    data: { customer_id: customerId },
                    dataType: 'json',
                    success: function(data) {
                        $('#loadingFamilyMembers').hide();
                        
                        if (data.length > 0) {
                            // Display family members
                            var tableBody = '';
                            $.each(data, function(index, member) {
                                tableBody += '<tr class="family-member-row">';
                                tableBody += '<td>' + (index + 1) + '</td>';
                                tableBody += '<td>' + member.name + '</td>';
                                tableBody += '<td>' + member.relationship + '</td>';
                                tableBody += '<td>' + member.date_of_birth + '</td>';
                                tableBody += '<td>' + member.gender + '</td>';
                                tableBody += '</tr>';
                            });
                            
                            $('#familyMembersTableBody').html(tableBody);
                            $('#familyMembersList').show();
                        } else {
                            // No family members found
                            $('#noFamilyMembers').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching family members:", error);
                        $('#loadingFamilyMembers').hide();
                        $('#errorLoadingFamilyMembers').show();
                    }
                });
            });
        });
    </script>
</body>
</html>
