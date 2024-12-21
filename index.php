<?php
require_once 'config/config.php';

// Only check for dashboard redirect if not explicitly showing login modal
if (!isset($_GET['action']) || $_GET['action'] !== 'login') {
    checkDashboardRedirect();
}

$showLoginModal = isset($_GET['action']) && $_GET['action'] === 'login';

if (isLoggedIn()) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            redirect('/admin/dashboard.php');
            break;
        case 'staff':
            redirect('/staff/dashboard.php');
            break;
        case 'member':
            redirect('/member/dashboard.php');
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            redirect('/' . $user['role'] . '/dashboard.php');
        }
    }
    
    $error = "Invalid username or password";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VikingsFit- Your Fitness Journey Starts Here</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/images/gym-hero.jpg');
            background-size: cover;
            background-position: center;
            height: 80vh;
            display: flex;
            align-items: center;
            color: white;
        }
        .feature-icon {
            font-size: 4rem;
            color: #1565c0;
        }
        .plan-card {
            height: 100%;
        }
        .testimonial {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            height: 100%;
        }
        .section {
            padding: 40px 0;
        }
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        
        body {
            padding-top: 64px; /* Height of the navbar */
        }
        
        @media only screen and (max-width: 600px) {
            body {
                padding-top: 56px; /* Height of mobile navbar */
            }
        }

        /* Smooth scroll padding for sections */
        section {
            scroll-margin-top: 64px;
        }
        
        @media only screen and (max-width: 600px) {
            section {
                scroll-margin-top: 56px;
            }
        }

        /* Add active state for nav links */
        .nav-active {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .card .card-content .card-title {
            font-weight: bold;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        .card h4 {
            font-size: 2.5rem;
            margin: 10px 0;
            font-weight: bold;
        }
        .card .grey-text {
            font-size: 1.1rem;
        }
        .card .card-action {
            padding: 20px;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        .card .btn-large {
            margin: 0;
            width: 80%;
        }
        .card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .card .card-content {
            flex-grow: 1;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="blue darken-3">
        <div class="nav-wrapper container">
            <a href="<?php echo SITE_URL; ?>/index.php" class="brand-logo">VikingsFit</a>
            <a href="#" data-target="mobile-nav" class="sidenav-trigger"><i class="material-icons">menu</i></a>
            <ul id="nav-mobile" class="right hide-on-med-and-down">
                <li><a href="#features">Features</a></li>
                <li><a href="#plans">Plans</a></li>
                <li><a href="#testimonials">Testimonials</a></li>
                <li><a href="#!" class="btn white black-text modal-trigger" data-target="loginModal">Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Mobile Navigation -->
    <ul class="sidenav" id="mobile-nav">
        <li><a href="#features">Features</a></li>
        <li><a href="#plans">Plans</a></li>
        <li><a href="#testimonials">Testimonials</a></li>
        <li><a href="#!" class="modal-trigger" data-target="loginModal">Login</a></li>
    </ul>

    <!-- Hero Section -->
    <div class="hero">
        <div class="container">
            <div class="row">
                <div class="col s12 m8">
                    <h1>Transform Your Life Today</h1>
                    <p class="flow-text">Join Vikings Fitness and start your fitness journey with expert guidance and state-of-the-art equipment.</p>
                    <a href="<?php echo SITE_URL; ?>/member/register.php" class="btn-large blue darken-3 waves-effect waves-light">Join Now</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <section id="features" class="section">
        <div class="container">
            <h2 class="center-align">Why Choose Us</h2>
            <div class="row">
                <div class="col s12 m4 center-align">
                    <i class="material-icons feature-icon">fitness_center</i>
                    <h5>Modern Equipment</h5>
                    <p>State-of-the-art fitness equipment for all your workout needs</p>
                </div>
                <div class="col s12 m4 center-align">
                    <i class="material-icons feature-icon">group</i>
                    <h5>Expert Trainers</h5>
                    <p>Professional trainers to guide you through your fitness journey</p>
                </div>
                <div class="col s12 m4 center-align">
                    <i class="material-icons feature-icon">schedule</i>
                    <h5>Flexible Hours</h5>
                    <p>Open 24/7 to fit your busy schedule</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Membership Plans Section -->
    <section id="plans" class="section">
        <div class="container">
            <div class="row">
                <div class="col s12 center">
                    <h3>Membership Plans</h3>
                    <p class="flow-text grey-text">Choose the plan that fits your needs</p>
                </div>
            </div>
            <div class="row">
                <?php
                // Fetch active plans
                $plans_query = "SELECT * FROM plans WHERE deleted_at IS NULL ORDER BY price ASC";
                $plans_result = $conn->query($plans_query);

                if ($plans_result && $plans_result->num_rows > 0):
                    while($plan = $plans_result->fetch_assoc()):
                ?>
                    <div class="col s12 m6 l4">
                        <div class="card hoverable">
                            <div class="card-content">
                                <span class="card-title center"><?php echo htmlspecialchars($plan['name']); ?></span>
                                <div class="center" style="padding: 20px 0;">
                                    <h4 class="blue-text">₱<?php echo number_format($plan['price'], 2); ?></h4>
                                    <p class="grey-text"><?php echo $plan['duration']; ?> Days</p>
                                </div>
                                <div style="min-height: 120px;">
                                    <?php echo nl2br(htmlspecialchars($plan['description'])); ?>
                                </div>
                            </div>
                            <div class="card-action center">
                                <?php if (isLoggedIn()): ?>
                                    <?php if (hasRole('member')): ?>
                                        <a href="member/subscribe.php?plan=<?php echo $plan['id']; ?>" class="btn-large waves-effect waves-light blue">
                                            Subscribe Now
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="member/register.php?plan=<?php echo $plan['id']; ?>" class="btn-large waves-effect waves-light blue">
                                        Join Now
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="col s12">
                        <p class="center-align grey-text">No membership plans available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="section">
        <div class="container">
            <h2 class="center-align">What Our Members Say</h2>
            <div class="row">
                <div class="col s12 m4">
                    <div class="testimonial">
                        <i class="material-icons">format_quote</i>
                        <p>"Best gym I've ever been to! The trainers are amazing and the equipment is top-notch."</p>
                        <p><strong>- John Doe</strong></p>
                    </div>
                </div>
                <div class="col s12 m4">
                    <div class="testimonial">
                        <i class="material-icons">format_quote</i>
                        <p>"I've achieved my fitness goals thanks to the amazing support from the staff."</p>
                        <p><strong>- Jane Smith</strong></p>
                    </div>
                </div>
                <div class="col s12 m4">
                    <div class="testimonial">
                        <i class="material-icons">format_quote</i>
                        <p>"Flexible hours and great atmosphere. Couldn't ask for more!"</p>
                        <p><strong>- Mike Johnson</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <h4 class="center-align blue-text text-darken-3">Welcome Back!</h4>
            <div id="loginError" class="red-text center-align" style="display: none;">
                <i class="material-icons tiny">error</i>
                <span id="loginErrorText"></span>
            </div>
            <form id="loginForm" class="row">
                <div class="input-field col s12">
                    <select name="role" id="role" required>
                        <option value="" disabled selected>Choose role</option>
                        <option value="member">Member</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                    <label>Login As</label>
                </div>
                <div class="input-field col s12">
                    <i class="material-icons prefix">person</i>
                    <input type="text" id="login_username" name="username" required>
                    <label for="login_username">Username</label>
                </div>
                <div class="input-field col s12">
                    <i class="material-icons prefix">lock</i>
                    <input type="password" id="login_password" name="password" required>
                    <label for="login_password">Password</label>
                </div>
                <div class="col s12 center-align">
                    <button class="btn-large waves-effect waves-light blue darken-3" type="submit">
                        Login
                        <i class="material-icons right">send</i>
                    </button>
                </div>
            </form>
            <div class="center-align" style="margin-top: 20px;">
                <p>New member? <a href="member/register.php">Register here</a></p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="page-footer blue darken-3">
        <div class="container">
            <div class="row">
                <div class="col s12 m6">
                    <h5 class="white-text">VikingsFit </h5>
                    <p class="grey-text text-lighten-4">Your journey to a healthier lifestyle starts here.</p>
                </div>
                <div class="col s12 m4 offset-m2">
                    <h5 class="white-text">Quick Links</h5>
                    <ul>
                        <li><a class="grey-text text-lighten-3" href="#features">Features</a></li>
                        <li><a class="grey-text text-lighten-3" href="#plans">Plans</a></li>
                        <li><a class="grey-text text-lighten-3 modal-trigger" href="#!" data-target="loginModal">Login</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-copyright">
            <div class="container">
                © 2024 Vikings Fitness Gym
                <a class="grey-text text-lighten-4 right" href="#!">Terms of Service</a>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        const SITE_URL = '<?php echo SITE_URL; ?>';

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all modals
            var modals = document.querySelectorAll('.modal');
            var modalInstances = M.Modal.init(modals);

            // Open login modal if requested
            <?php if ($showLoginModal): ?>
                var loginModal = document.getElementById('loginModal');
                var instance = M.Modal.getInstance(loginModal);
                instance.open();
            <?php endif; ?>

            // Initialize mobile navigation
            var elems = document.querySelectorAll('.sidenav');
            var instances = M.Sidenav.init(elems);

            // Initialize select
            var selects = document.querySelectorAll('select');
            var selectInstances = M.FormSelect.init(selects);

            // Handle Login Form
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="material-icons right">hourglass_empty</i>Logging in...';
                submitBtn.disabled = true;
                
                // Hide any previous error
                document.getElementById('loginError').style.display = 'none';

                fetch(SITE_URL + '/ajax/login.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        M.toast({html: data.message || 'Login successful! Redirecting...', classes: 'green'});
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        document.getElementById('loginErrorText').textContent = data.message;
                        document.getElementById('loginError').style.display = 'block';
                        
                        // If account was removed, show register button
                        if (data.show_register) {
                            const registerBtn = document.createElement('a');
                            registerBtn.href = SITE_URL + '/member/register.php';
                            registerBtn.className = 'btn blue darken-3 waves-effect waves-light';
                            registerBtn.style.marginTop = '10px';
                            registerBtn.innerHTML = 'Create New Account';
                            document.getElementById('loginError').appendChild(registerBtn);
                        }
                        
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('loginErrorText').textContent = 'An error occurred. Please try again.';
                    document.getElementById('loginError').style.display = 'block';
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    if (this.getAttribute('href').length > 1) {
                        e.preventDefault();
                        const targetId = this.getAttribute('href');
                        const targetElement = document.querySelector(targetId);
                        
                        if (targetElement) {
                            targetElement.scrollIntoView({
                                behavior: 'smooth'
                            });
                            
                            // Update active state in navigation
                            document.querySelectorAll('.nav-active').forEach(el => el.classList.remove('nav-active'));
                            this.parentElement.classList.add('nav-active');
                        }
                    }
                });
            });

            // Intersection Observer for section highlighting
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('nav ul li a[href^="#"]');

            const observerOptions = {
                rootMargin: '-30% 0px -70% 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.getAttribute('id');
                        navLinks.forEach(link => {
                            if (link.getAttribute('href') === `#${id}`) {
                                document.querySelectorAll('.nav-active').forEach(el => el.classList.remove('nav-active'));
                                link.parentElement.classList.add('nav-active');
                            }
                        });
                    }
                });
            }, observerOptions);

            sections.forEach(section => {
                observer.observe(section);
            });

            // Close mobile nav when clicking a link
            document.querySelectorAll('.sidenav a').forEach(link => {
                link.addEventListener('click', () => {
                    const sidenav = document.querySelector('.sidenav');
                    const instance = M.Sidenav.getInstance(sidenav);
                    instance.close();
                });
            });

            // Get all links that have hash (#) in them
            const links = document.querySelectorAll('a[href^="#"]');
            
            links.forEach(link => {
                if (link.getAttribute('href').length > 1) { // Ignore links that are just "#"
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const targetId = this.getAttribute('href');
                        const targetElement = document.querySelector(targetId);
                        
                        if (targetElement) {
                            targetElement.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                            
                            // Update URL without scrolling
                            history.pushState(null, null, targetId);
                        }
                    });
                }
            });
        });
    </script>
</body>
</html> 