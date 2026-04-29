<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxxpert - Free GST & Income Tax Management for Indian Businesses</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
            background: #f8f9fa;
        }

        /* Header & Navigation */
        .header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 30px rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.4s ease;
        }

        .header.scrolled {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 5px 30px rgba(0,0,0,0.15);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 26px;
            font-weight: 700;
            color: #2c3e50;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo-icon {
            font-size: 32px;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        .nav-menu a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            padding: 5px 0;
        }

        .nav-menu a:hover {
            color: #3498db;
        }

        .nav-menu a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, #3498db, #2c3e50);
            border-radius: 3px;
            transition: width 0.4s ease;
        }

        .nav-menu a:hover::after {
            width: 100%;
        }

        .nav-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn {
            padding: 12px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.4s ease;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.7s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-outline {
            border: 2px solid #3498db;
            color: #3498db;
            background: transparent;
        }

        .btn-outline:hover {
            background: #3498db;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(52, 152, 219, 0.4);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 180px 0 120px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,0 1000,1000"/></svg>');
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .hero-content h1 {
            font-size: 3.8rem;
            font-weight: 800;
            margin-bottom: 25px;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 35px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .hero-btn {
            padding: 15px 35px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .hero-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.7s ease;
        }

        .hero-btn:hover::before {
            left: 100%;
        }

        .hero-btn.primary {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.3);
        }

        .hero-btn.primary:hover {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(231, 76, 60, 0.4);
        }

        .hero-btn.secondary {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .hero-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.2);
        }

        .hero-image {
            text-align: center;
            position: relative;
        }

        .dashboard-preview {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            transform: perspective(1000px) rotateY(-5deg) rotateX(5deg);
            transition: transform 0.5s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 2;
        }

        .dashboard-preview:hover {
            transform: perspective(1000px) rotateY(0) rotateX(0);
        }

        /* Floating Tax Elements */
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 15%;
            left: 5%;
            animation-delay: 0s;
            background: rgba(231, 76, 60, 0.2);
        }

        .floating-element:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 65%;
            left: 10%;
            animation-delay: 1s;
            background: rgba(52, 152, 219, 0.2);
        }

        .floating-element:nth-child(3) {
            width: 70px;
            height: 70px;
            top: 25%;
            right: 10%;
            animation-delay: 2s;
            background: rgba(46, 204, 113, 0.2);
        }

        .floating-element:nth-child(4) {
            width: 50px;
            height: 50px;
            top: 70%;
            right: 5%;
            animation-delay: 3s;
            background: rgba(155, 89, 182, 0.2);
        }

        .floating-element:nth-child(5) {
            width: 90px;
            height: 90px;
            top: 45%;
            left: 7%;
            animation-delay: 4s;
            background: rgba(241, 196, 15, 0.2);
        }

        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(5deg);
            }
            100% {
                transform: translateY(0) rotate(0deg);
            }
        }

        /* Pulsing Icons */
        .pulse-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 40px;
        }

        .pulse-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: pulse 2s infinite;
            transition: all 0.4s ease;
        }

        .pulse-icon:hover {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.25);
        }

        .pulse-icon:nth-child(1) {
            animation-delay: 0s;
        }

        .pulse-icon:nth-child(2) {
            animation-delay: 0.5s;
        }

        .pulse-icon:nth-child(3) {
            animation-delay: 1s;
        }

        .pulse-icon:nth-child(4) {
            animation-delay: 1.5s;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(255, 255, 255, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
            }
        }

        /* Features Section */
.features {
    padding: 120px 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    position: relative;
    overflow: hidden;
}

.features::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.1) 0%, transparent 50%);
}

.section-title {
    text-align: center;
    margin-bottom: 80px;
    position: relative;
    z-index: 2;
}

.section-title h2 {
    font-size: 3.2rem;
    background: linear-gradient(135deg, #2c3e50, #3498db);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 20px;
    font-weight: 800;
}

.section-title p {
    font-size: 1.3rem;
    color: #7f8c8d;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

.features-grid {
    max-width: 1300px;
    margin: 0 auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
    gap: 30px;
    position: relative;
    z-index: 2;
}

.feature-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 24px;
    padding: 50px 35px;
    text-align: center;
    transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
    box-shadow: 
        0 10px 30px rgba(0, 0, 0, 0.05),
        inset 0 1px 0 rgba(255, 255, 255, 0.6);
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, 
        transparent, 
        rgba(255, 255, 255, 0.4), 
        transparent);
    transition: left 0.7s ease;
}

