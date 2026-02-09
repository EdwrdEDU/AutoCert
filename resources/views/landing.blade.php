<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoCert - Certificates that look official in minutes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #111827;
            --muted: #6b7280;
            --accent: #4f46e5;
            --accent-dark: #4338ca;
            --paper: #f9fafb;
            --sun: #e0e7ff;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Space Grotesk", "Segoe UI", Tahoma, sans-serif;
            color: var(--ink);
            background: radial-gradient(1200px 600px at 20% -10%, #eef2ff 0%, rgba(238, 242, 255, 0) 60%),
                        radial-gradient(900px 600px at 90% 10%, #e0f2fe 0%, rgba(224, 242, 254, 0) 55%),
                        var(--paper);
            min-height: 100vh;
        }

        a { color: inherit; text-decoration: none; }

        .page {
            position: relative;
            overflow: hidden;
        }

        .halo {
            position: absolute;
            inset: -120px auto auto -120px;
            width: 320px;
            height: 320px;
            background: linear-gradient(140deg, #e0e7ff, #f5f3ff);
            filter: blur(10px);
            border-radius: 32% 68% 66% 34% / 48% 53% 47% 52%;
            opacity: 0.75;
            animation: float 12s ease-in-out infinite;
        }

        .halo.two {
            inset: auto -120px 60px auto;
            width: 380px;
            height: 380px;
            background: linear-gradient(160deg, #dbeafe, #eff6ff);
            animation-delay: -4s;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 20px 80px;
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 20px;
            letter-spacing: 0.2px;
        }

        .logo-mark {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: #fff;
            display: grid;
            place-items: center;
            box-shadow: 0 10px 30px rgba(20, 26, 33, 0.15);
        }

        .nav-links {
            display: flex;
            gap: 18px;
            font-weight: 500;
            color: var(--muted);
        }

        .nav-links a:hover { color: var(--ink); }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
            gap: 40px;
            align-items: center;
            margin-top: 64px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            background: #fff3e6;
            border-radius: 999px;
            font-weight: 600;
            font-size: 13px;
            color: #b45309;
        }

        h1 {
            font-family: "Fraunces", "Times New Roman", serif;
            font-size: clamp(36px, 6vw, 56px);
            line-height: 1.05;
            margin: 18px 0 18px;
        }

        .lead {
            font-size: 18px;
            color: var(--muted);
            max-width: 520px;
        }

        .cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 28px;
        }

        .btn {
            padding: 14px 22px;
            border-radius: 14px;
            border: 1px solid transparent;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #6366f1);
            color: #fff;
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.35);
        }

        .btn-secondary {
            border-color: #d2d7df;
            background: #fff;
            color: var(--ink);
        }

        .btn:hover { transform: translateY(-2px); }

        .hero-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 24px 50px rgba(16, 24, 40, 0.12);
            position: relative;
        }

        .hero-card::after {
            content: "";
            position: absolute;
            inset: 18px;
            border-radius: 18px;
            border: 1px dashed rgba(20, 26, 33, 0.2);
        }

        .card-grid {
            display: grid;
            gap: 16px;
        }

        .stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            background: #f6f8fb;
            border-radius: 14px;
            font-weight: 600;
        }

        .stamp {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #1d4ed8;
            background: #e0edff;
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 600;
        }

        .section {
            margin-top: 90px;
        }

        .section-title {
            font-size: 28px;
            margin-bottom: 18px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .feature {
            background: #ffffff;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 18px 40px rgba(18, 22, 30, 0.08);
        }

        .feature h3 { margin: 12px 0 8px; }
        .feature p { color: var(--muted); margin: 0; }

        .steps {
            display: grid;
            gap: 16px;
        }

        .step {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 14px;
            align-items: start;
            padding: 16px 18px;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 12px 30px rgba(20, 26, 33, 0.08);
        }

        .step span {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: #0ea5e9;
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 700;
        }

        .footer {
            margin-top: 90px;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
        }

        .fade-up {
            animation: fadeUp 0.9s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(16px); }
        }

        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; }
            .nav-links { display: none; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="halo"></div>
        <div class="halo two"></div>

        <div class="container" style="min-height: 100vh; display: grid; place-items: center;">
            <div style="text-align: center;" class="fade-up">
                <div class="logo" style="justify-content: center; margin-bottom: 18px;">
                    <span class="logo-mark">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7 3H14L19 8V21H7V3Z" stroke="#4f46e5" stroke-width="1.5" fill="#fff"/>
                            <path d="M14 3V8H19" stroke="#4f46e5" stroke-width="1.5"/>
                            <path d="M9 12H17" stroke="#4f46e5" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M9 16H15" stroke="#4f46e5" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </span>
                    AutoCert
                </div>
                <h1 style="margin-bottom: 22px;">Let’s start</h1>
                <a class="btn btn-primary" href="{{ route('templates.index') }}">Open dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
