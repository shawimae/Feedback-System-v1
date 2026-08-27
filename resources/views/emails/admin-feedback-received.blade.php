<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Feedback Received</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#1e293b;">
    
    <div style="width:100%; background-color:#f8fafc; padding:40px 20px;">
        <div style="max-width:700px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; overflow:hidden; box-shadow:0 8px 24px rgba(15,23,42,0.08);">

            <div style="background:#0f172a; padding:24px 32px;">
                <h1 style="margin:0; font-size:24px; color:#ffffff;">New Feedback Received</h1>
                <p style="margin:8px 0 0; font-size:14px; color:#cbd5e1;">
                    A new survey response has been submitted.
                </p>
            </div>

            <div style="padding:32px;">
                <div style="margin-bottom:24px; padding:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px;">
                    <h2 style="margin:0 0 16px; font-size:18px; color:#0f172a;">Feedback Details</h2>

                    <p style="margin:0 0 10px; font-size:14px;"><strong>Store:</strong> {{ $store->name ?? 'N/A' }}</p>
                    <p style="margin:0 0 10px; font-size:14px;"><strong>Customer Name:</strong> {{ $feedback->customer_name ?? 'Anonymous' }}</p>
                    <p style="margin:0 0 10px; font-size:14px;"><strong>Email:</strong> {{ $feedback->customer_email ?? 'N/A' }}</p>
                    <p style="margin:0 0 10px; font-size:14px;"><strong>Phone:</strong> {{ $feedback->customer_phone ?? 'N/A' }}</p>
                    <p style="margin:0 0 10px; font-size:14px;"><strong>Overall Rating:</strong> {{ $feedback->overall_rating ?? 'N/A' }}</p>
                    <p style="margin:0 0 10px; font-size:14px;"><strong>Comment:</strong> {{ $feedback->overall_comment ?? 'No comment' }}</p>
                    <p style="margin:0; font-size:14px;"><strong>Submitted At:</strong> {{ optional($feedback->created_at)->format('M d, Y h:i A') }}</p>
                </div>

                <div style="padding:18px 20px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:14px;">
                    <p style="margin:0; font-size:14px; color:#1e3a8a;">
                        Please check the admin dashboard for the full response and survey details.
                    </p>
                </div>
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