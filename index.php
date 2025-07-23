<?php
session_start();
include 'includes/config.php';

// Fetch all active policies
$policies_query = "SELECT * FROM Policies ORDER BY policy_id DESC";
$policies_result = $conn->query($policies_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare Pro - Affordable Health Insurance Solutions</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #10b981;
            --accent: #f59e0b;
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-alt: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--background);
        }

        /* Navigation - Disappearing on scroll */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px);
            box-shadow: 0 2px 20px var(--shadow);
            padding: 0.75rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .navbar.hidden {
            transform: translateY(-100%);
            opacity: 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            color: var(--primary) !important;
        }

        .navbar-nav .nav-link {
            font-weight: 500;
            color: var(--text-primary) !important;
            margin: 0 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: var(--primary) !important;
            background-color: rgba(37, 99, 235, 0.1);
        }

        /* Hero Section - Reduced Height */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem 0; /* Much smaller padding */
            min-height: 40vh; /* Reduced from 60vh to 40vh */
            display: flex;
            align-items: center;
            margin-top: 70px; /* Account for fixed navbar */
        }

        .hero h1 {
            font-size: 2.2rem; /* Reduced font size */
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .hero p {
            font-size: 1.1rem; /* Reduced font size */
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .hero-image {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        .hero-stats {
            margin-top: 2rem;
        }

        .hero-stats h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent);
        }

        /* Buttons - Improved Design */
        .btn {
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0.25rem;
            border: none;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--secondary), #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            color: white;
        }

        .btn-outline-light {
            border: 2px solid rgba(255, 255, 255, 0.8);
            color: white;
            background: transparent;
        }

        .btn-outline-light:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-2px);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        /* Cards with comfortable colors */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px var(--shadow);
            transition: all 0.3s ease;
            height: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
            border-radius: 12px 12px 0 0;
        }

        /* Feature Cards */
        .feature-card {
            text-align: center;
            padding: 2rem;
            background: var(--surface);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }

        /* Policy Cards */
        .policy-card {
            position: relative;
            overflow: hidden;
            background: var(--surface);
        }

        .policy-card .badge {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 2;
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .price-tag {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary);
            margin: 1rem 0;
        }

        .price-tag small {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 400;
        }

        /* Sections with comfortable colors */
        .section {
            padding: 4rem 0;
        }

        .section.bg-light {
            background-color: var(--surface-alt);
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        /* About Section */
        .about-img {
            border-radius: 12px;
            box-shadow: 0 15px 35px var(--shadow);
        }

        /* Contact Section */
        .contact-info {
            background: var(--surface);
            padding: 2rem;
            border-radius: 12px;
            height: 100%;
            box-shadow: 0 4px 20px var(--shadow);
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--surface-alt);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .contact-item:hover {
            background: rgba(37, 99, 235, 0.1);
        }

        .contact-item i {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }

        /* Form Controls */
        .form-control, .form-select {
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            background: var(--surface);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: var(--surface);
        }

        /* Footer */
        .footer {
            background: var(--text-primary);
            color: white;
            padding: 3rem 0 1rem;
        }

        .footer h5 {
            color: white;
            margin-bottom: 1rem;
        }

        .footer a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: white;
        }

        /* Scroll to top */
        .scroll-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            cursor: pointer;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }

        .scroll-top.show {
            opacity: 1;
        }

        .scroll-top:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
        }

        /* Alert styling */
        .alert {
            border: none;
            border-radius: 8px;
            background: var(--surface-alt);
            color: var(--text-secondary);
            border-left: 4px solid var(--primary);
        }

        /* List styling */
        .list-unstyled li {
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero {
                min-height: 35vh;
                padding: 1.5rem 0;
            }
            
            .hero h1 {
                font-size: 1.8rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .section-title h2 {
                font-size: 1.8rem;
            }
            
            .btn-lg {
                padding: 0.875rem 1.5rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <i class="fas fa-heartbeat me-2"></i>MediCare Pro
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#plans">Plans</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                
                <div class="d-flex ms-3">
                    <a href="login.php" class="btn btn-outline-primary btn-sm me-2">Login</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Get Started</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section - Reduced Height -->
    <section id="home" class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1>Protect Your Family's Health Future</h1>
                    <p class="lead">Comprehensive health insurance plans designed for Ethiopian families. Quality healthcare coverage at affordable prices with nationwide hospital network access.</p>
                    
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="register.php" class="btn btn-success btn-lg">
                            <i class="fas fa-shield-alt"></i>Get Coverage Now
                        </a>
                        <a href="#plans" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-eye"></i>View Plans
                        </a>
                    </div>
                    
                    <div class="row text-center hero-stats">
                        <div class="col-4">
                            <h3 class="mb-0">25K+</h3>
                            <small>Happy Families</small>
                        </div>
                        <div class="col-4">
                            <h3 class="mb-0">500+</h3>
                            <small>Partner Hospitals</small>
                        </div>
                        <div class="col-4">
                            <h3 class="mb-0">99%</h3>
                            <small>Claim Success</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1609220136736-443140cffec6?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Happy family with health insurance" class="hero-image img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section bg-light">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose MediCare Pro?</h2>
                <p>We provide comprehensive healthcare solutions with exceptional service and nationwide coverage</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <h4>500+ Partner Hospitals</h4>
                        <p>Access to premium healthcare facilities across Ethiopia with cashless treatment options and quality medical care.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4>24/7 Emergency Support</h4>
                        <p>Round-the-clock emergency assistance and immediate claim processing for urgent medical situations.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4>Digital Health App</h4>
                        <p>Manage your insurance, book appointments, and track claims through our user-friendly mobile application.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Plans Section -->
    <section id="plans" class="section">
        <div class="container">
            <div class="section-title">
                <h2>Choose Your Perfect Plan</h2>
                <p>Flexible insurance plans designed to fit your budget and healthcare needs</p>
            </div>
            
            <div class="row g-4">
                <?php
                if ($policies_result && $policies_result->num_rows > 0) {
                    $plan_images = [
                        'https://images.unsplash.com/photo-1559757175-0eb30cd8c063?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', // Individual health
                        'https://images.unsplash.com/photo-1609220136736-443140cffec6?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', // Family health
                        'https://images.unsplash.com/photo-1582750433449-648ed127bb54?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', // Premium care
                        'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', // Senior care
                        'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80', // Student health
                        'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'  // Corporate group
                    ];
                    
                    $badge_colors = ['primary', 'success', 'info', 'warning', 'secondary', 'danger'];
                    $image_index = 0;
                    
                    while ($policy = $policies_result->fetch_assoc()) {
                        $current_image = $plan_images[$image_index % count($plan_images)];
                        $badge_color = $badge_colors[$image_index % count($badge_colors)];
                        $image_index++;
                        ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card policy-card">
                                <span class="badge bg-<?php echo $badge_color; ?>"><?php echo htmlspecialchars($policy['policy_type']); ?></span>
                                <img src="<?php echo $current_image; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($policy['policy_name']); ?>">
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($policy['policy_name']); ?></h5>
                                    
                                    <div class="price-tag">
                                        ETB <?php echo number_format($policy['price'], 0); ?>
                                        <small>/<?php echo $policy['payment_interval']; ?></small>
                                    </div>
                                    
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo $policy['policy_term']; ?> Years Coverage</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo $policy['profit_rate']; ?>% Annual Returns</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Cashless Treatment</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Emergency Ambulance</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Pre & Post Hospitalization</li>
                                    </ul>
                                    
                                    <div class="alert alert-light">
                                        <small><?php echo htmlspecialchars($policy['description']); ?></small>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="register.php" class="btn btn-primary">
                                            <i class="fas fa-shopping-cart"></i>Choose This Plan
                                        </a>
                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#planModal<?php echo $policy['policy_id']; ?>">
                                            <i class="fas fa-info-circle"></i>More Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Plan Details Modal -->
                        <div class="modal fade" id="planModal<?php echo $policy['policy_id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php echo htmlspecialchars($policy['policy_name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <img src="<?php echo $current_image; ?>" class="img-fluid mb-3 rounded" alt="Plan Image">
                                        <p><strong>Coverage Type:</strong> <?php echo htmlspecialchars($policy['policy_type']); ?></p>
                                        <p><strong>Term:</strong> <?php echo $policy['policy_term']; ?> Years</p>
                                        <p><strong>Premium:</strong> ETB <?php echo number_format($policy['price'], 2); ?> per <?php echo $policy['payment_interval']; ?></p>
                                        <p><strong>Returns:</strong> <?php echo $policy['profit_rate']; ?>% annually</p>
                                        <p><strong>Description:</strong> <?php echo htmlspecialchars($policy['description']); ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                        <a href="register.php" class="btn btn-primary">Get This Plan</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="col-12 text-center"><div class="alert alert-info">No insurance plans available at the moment.</div></div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2>Trusted Healthcare Partner Since 2010</h2>
                    <p class="lead">MediCare Pro has been serving Ethiopian families with reliable health insurance solutions for over a decade.</p>
                    
                    <p>We understand the importance of accessible healthcare and work tirelessly to provide comprehensive coverage that fits your budget. Our extensive network of partner hospitals ensures you receive quality care when you need it most.</p>
                    
                    <div class="row mt-4">
                        <div class="col-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-award text-primary me-3"></i>
                                <span>Award-Winning Service</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users text-primary me-3"></i>
                                <span>25,000+ Satisfied Customers</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-hospital text-primary me-3"></i>
                                <span>500+ Partner Hospitals</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock text-primary me-3"></i>
                                <span>24/7 Customer Support</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="#contact" class="btn btn-primary me-3">
                            <i class="fas fa-phone"></i>Contact Us
                        </a>
                        <a href="#plans" class="btn btn-outline-primary">
                            <i class="fas fa-eye"></i>View Plans
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1582750433449-648ed127bb54?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" 
                         alt="Medical team providing healthcare" class="about-img img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section">
        <div class="container">
            <div class="section-title">
                <h2>Get In Touch</h2>
                <p>Ready to secure your health future? Contact our experts today</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="contact-info">
                        <h4 class="mb-4">Contact Information</h4>
                        
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Address</strong><br>
                                Bole Road, Addis Ababa, Ethiopia
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Phone</strong><br>
                                +251 911 123 456
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email</strong><br>
                                info@medicarepro.com
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Business Hours</strong><br>
                                Mon - Fri: 8:00 AM - 6:00 PM<br>
                                Sat: 9:00 AM - 2:00 PM
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Send Us a Message</h4>
                            
                            <form id="contactForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Subject</label>
                                        <select class="form-select" required>
                                            <option value="">Choose...</option>
                                            <option value="general">General Inquiry</option>
                                            <option value="plans">Insurance Plans</option>
                                            <option value="claims">Claims Support</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Message</label>
                                        <textarea class="form-control" rows="4" required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-paper-plane"></i>Send Message
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5><i class="fas fa-heartbeat me-2"></i>MediCare Pro</h5>
                    <p>Your trusted partner in health insurance. We provide comprehensive coverage and exceptional service to protect what matters most - your health and your family's wellbeing.</p>
                    
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-outline-light btn-sm"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#plans">Plans</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3">
                    <h5>Services</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Individual Plans</a></li>
                        <li><a href="#">Family Coverage</a></li>
                        <li><a href="#">Corporate Insurance</a></li>
                        <li><a href="#">Emergency Services</a></li>
                        <li><a href="#">Claims Processing</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i>Bole Road, Addis Ababa</li>
                        <li><i class="fas fa-phone me-2"></i>+251 911 123 456</li>
                        <li><i class="fas fa-envelope me-2"></i>info@medicarepro.com</li>
                        <li><i class="fas fa-clock me-2"></i>Mon-Fri: 8AM-6PM</li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; 2025 MediCare Pro. All rights reserved. | Privacy Policy | Terms of Service</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top -->
    <button class="scroll-top" id="scrollTop">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let lastScrollTop = 0;
        const navbar = document.getElementById('mainNavbar');
        const scrollTop = document.getElementById('scrollTop');

        // Navbar hide/show on scroll
        window.addEventListener('scroll', function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Hide navbar when scrolling down, show when scrolling up
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                navbar.classList.add('hidden');
            } else {
                navbar.classList.remove('hidden');
            }
            lastScrollTop = scrollTop;
            
            // Show/hide scroll to top button
            if (scrollTop > 300) {
                document.getElementById('scrollTop').classList.add('show');
            } else {
                document.getElementById('scrollTop').classList.remove('show');
            }
            
            // Update active nav link
            const sections = document.querySelectorAll('section');
            const navLinks = document.querySelectorAll('.nav-link');
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 150;
                const sectionHeight = section.clientHeight;
                const scroll = window.scrollY;
                
                if (scroll >= sectionTop && scroll < sectionTop + sectionHeight) {
                    const currentId = section.getAttribute('id');
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === `#${currentId}`) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Scroll to top
        document.getElementById('scrollTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Contact form
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            
            setTimeout(() => {
                alert('Thank you for your message! We will contact you within 24 hours.');
                this.reset();
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 2000);
        });
    </script>
</body>
</html>