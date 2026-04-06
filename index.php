<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>MatchFace Campus - Connect on Campus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            height: 100vh;
            width: 100%;
            overflow: hidden;
        }

        /* Background image like Tinder */
        .hero-bg {
            position: relative;
            width: 100%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1523580494863-6f3031224c94?w=1600&q=80');
            background-size: cover;
            background-position: center 30%;
            background-repeat: no-repeat;
        }

        /* Dark overlay for text readability */
        .hero-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.55) 0%, rgba(0, 0, 0, 0.4) 100%);
        }

        /* Content container */
        .content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 2rem;
        }

        /* Logo */
        .logo {
            margin-bottom: 1.5rem;
        }

        .logo-icon {
            font-size: 4rem;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3));
        }

        .logo-text {
            font-size: 2.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Tagline */
        .tagline {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            text-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .subtagline {
            color: rgba(255,255,255,0.9);
            font-size: 1rem;
            margin-bottom: 2.5rem;
            max-width: 400px;
            text-shadow: 0 1px 4px rgba(0,0,0,0.2);
        }

        /* Centered login button */
        .btn-login {
            background: linear-gradient(95deg, #ff6b6b, #ff8e53);
            padding: 1rem 3rem;
            border-radius: 60px;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
            transition: all 0.25s ease;
            border: none;
            cursor: pointer;
            margin-bottom: 3rem;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }

        /* Minimal info cards - subtle, not overwhelming */
        .info-row {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
            max-width: 800px;
            margin-top: 1rem;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 0.6rem 1.5rem;
            color: white;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(255,255,255,0.25);
        }

        .info-item i {
            font-size: 1.1rem;
            color: #ffb347;
        }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            color: rgba(255,255,255,0.6);
            font-size: 0.75rem;
            z-index: 2;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .logo-text {
                font-size: 2rem;
            }
            .tagline {
                font-size: 1.2rem;
            }
            .btn-login {
                padding: 0.8rem 2rem;
                font-size: 1rem;
            }
            .info-item {
                padding: 0.4rem 1.2rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="hero-bg">
        <div class="content">
            <!-- Logo -->
            <div class="logo">
                <span class="logo-icon"></span>
                <span class="logo-text">MatchFace</span>
            </div>

            <!-- Main message -->
            <div class="tagline">Connect on campus</div>
            <div class="subtagline">Meet people around you · Share interests · Make friends</div>

            <!-- Centered Login Button -->
            <a href="pages/login.php" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login to continue
            </a>

            <!-- Minimal information (subtle, like Tinder footer) -->
            <div class="info-row">
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i> Campus only
                </div>
                <div class="info-item">
                    <i class="fas fa-face-smile"></i> By affinity
                </div>
                <div class="info-item">
                    <i class="fas fa-shield-alt"></i> Secure
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2025 MatchFace Campus · Socialize on your campus</p>
        </div>
    </div>

    <script>
        // Simple console log
        console.log("MatchFace Campus - Social networking platform");
    </script>
</body>
</html>
