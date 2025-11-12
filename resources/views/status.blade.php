<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Status - Fitness App</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 36px;
        }

        .header p {
            color: #666;
            font-size: 18px;
        }

        .video-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }

        .video-card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            background: #000;
            border-radius: 10px;
            overflow: hidden;
        }

        video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .info-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .info-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .info-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            background: #4CAF50;
            color: white;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 10px;
        }

        .milestone-list {
            list-style: none;
            margin-top: 20px;
        }

        .milestone-list li {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            color: #555;
        }

        .milestone-list li:last-child {
            border-bottom: none;
        }

        .milestone-list li::before {
            content: "✓ ";
            color: #4CAF50;
            font-weight: bold;
            margin-right: 8px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Fitness App - Project Status</h1>
            <p>Current Development Progress</p>
        </div>

        <!-- Video Section -->
        <div class="video-card">
            <h2>Milestone 3 - Demo Video</h2>
            <div class="video-container">
                <video controls>
                    <source src="{{ asset('assets/milestone_3.mp4') }}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>

        <!-- Status Information -->
        <div class="info-card">
            <h3>Development Status</h3>
            <span class="status-badge">95% Complete</span>

            <p style="margin-top: 20px;">
                The Fitness App development is nearly complete. Below are the completed milestones:
            </p>

            <ul class="milestone-list">
                <li>Milestone 1: Complete UI implementation with 10 screens and navigation</li>
                <li>Milestone 1: Mock data integration for all features</li>
                <li>Milestone 2: Laravel backend setup and API development</li>
                <li>Milestone 2: Database schema and migrations</li>
                <li>Milestone 2: Authentication system (Student/Admin roles)</li>
                <li>Milestone 2: Workout management and video streaming</li>
                <li>Milestone 2: Nutrition plans management</li>
                <li>Milestone 2: Real-time messaging system</li>
                <li>Milestone 2: Admin panel for content management</li>
                <li>Milestone 3: Full mobile app backend integration</li>
                <li>Milestone 3: Custom video player with controls</li>
                <li>Milestone 3: YouTube video integration</li>
                <li>Milestone 3: Calendar and progress tracking</li>
                <li>Milestone 3: Profile management and avatar uploads</li>
            </ul>

            <p style="margin-top: 20px;">
                <strong>Remaining Work:</strong> Payment integration (Stripe) for subscription management.
            </p>

        </div>
    </div>
</body>
</html>