.feature-card:hover::before {
    left: 100%;
}

.feature-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 24px;
    padding: 2px;
    background: linear-gradient(135deg, 
        rgba(255, 255, 255, 0.8), 
        rgba(255, 255, 255, 0.2));
    -webkit-mask: 
        linear-gradient(#fff 0 0) content-box, 
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 0.4s ease;
}

.feature-card:hover::after {
    opacity: 1;
}

/* Individual card color themes */
.feature-card:nth-child(1) {
    background: rgba(52, 152, 219, 0.1);
    border: 1px solid rgba(52, 152, 219, 0.2);
}

.feature-card:nth-child(1):hover {
    background: rgba(52, 152, 219, 0.15);
    transform: translateY(-15px) scale(1.02);
    box-shadow: 
        0 25px 50px rgba(52, 152, 219, 0.15),
        0 10px 30px rgba(52, 152, 219, 0.1);
}

.feature-card:nth-child(2) {
    background: rgba(46, 204, 113, 0.1);
    border: 1px solid rgba(46, 204, 113, 0.2);
}

.feature-card:nth-child(2):hover {
    background: rgba(46, 204, 113, 0.15);
    transform: translateY(-15px) scale(1.02);
    box-shadow: 
        0 25px 50px rgba(46, 204, 113, 0.15),
        0 10px 30px rgba(46, 204, 113, 0.1);
}

.feature-card:nth-child(3) {
    background: rgba(155, 89, 182, 0.1);
    border: 1px solid rgba(155, 89, 182, 0.2);
}

.feature-card:nth-child(3):hover {
    background: rgba(155, 89, 182, 0.15);
    transform: translateY(-15px) scale(1.02);
    box-shadow: 
        0 25px 50px rgba(155, 89, 182, 0.15),
        0 10px 30px rgba(155, 89, 182, 0.1);
}

.feature-card:nth-child(4) {
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid rgba(231, 76, 60, 0.2);
}

.feature-card:nth-child(4):hover {
    background: rgba(231, 76, 60, 0.15);
    transform: translateY(-15px) scale(1.02);
    box-shadow: 
        0 25px 50px rgba(231, 76, 60, 0.15),
        0 10px 30px rgba(231, 76, 60, 0.1);
}

.feature-card:nth-child(5) {
    background: rgba(241, 196, 15, 0.1);
    border: 1px solid rgba(241, 196, 15, 0.2);
}

.feature-card:nth-child(5):hover {
    background: rgba(241, 196, 15, 0.15);
    transform: translateY(-15px) scale(1.02);
    box-shadow: 
        0 25px 50px rgba(241, 196, 15, 0.15),
        0 10px 30px rgba(241, 196, 15, 0.1);
}

.feature-card:nth-child(6) {
    background: rgba(52, 73, 94, 0.1);
    border: 1px solid rgba(52, 73, 94, 0.2);
}

.feature-card:nth-child(6):hover {
    background: rgba(52, 73, 94, 0.15);
    transform: translateY(-15px) scale(1.02);
    box-shadow: 
        0 25px 50px rgba(52, 73, 94, 0.15),
        0 10px 30px rgba(52, 73, 94, 0.1);
}

.feature-icon {
    font-size: 64px;
    margin-bottom: 30px;
    display: inline-block;
    transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.1));
}

.feature-card:hover .feature-icon {
    transform: scale(1.2) rotate(5deg);
    filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.15));
}

