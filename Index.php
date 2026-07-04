<?php
session_start();
include 'db.php';

// Fetch a few services to display on the homepage (if the services table exists)
$featured_services = [];
try {
    $services_result = $conn->query("SELECT service_name, description, price, duration FROM services ORDER BY RAND() LIMIT 3");
    if ($services_result) {
        while ($row = $services_result->fetch_assoc()) {
            $featured_services[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    // Services table doesn't exist yet — placeholder cards will show instead
    $featured_services = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salon & Spa Booking System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ─── Reset & base ──────────────────────────────── */
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg-color); }

        /* ─── Hero section ──────────────────────────────── */
        .hero {
            background: linear-gradient(135deg, #376bc0 0%, #2b1fae 100%);
            color: white;
            text-align: center;
            padding: 80px 20px 100px;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -40px;
            width: 250px; height: 250px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .hero h1 {
            font-size: 2.8rem;
            font-weight: 700;
            margin: 0 0 16px;
            letter-spacing: -0.5px;
        }
        .hero p {
            font-size: 1.15rem;
            opacity: 0.9;
            max-width: 540px;
            margin: 0 auto 36px;
            line-height: 1.7;
        }
        .hero-buttons {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-primary {
            background: white;
            color: #6c5ce7;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-block;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .btn-outline {
            background: transparent;
            color: white;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            border: 2px solid rgba(255,255,255,0.6);
            transition: background 0.2s, border-color 0.2s;
            display: inline-block;
        }
        .btn-outline:hover {
            background: rgba(255,255,255,0.1);
            border-color: white;
        }

        /* ─── Stats strip ───────────────────────────────── */
        .stats-strip {
            background: white;
            display: flex;
            justify-content: center;
            gap: 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        .stat-item {
            padding: 28px 50px;
            text-align: center;
            border-right: 1px solid #f0f0f0;
        }
        .stat-item:last-child { border-right: none; }
        .stat-num {
            font-size: 2rem;
            font-weight: 700;
            color: #6c5ce7;
            display: block;
        }
        .stat-label {
            font-size: 0.85rem;
            color: #888;
            margin-top: 4px;
        }

        /* ─── Sections ──────────────────────────────────── */
        .section { padding: 70px 20px; max-width: 1100px; margin: 0 auto; }
        .section-title {
            text-align: center;
            font-size: 1.9rem;
            font-weight: 700;
            color: #2d3436;
            margin-bottom: 10px;
        }
        .section-subtitle {
            text-align: center;
            color: #888;
            font-size: 1rem;
            margin-bottom: 48px;
        }

        /* ─── How it works ──────────────────────────────── */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }
        .step-card {
            text-align: center;
            padding: 30px 20px;
        }
        .step-number {
            width: 54px; height: 54px;
            background: #f0edff;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.4rem;
            font-weight: 700;
            color: #6c5ce7;
        }
        .step-card h3 { margin: 0 0 8px; color: #2d3436; font-size: 1.05rem; }
        .step-card p  { color: #888; font-size: 0.9rem; margin: 0; line-height: 1.6; }

        /* ─── Services section ──────────────────────────── */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
        }
        .service-card {
            background: white;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            border: 1px solid #f0f0f0;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 28px rgba(108,92,231,0.14);
            border-color: #d6d0f9;
        }
        .service-card .icon { font-size: 2rem; margin-bottom: 12px; }
        .service-card h3 { margin: 0 0 8px; color: #2d3436; }
        .service-card p  { color: #888; font-size: 0.9rem; margin: 0 0 16px; line-height: 1.6; }
        .service-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 14px;
            border-top: 1px solid #f5f5f5;
        }
        .service-price { font-weight: 700; color: #6c5ce7; font-size: 1.1rem; }
        .service-duration { font-size: 0.82rem; color: #aaa; }

        /* Placeholder service cards (when no DB data) */
        .placeholder-services {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
        }

        /* ─── Role CTA cards ────────────────────────────── */
        .cta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }
        .cta-card {
            background: white;
            border-radius: 16px;
            padding: 36px 28px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            border: 1px solid #f0f0f0;
            text-align: center;
        }
        .cta-card .cta-icon {
            font-size: 2.5rem;
            margin-bottom: 16px;
        }
        .cta-card h3 { margin: 0 0 10px; color: #2d3436; font-size: 1.2rem; }
        .cta-card p  { color: #888; font-size: 0.9rem; margin: 0 0 24px; line-height: 1.6; }
        .cta-card .btn-cta {
            display: inline-block;
            padding: 12px 28px;
            background: #6c5ce7;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s, transform 0.2s;
        }
        .cta-card .btn-cta:hover {
            background: #5849d1;
            transform: translateY(-1px);
        }
        .cta-card .btn-cta.secondary {
            background: #f0edff;
            color: #6c5ce7;
        }
        .cta-card .btn-cta.secondary:hover {
            background: #e0d9ff;
        }

        /* ─── Testimonials ──────────────────────────────── */
        .testimonials-bg { background: #f8f7ff; padding: 70px 20px; }
        .testimonials-inner { max-width: 1100px; margin: 0 auto; }
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }
        .testimonial-card {
            background: white;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.05);
        }
        .stars { color: #f39c12; font-size: 1.1rem; margin-bottom: 12px; }
        .testimonial-text { color: #555; font-size: 0.95rem; line-height: 1.7; margin: 0 0 18px; font-style: italic; }
        .testimonial-author { font-weight: 700; color: #2d3436; font-size: 0.9rem; }
        .testimonial-role   { color: #aaa; font-size: 0.8rem; }

        /* ─── Footer ────────────────────────────────────── */
        footer {
            background: #2d3436;
            color: #ccc;
            padding: 40px 20px;
            text-align: center;
            font-size: 0.9rem;
        }
        footer a { color: #a29bfe; text-decoration: none; }
        footer a:hover { text-decoration: underline; }
        footer .footer-links { margin-bottom: 16px; display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }

        /* ─── Responsive ────────────────────────────────── */
        @media (max-width: 600px) {
            .hero h1 { font-size: 2rem; }
            .stats-strip { flex-wrap: wrap; }
            .stat-item { border-right: none; border-bottom: 1px solid #f0f0f0; padding: 20px 30px; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!-- ─── HERO ─────────────────────────────────────────── -->
    <section class="hero">
        <h1>✨ Your Beauty, Our Priority</h1>
        <p>Book salon and spa services effortlessly. Choose your stylist, pick your slot, and relax — we handle the rest.</p>
        <div class="hero-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'customer'): ?>
                    <a href="booking/book.php" class="btn-primary">Book an Appointment</a>
                    <a href="customer_dashboard.php" class="btn-outline">My Dashboard</a>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin_dashboard.php" class="btn-primary">Admin Panel</a>
                <?php else: ?>
                    <a href="staff_dashboard.php" class="btn-primary">Staff Panel</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="register.php" class="btn-primary">Get Started — It's Free</a>
                <a href="login.php"    class="btn-outline">Login</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- ─── STATS STRIP ──────────────────────────────────── -->
    <div class="stats-strip">
        <div class="stat-item">
            <span class="stat-num">500+</span>
            <div class="stat-label">Happy Clients</div>
        </div>
        <div class="stat-item">
            <span class="stat-num">20+</span>
            <div class="stat-label">Expert Stylists</div>
        </div>
        <div class="stat-item">
            <span class="stat-num">15+</span>
            <div class="stat-label">Services</div>
        </div>
        <div class="stat-item">
            <span class="stat-num">4.9★</span>
            <div class="stat-label">Average Rating</div>
        </div>
    </div>

    <!-- ─── HOW IT WORKS ─────────────────────────────────── -->
    <div class="section">
        <h2 class="section-title">How It Works</h2>
        <p class="section-subtitle">Book your appointment in three simple steps</p>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3>Create an Account</h3>
                <p>Register for free in under a minute. No credit card required.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h3>Browse Services</h3>
                <p>Explore our full menu — haircuts, skincare, massages, and more.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h3>Pick Your Slot</h3>
                <p>Choose your preferred stylist and book an available time slot.</p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <h3>Relax & Enjoy</h3>
                <p>Show up and let our professionals take care of the rest.</p>
            </div>
        </div>
    </div>

    <!-- ─── FEATURED SERVICES ─────────────────────────────── -->
    <div style="background: #f8f7ff; padding: 20px 0;">
        <div class="section" style="padding-top: 50px; padding-bottom: 50px;">
            <h2 class="section-title">Our Services</h2>
            <p class="section-subtitle">Professional treatments tailored just for you</p>

            <?php if (!empty($featured_services)): ?>
                <!-- Dynamic services from the database -->
                <div class="services-grid">
                    <?php foreach ($featured_services as $service): ?>
                        <div class="service-card">
                            <div class="icon">💇</div>
                            <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                            <p><?php echo htmlspecialchars($service['description']); ?></p>
                            <div class="service-meta">
                                <span class="service-price">RM <?php echo number_format($service['price'], 2); ?></span>
                                <span class="service-duration">⏱ <?php echo htmlspecialchars($service['duration']); ?> min</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Static placeholder cards when services table is empty or not yet created -->
                <div class="placeholder-services">
                    <div class="service-card">
                        <div class="icon">✂️</div>
                        <h3>Haircut & Styling</h3>
                        <p>Precision cuts and professional styling tailored to your face shape and preferences.</p>
                        <div class="service-meta">
                            <span class="service-price">From RM 50</span>
                            <span class="service-duration">⏱ 45 min</span>
                        </div>
                    </div>
                    <div class="service-card">
                        <div class="icon">💆</div>
                        <h3>Relaxing Massage</h3>
                        <p>Full-body or targeted massage to melt away stress and restore your energy.</p>
                        <div class="service-meta">
                            <span class="service-price">From RM 80</span>
                            <span class="service-duration">⏱ 60 min</span>
                        </div>
                    </div>
                    <div class="service-card">
                        <div class="icon">🌿</div>
                        <h3>Facial Treatment</h3>
                        <p>Deep-cleansing facials using premium skincare products for a radiant glow.</p>
                        <div class="service-meta">
                            <span class="service-price">From RM 90</span>
                            <span class="service-duration">⏱ 75 min</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 36px;">
                <a href="services.php" style="color: #6c5ce7; font-weight: 600; text-decoration: none; font-size: 1rem;">
                    View All Services →
                </a>
            </div>
        </div>
    </div>

    <!-- ─── ROLE-BASED CTAs ───────────────────────────────── -->
    <?php if (!isset($_SESSION['user_id'])): ?>
    <div class="section">
        <h2 class="section-title">Who Are You?</h2>
        <p class="section-subtitle">We have the right experience for everyone</p>

        <div class="cta-grid">
            <div class="cta-card">
                <div class="cta-icon">🧖</div>
                <h3>I'm a Customer</h3>
                <p>Browse services, book appointments, and track your beauty history all in one place.</p>
                <a href="register.php" class="btn-cta">Book Now</a>
            </div>
            <div class="cta-card">
                <div class="cta-icon">💇</div>
                <h3>I'm a Stylist</h3>
                <p>Manage your schedule, view upcoming bookings, and build your professional profile.</p>
                <a href="register.php" class="btn-cta secondary">Join as Stylist</a>
            </div>
            <div class="cta-card">
                <div class="cta-icon">🔑</div>
                <h3>Already Have an Account?</h3>
                <p>Welcome back! Log in to access your personalised dashboard and bookings.</p>
                <a href="login.php" class="btn-cta secondary">Login</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ─── TESTIMONIALS ──────────────────────────────────── -->
    <div class="testimonials-bg">
        <div class="testimonials-inner">
            <h2 class="section-title">What Our Clients Say</h2>
            <p class="section-subtitle">Real experiences from our happy customers</p>

            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <p class="testimonial-text">"Booking was so easy! I found my stylist, picked a time, and the whole experience was seamless. Definitely coming back."</p>
                    <div class="testimonial-author">Sarah L.</div>
                    <div class="testimonial-role">Regular Customer</div>
                </div>
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <p class="testimonial-text">"As a stylist, managing my bookings through this system has saved me so much time. My clients love being able to book online."</p>
                    <div class="testimonial-author">James T.</div>
                    <div class="testimonial-role">Senior Stylist</div>
                </div>
                <div class="testimonial-card">
                    <div class="stars">★★★★☆</div>
                    <p class="testimonial-text">"Great service and a beautiful spa. The online booking made everything stress-free. I'll be recommending this to all my friends."</p>
                    <div class="testimonial-author">Priya M.</div>
                    <div class="testimonial-role">New Customer</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── FOOTER ────────────────────────────────────────── -->
    <footer>
        <div class="footer-links">
            <a href="index.php">Home</a>
            <a href="services.php">Services</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>
        <p style="margin: 0; color: #777;">
            &copy; <?php echo date('Y'); ?> Salon &amp; Spa Booking System. All rights reserved.
        </p>
    </footer>
</body>
</html>