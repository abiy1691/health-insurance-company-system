<!-- Required CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

<!-- Required JavaScript -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- Custom styles for this template-->
<link href="../css/sb-admin-2.min.css" rel="stylesheet">

<!-- Custom fonts for this template-->
<link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
<link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

<style>
.img-profile {
    width: 32px;
    height: 32px;
    object-fit: cover;
    border: 2px solid #4e73df;
}

.dropdown-menu {
    position: absolute;
    transform: none !important;
    top: 100% !important;
    right: 0 !important;
    left: auto !important;
}
</style>

<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">

        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= $_SESSION['manager_name'] ?? 'Manager' ?></span>
                <img class="img-profile rounded-circle" src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['manager_name'] ?? 'Manager') ?>&background=4e73df&color=fff" alt="Profile">
            </a>
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="userDropdown">
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#profileModal">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-primary"></i>
                    Update Profile
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="../logout.php">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-danger"></i>
                    Logout
                </a>
            </div>
        </li>

    </ul>

</nav>
<!-- End of Topbar -->

<!-- Profile Update Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">Update Profile</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form id="profileForm" action="update_profile.php" method="POST">
                <div class="modal-body" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                    <div id="profileAlert" class="alert d-none" style="margin: 0; text-align: center; font-size: 18px; padding: 20px; width: 100%;"></div>
                    <div id="profileFormContent">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= $_SESSION['manager_name'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= $_SESSION['manager_username'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= $_SESSION['manager_email'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="password">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="updateButton">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger" href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();
        
        // Immediately hide the form and show success message
        $('#profileFormContent').hide();
        $('.modal-footer').hide();
        $('#profileAlert')
            .removeClass('d-none alert-danger')
            .addClass('alert-success')
            .html('<i class="fas fa-check-circle" style="font-size: 24px; margin-right: 10px;"></i>Profile updated successfully');
        
        // Send the update request
        $.ajax({
            type: 'POST',
            url: 'update_profile.php',
            data: $(this).serialize(),
            success: function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    // Update the displayed name if changed
                    if (result.name) {
                        $('.text-gray-600.small').text(result.name);
                        $('.img-profile').attr('src', 'https://ui-avatars.com/api/?name=' + encodeURIComponent(result.name) + '&background=4e73df&color=fff');
                    }
                    
                    // Hide modal after 3 seconds
                    setTimeout(function() {
                        $('#profileModal').modal('hide');
                        // Reset form for next time
                        $('#profileFormContent').show();
                        $('.modal-footer').show();
                        $('#profileAlert').addClass('d-none');
                    }, 3000);
                } else {
                    // Show error message
                    $('#profileAlert')
                        .removeClass('alert-success')
                        .addClass('alert-danger')
                        .html('<i class="fas fa-exclamation-circle" style="font-size: 24px; margin-right: 10px;"></i>' + result.message);
                    // Show form content again
                    $('#profileFormContent').show();
                    $('.modal-footer').show();
                }
            },
            error: function() {
                // Show error message
                $('#profileAlert')
                    .removeClass('alert-success')
                    .addClass('alert-danger')
                    .html('<i class="fas fa-exclamation-circle" style="font-size: 24px; margin-right: 10px;"></i>An error occurred while updating your profile. Please try again.');
                // Show form content again
                $('#profileFormContent').show();
                $('.modal-footer').show();
            }
        });
    });

    // Reset form when modal is closed
    $('#profileModal').on('hidden.bs.modal', function () {
        $('#profileFormContent').show();
        $('.modal-footer').show();
        $('#profileAlert').addClass('d-none');
        $('#password').val('');
    });
});
</script>