/* Individual icon colors */
.feature-card:nth-child(1) .feature-icon {
    background: linear-gradient(135deg, #3498db, #2980b9);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.feature-card:nth-child(2) .feature-icon {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.feature-card:nth-child(3) .feature-icon {
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.feature-card:nth-child(4) .feature-icon {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.feature-card:nth-child(5) .feature-icon {
    background: linear-gradient(135deg, #f39c12, #f1c40f);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.feature-card:nth-child(6) .feature-icon {
    background: linear-gradient(135deg, #34495e, #2c3e50);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.feature-card h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #2c3e50, #34495e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    transition: all 0.4s ease;
}

.feature-card:hover h3 {
    background: linear-gradient(135deg, #3498db, #2980b9);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.feature-card p {
    color: #5d6d7e;
    line-height: 1.7;
    font-size: 1.1rem;
    transition: color 0.4s ease;
}

.feature-card:hover p {
    color: #2c3e50;
}

/* Floating animation for cards */
@keyframes float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.feature-card {
    animation: float 6s ease-in-out infinite;
}

.feature-card:nth-child(2) {
    animation-delay: 1s;
}

.feature-card:nth-child(3) {
    animation-delay: 2s;
}

.feature-card:nth-child(4) {
    animation-delay: 3s;
}

.feature-card:nth-child(5) {
    animation-delay: 4s;
}

.feature-card:nth-child(6) {
    animation-delay: 5s;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .features-grid {
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 25px;
    }
}

@media (max-width: 768px) {
    .features {
        padding: 80px 0;
    }
    
    .section-title h2 {
        font-size: 2.5rem;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .feature-card {
        padding: 40px 25px;
    }
    
    .feature-icon {
        font-size: 54px;
    }
    
    .feature-card h3 {
        font-size: 1.6rem;
    }
}

@media (max-width: 480px) {
    .section-title h2 {
        font-size: 2rem;
    }
    
    .section-title p {
        font-size: 1.1rem;
    }
    
    .feature-card {
        padding: 30px 20px;
        border-radius: 20px;
    }
    
    .feature-icon {
        font-size: 48px;
    }
    
    .feature-card h3 {
        font-size: 1.4rem;
    }
    
    .feature-card p {
        font-size: 1rem;
    }
}

/* Enhanced hover effects */
.feature-card {
    position: relative;
}

.feature-card .hover-content {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, 
        rgba(255, 255, 255, 0.9), 
        rgba(255, 255, 255, 0.7));
    backdrop-filter: blur(10px);
    border-radius: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    padding: 20px;
    text-align: center;
}

.feature-card:hover .hover-content {
    opacity: 1;
    transform: scale(1);
}

.hover-content .learn-more {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 12px 24px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

.hover-content .learn-more:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
}

        /* Free Section */
        .free-section {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 100px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .free-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,0 0,1000 1000,0"/></svg>');
        }

        .free-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .free-section h2 {
            font-size: 3rem;
            margin-bottom: 25px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .free-section p {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .free-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin: 50px 0;
        }

        .free-feature {
            text-align: center;
            padding: 25px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
        }

        .free-feature:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.25);
        }

        .free-feature .icon {
            font-size: 42px;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .free-feature h4 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        /* How It Works */
        .how-it-works {
            padding: 120px 0;
            background: white;
        }

        .steps {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 50px;
        }

        .step {
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0 auto 25px;
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.3);
            transition: all 0.4s ease;
        }

        .step:hover .step-number {
            transform: scale(1.1);
            box-shadow: 0 15px 30px rgba(52, 152, 219, 0.4);
        }

        .step h3 {
            font-size: 1.4rem;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .step p {
            color: #7f8c8d;
            line-height: 1.6;
        }

        /* Testimonials */
        .testimonials {
            padding: 120px 0;
            background: #f8f9fa;
        }

        .testimonial-grid {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
        }

        .testimonial-card {
            background: white;
            padding: 40px 35px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.08);
            transition: all 0.4s ease;
            position: relative;
        }

        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: 20px;
            left: 30px;
            font-size: 80px;
            color: #3498db;
            opacity: 0.1;
            font-family: Georgia, serif;
        }

        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0,0,0,0.15);
        }

        .testimonial-text {
            font-style: italic;
            color: #7f8c8d;
            margin-bottom: 25px;
            line-height: 1.7;
            position: relative;
            z-index: 2;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .author-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .author-info h4 {
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .author-info p {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 120px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,0 1000,1000 1000,0"/></svg>');
        }

        .cta-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .cta-section h2 {
            font-size: 3rem;
            margin-bottom: 25px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .cta-section p {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .cta-buttons {
            display: flex;
            gap: 25px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 80px 0 40px;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #3498db, #2c3e50);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 50px;
        }

        .footer-column h3 {
            font-size: 1.3rem;
            margin-bottom: 25px;
            color: #3498db;
            position: relative;
            display: inline-block;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 3px;
            background: #3498db;
            border-radius: 2px;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .footer-links a:hover {
            color: #3498db;
            transform: translateX(5px);
        }

        .footer-bottom {
            text-align: center;
            margin-top: 70px;
            padding-top: 30px;
            border-top: 1px solid #34495e;
            color: #bdc3c7;
            font-size: 0.95rem;
        }

        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #2c3e50;
            transition: transform 0.3s ease;
        }

        .mobile-menu-btn:hover {
            transform: scale(1.1);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-content h1 {
                font-size: 3.2rem;
            }
            
            .section-title h2 {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .nav-menu, .nav-actions {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 40px;
            }

            .hero-content h1 {
                font-size: 2.8rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .steps {
                grid-template-columns: 1fr;
            }

            .hero-buttons, .cta-buttons {
                justify-content: center;
            }

            .pulse-container {
                gap: 20px;
            }

            .pulse-icon {
                width: 70px;
                height: 70px;
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 2.2rem;
            }
            
            .hero-content p {
                font-size: 1.1rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .hero-btn {
                padding: 12px 25px;
            }
            
            .pulse-container {
                flex-wrap: wrap;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeInUp 0.8s ease-out;
        }
    </style>
</head>
<body>
    <!-- Header & Navigation -->
    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <span class="logo-icon">🧾</span>
                Taxxpert
            </a>
            
            <nav>
                <ul class="nav-menu">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#testimonials">Testimonials</a></li>
                    <li><a href="#free">Pricing</a></li>
                    <li><a href="#about">About</a></li>
                </ul>
            </nav>
            
            <div class="nav-actions">
                <a href="login.php" class="btn btn-outline">Sign In</a>
                <a href="register.php" class="btn btn-primary">Get Started Free</a>
            </div>
            
            <button class="mobile-menu-btn">☰</button>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="floating-elements">
            <div class="floating-element">🧾</div>
            <div class="floating-element">💰</div>
            
            <div class="floating-element">📈</div>
            <div class="floating-element">💸</div>
        </div>
        
        <div class="hero-container">
            <div class="hero-content fade-in">
                <h1>Simplify GST & Income Tax for Your Business</h1>
                <p>Free, easy-to-use tax management software designed specifically for Indian product-based companies. Track purchases, sales, expenses, and stay compliant with automated calculations.</p>
                <div class="hero-buttons">
                    <a href="register.php" class="hero-btn primary">
                        <span>🚀</span> Start Free Today
                    </a>
                    <a href="#features" class="hero-btn secondary">
                        <span>🔍</span> See How It Works
                    </a>
                </div>
                <div style="margin-top: 25px; font-size: 0.95rem; opacity: 0.8;">
                    ✅ No credit card required • 🆓 Free forever • ⚡ Setup in 2 minutes
                </div>
                
                <div class="pulse-container">
                    <div class="pulse-icon">🧮</div>
                    <div class="pulse-icon">💰</div>
                    <div class="pulse-icon">📊</div>
                    <div class="pulse-icon">📈</div>
                </div>
            </div>
            <div class="hero-image fade-in">
                <div class="dashboard-preview">
                    <div style="background: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 15px; text-align: center; color: #2c3e50; backdrop-filter: blur(10px);">
                        <div style="font-size: 16px; margin-bottom: 15px; font-weight: 600;">📊 Your Tax Dashboard Preview</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                            <div style="background: white; padding: 15px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                                <div>📥 Purchases</div>
                                <div style="font-weight: bold; font-size: 16px;">₹1,25,000</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                                <div>📤 Sales</div>
                                <div style="font-weight: bold; font-size: 16px;">₹2,50,000</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                                <div>🧮 GST Payable</div>
                                <div style="font-weight: bold; font-size: 16px; color: #e74c3c;">₹18,750</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                                <div>💰 Income Tax</div>
                                <div style="font-weight: bold; font-size: 16px; color: #27ae60;">₹31,250</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="section-title fade-in">
            <h2>Everything You Need for Tax Compliance</h2>
            <p>Comprehensive features designed specifically for Indian GST and Income Tax requirements</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card fade-in">
                <div class="feature-icon">🧮</div>
                <h3>Smart GST Calculation</h3>
                <p>Automated GST calculations with proper set-off rules. Input tax credit utilization as per Indian GST laws with step-by-step audit trail.</p>
            </div>
            
            <div class="feature-card fade-in">
                <div class="feature-icon">💰</div>
                <h3>Income Tax Management</h3>
                <p>Track business expenses, calculate profits, and compute income tax liability. Separate from GST for accurate tax planning.</p>
            </div>
            
            <div class="feature-card fade-in">
                <div class="feature-icon">📊</div>
                <h3>Interactive Dashboard</h3>
                <p>Visual overview of your tax position with charts, summaries, and key metrics. Everything you need at a glance.</p>
            </div>
            
            <div class="feature-card fade-in">
                <div class="feature-icon">📥</div>
                <h3>Purchase Tracking</h3>
                <p>Record purchase invoices with supplier details, GST breakdown, and input tax credit eligibility tracking.</p>
            </div>
            
            <div class="feature-card fade-in">
                <div class="feature-icon">📤</div>
                <h3>Sales Management</h3>
                <p>Track sales invoices with customer information, automatic inter-state/intra-state GST classification.</p>
            </div>
            
            <div class="feature-card fade-in">
                <div class="feature-icon">🔔</div>
                <h3>Smart Notifications</h3>
                <p>Never miss a deadline with automated reminders for GST filing, tax payments, and compliance requirements.</p>
            </div>
        </div>
    </section>

    <!-- Free Section -->
    <section id="free" class="free-section">
        <div class="free-container fade-in">
            <h2>Completely Free. Seriously.</h2>
            <p>No hidden charges, no premium plans, no limitations. We believe every business should have access to proper tax management tools.</p>
            
            <div class="free-features">
                <div class="free-feature">
                    <div class="icon">💸</div>
                    <h4>Zero Cost</h4>
                    <p>Free forever for all features</p>
                </div>
                <div class="free-feature">
                    <div class="icon">🚫</div>
                    <h4>No Limitations</h4>
                    <p>Unlimited invoices and entries</p>
                </div>
                <div class="free-feature">
                    <div class="icon">🔒</div>
                    <h4>Data Privacy</h4>
                    <p>Your data stays with you</p>
                </div>
                <div class="free-feature">
                    <div class="icon">⚡</div>
                    <h4>Instant Setup</h4>
                    <p>Get started in 2 minutes</p>
                </div>
            </div>
            
            <a href="register.php" class="hero-btn primary" style="background: rgba(255, 255, 255, 0.9); color: #27ae60; border: 2px solid rgba(255, 255, 255, 0.3);">Start Free Today</a>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="how-it-works">
        <div class="section-title fade-in">
            <h2>Simple & Straightforward</h2>
            <p>Get started with Taxxpert in just 4 easy steps</p>
        </div>
        
        <div class="steps">
            <div class="step fade-in">
                <div class="step-number">1</div>
                <h3>Register Your Company</h3>
                <p>Sign up with your company details, GSTIN, and PAN in 2 minutes</p>
            </div>
            
            <div class="step fade-in">
                <div class="step-number">2</div>
                <h3>Record Transactions</h3>
                <p>Enter your purchase and sales invoices with GST details</p>
            </div>
            
            <div class="step fade-in">
                <div class="step-number">3</div>
                <h3>Track Expenses</h3>
                <p>Add business expenses for accurate income tax calculation</p>
            </div>
            
            <div class="step fade-in">
                <div class="step-number">4</div>
                <h3>Get Tax Insights</h3>
                <p>View automated GST and income tax calculations with compliance reports</p>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section id="testimonials" class="testimonials">
        <div class="section-title fade-in">
            <h2>Loved by Indian Businesses</h2>
            <p>See what our users have to say about Taxxpert</p>
        </div>
        
        <div class="testimonial-grid">
            <div class="testimonial-card fade-in">
                <div class="testimonial-text">
                    "Taxxpert has simplified our GST compliance tremendously. The automatic calculations and reminders have saved us hours of work every month."
                </div>
                <div class="testimonial-author">
                    <div class="author-avatar">RK</div>
                    <div class="author-info">
                        <h4>Rajesh Kumar</h4>
                        <p>Manufacturing Business, Delhi</p>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card fade-in">
                <div class="testimonial-text">
                    "As a small business owner, I can't afford expensive accounting software. Taxxpert being free has been a game-changer for our tax management."
                </div>
                <div class="testimonial-author">
                    <div class="author-avatar">PM</div>
                    <div class="author-info">
                        <h4>Priya Mehta</h4>
                        <p>Retail Business, Mumbai</p>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card fade-in">
                <div class="testimonial-text">
                    "The GST set-off calculation is incredibly accurate. It follows the exact rules we need for compliance. Highly recommended for Indian businesses!"
                </div>
                <div class="testimonial-author">
                    <div class="author-avatar">AS</div>
                    <div class="author-info">
                        <h4>Anil Sharma</h4>
                        <p>CA & Business Consultant</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-container fade-in">
            <h2>Ready to Simplify Your Tax Management?</h2>
            <p>Join thousands of Indian businesses using Taxxpert for free GST and income tax compliance</p>
            <div class="cta-buttons">
                <a href="register.php" class="hero-btn primary">Get Started Free</a>
                <a href="login.php" class="hero-btn secondary">Sign In</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="about">
        <div class="footer-container">
            <div class="footer-column">
                <h3>Taxxpert</h3>
                <p style="color: #bdc3c7; line-height: 1.6;">
                    Free GST and Income Tax management software designed specifically for Indian product-based companies. Making tax compliance simple and accessible for everyone.
                </p>
            </div>
            
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="login.php">Sign In</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Resources</h3>
                <ul class="footer-links">
                    <li><a href="#">GST Guide</a></li>
                    <li><a href="#">Income Tax Help</a></li>
                    <li><a href="#">Compliance Calendar</a></li>
                    <li><a href="#">Support</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Legal</h3>
                <ul class="footer-links">
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Data Security</a></li>
                    <li><a href="#">Compliance</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2024 Taxxpert. All rights reserved. Made with ❤️ for Indian Businesses</p>
            <p style="margin-top: 10px; font-size: 0.9rem;">
                GST and Income Tax compliance made simple and free for everyone
            </p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            const navMenu = document.querySelector('.nav-menu');
            const navActions = document.querySelector('.nav-actions');
            
            if (navMenu.style.display === 'flex') {
                navMenu.style.display = 'none';
                navActions.style.display = 'none';
            } else {
                navMenu.style.display = 'flex';
                navMenu.style.flexDirection = 'column';
                navMenu.style.position = 'absolute';
                navMenu.style.top = '80px';
                navMenu.style.left = '0';
                navMenu.style.width = '100%';
                navMenu.style.background = 'rgba(255, 255, 255, 0.98)';
                navMenu.style.backdropFilter = 'blur(15px)';
                navMenu.style.padding = '20px';
                navMenu.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
                navMenu.style.gap = '15px';
                
                navActions.style.display = 'flex';
                navActions.style.flexDirection = 'column';
                navActions.style.position = 'absolute';
                navActions.style.top = 'calc(80px + ' + (navMenu.children.length * 50) + 'px)';
                navActions.style.left = '0';
                navActions.style.width = '100%';
                navActions.style.background = 'rgba(255, 255, 255, 0.98)';
                navActions.style.backdropFilter = 'blur(15px)';
                navActions.style.padding = '20px';
                navActions.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
                navActions.style.gap = '15px';
            }
        });

        // Smooth scrolling for navigation links
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

        // Add fade-in animation to elements when they come into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, {
            threshold: 0.1
        });

        // Observe all sections for animation
        document.querySelectorAll('section').forEach(section => {
            observer.observe(section);
        });

        // Header background on scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>