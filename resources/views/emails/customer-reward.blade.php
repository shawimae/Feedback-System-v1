<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Review Reward Approved</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#1e293b;">

    <div style="width:100%; background-color:#f8fafc; padding:40px 20px;">
        <div style="max-width:700px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; overflow:hidden; box-shadow:0 8px 24px rgba(15,23,42,0.08);">

            <div style="background:#0f172a; padding:24px 32px;">
                <h1 style="margin:0; font-size:24px; color:#ffffff;">Your Reward Has Been Approved</h1>
                <p style="margin:8px 0 0; font-size:14px; color:#cbd5e1;">
                    Your Google Review reward is now added to your account.
                </p>
            </div>

            <div style="padding:32px;">
                <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
                    Hi <strong>{{ $customer->name ?? 'Customer' }}</strong>,
                </p>

                <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
                    Thank you for supporting <strong>{{ $store->name ?? 'our store' }}</strong> and for sharing your Google Review.
                </p>

                <div style="margin:24px 0; padding:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px;">
                    <p style="margin:0 0 12px; font-size:15px;">
                        <strong>You earned {{ $points }} reward points</strong>
                    </p>

                    <p style="margin:0 0 12px; font-size:15px;">
                        <strong>Total Points:</strong> {{ $customer->total_points ?? 0 }}
                    </p>

                    @if(!is_null($feedback->overall_rating))
                        <p style="margin:0; font-size:15px;">
                            <strong>Your Rating:</strong> {{ number_format($feedback->overall_rating, 1) }}/5
                        </p>
                    @endif
                </div>

                <p style="margin:0; font-size:15px; line-height:1.7;">
                    We appreciate your feedback, your review, and your continued support.
                </p>
            </div>

            <div style="padding:20px 32px; background:#f8fafc; border-top:1px solid #e2e8f0;">
                <p style="margin:0; font-size:12px; color:#64748b;">
                    Customer Feedback System
                </p>
            </div>
        </div>
    </div>

</body>
</html>
