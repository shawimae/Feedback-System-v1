<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #f8fafc;
            --card: rgba(255, 255, 255, 0.9);
            --border: rgba(148, 163, 184, 0.18);
            --text: #0f172a;
            --muted: #64748b;
            --soft: #f1f5f9;
            --success: #059669;
            --shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
        }
        body {
            min-height: 100vh;
            font-family: "SF Pro Text", "SF Pro Display", "Helvetica Neue", "Helvetica", "Arial", -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            letter-spacing: -0.01em;
            background: radial-gradient(circle at top left, rgba(16, 185, 129, 0.06), transparent 30%), radial-gradient(circle at bottom right, rgba(59, 130, 246, 0.06), transparent 30%), var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            color: var(--text);
        }
        .wrapper { width: 100%; max-width: 720px; }
        .card {
            background: var(--card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .top-accent { height: 5px; background: linear-gradient(90deg, #10b981, #34d399, #60a5fa); }
        .content { padding: 50px 36px 36px; text-align: center; }
        .badge {
            display: inline-block;
            font-size: 13px;
            color: var(--muted);
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 8px 14px;
            border-radius: 999px;
            margin-bottom: 20px;
        }
        .logo-wrap {
            margin: 0 auto 18px;
            display: flex;
            justify-content: center;
        }
        .logo {
            width: 92px;
            height: 92px;
            object-fit: contain;
            filter: drop-shadow(0 12px 24px rgba(15, 23, 42, 0.12));
        }
        .icon {
            width: 88px;
            height: 88px;
            margin: 0 auto 20px;
            border-radius: 24px;
            background: linear-gradient(180deg, #ecfdf5, #ecfeff);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1fae5;
        }
        .icon svg { width: 34px; height: 34px; stroke: var(--success); }
        h1 { font-size: 32px; margin-bottom: 12px; font-weight: 600; letter-spacing: -0.03em; }
        .subtitle { font-size: 15px; color: var(--muted); line-height: 1.7; max-width: 520px; margin: 0 auto; }
        .rating-box {
            margin-top: 28px;
            background: var(--soft);
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 16px 20px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .rating-label { font-size: 13px; color: var(--muted); }
        .rating-value { font-size: 20px; font-weight: 600; }
        .detail-row {
            margin-top: 18px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .detail-pill {
            font-size: 12px;
            color: var(--muted);
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 8px 12px;
        }
        .stars { display: flex; justify-content: center; gap: 4px; margin-top: 4px; }
        .star { font-size: 22px; }
        .star.full { color: #facc15; }
        .star.half { background: linear-gradient(90deg, #facc15 50%, #e5e7eb 50%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .star.empty { color: #e5e7eb; }
        .divider { height: 1px; background: linear-gradient(to right, transparent, #e2e8f0, transparent); margin: 30px auto; width: 80%; }
        .review-text { font-size: 14px; color: var(--muted); margin-bottom: 16px; }
        .btn {
            display: inline-block;
            background: #0f172a;
            color: #fff;
            padding: 12px 22px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
            border: 0;
            cursor: pointer;
        }
        .btn:hover { background: #1e293b; }
        .footer { margin-top: 20px; font-size: 12px; color: #94a3b8; }
        .success-alert {
            margin: 0 auto 18px;
            max-width: 560px;
            padding: 12px 14px;
            border-radius: 16px;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            font-size: 13px;
        }
        @media (max-width: 600px) {
            .content { padding: 36px 20px; }
            h1 { font-size: 25px; }
            .star { font-size: 20px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="top-accent"></div>
        <div class="content">
            @if(session('success'))
                <div class="success-alert">{{ session('success') }}</div>
            @endif

            <div class="logo-wrap">
                <img src="{{ $feedback->store?->profile_photo_url ?: asset('assets/img/logo.png.png') }}" alt="{{ $feedback->store?->name ?? 'System' }} logo" class="logo object-contain">
            </div>

            <div class="badge">Feedback Submitted</div>

            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
            </div>

            <h1>Thank you for your response</h1>

            <p class="subtitle">
                We truly appreciate you taking the time to share your feedback with us. Your input helps us improve and serve you better.
            </p>

            <div class="detail-row">
                <div class="detail-pill">Submitted: {{ optional($feedback->created_at)->format('M d, Y h:i A') }}</div>
                @if($feedback->store)
                    <div class="detail-pill">Store: {{ $feedback->store->name }}</div>
                @endif
            </div>

            @if($feedback->overall_rating)
                <div class="rating-box">
                    <div class="rating-label">Your Overall Rating</div>
                    <div class="rating-value">{{ number_format($feedback->overall_rating, 1) }}/5</div>
                    <div class="stars">
                        @for ($i = 1; $i <= 5; $i++)
                            @if($feedback->overall_rating >= $i)
                                <span class="star full">&#9733;</span>
                            @elseif($feedback->overall_rating >= ($i - 0.5))
                                <span class="star half">&#9733;</span>
                            @else
                                <span class="star empty">&#9733;</span>
                            @endif
                        @endfor
                    </div>
                </div>
            @endif

            @if($showGoogleReviewButton)
                <div class="divider"></div>

                <p class="review-text">
                    Loved your visit? Share it on Google.
                </p>

                <a href="{{ $googleReviewUrl }}" target="_blank" class="btn">
                    Google Review
                </a>
            @endif

            <div class="footer">
                Your feedback has been recorded successfully.
            </div>
        </div>
    </div>
</div>
</body>
</html>
