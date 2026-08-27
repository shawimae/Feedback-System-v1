<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Submission Confirmation</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#1e293b;">
    <div style="width:100%; background-color:#f8fafc; padding:40px 20px;">
        <div style="max-width:720px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; overflow:hidden; box-shadow:0 8px 24px rgba(15,23,42,0.08);">
            <div style="background:#0f172a; padding:24px 32px;">
                <h1 style="margin:0; font-size:24px; color:#ffffff;">Feedback Received</h1>
                <p style="margin:8px 0 0; font-size:14px; color:#cbd5e1;">
                    Thank you for answering the survey for {{ $store->name ?? 'our store' }}.
                </p>
            </div>

            <div style="padding:32px;">
                <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
                    Hi <strong>{{ $feedback->customer_name ?: 'Customer' }}</strong>,
                </p>

                <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
                    This email confirms that we successfully received your feedback. Here is a copy of the response you submitted.
                </p>

                <div style="margin:24px 0; padding:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px;">
                    <h2 style="margin:0 0 14px; font-size:18px; color:#0f172a;">Submission Details</h2>
                    <p style="margin:0 0 10px; font-size:14px;"><strong>Store:</strong> {{ $store->name ?? 'N/A' }}</p>
                    <p style="margin:0 0 10px; font-size:14px;"><strong>Submitted At:</strong> {{ optional($feedback->created_at)->format('M d, Y h:i A') }}</p>
                    <p style="margin:0 0 10px; font-size:14px;"><strong>Email:</strong> {{ $feedback->customer_email ?? 'N/A' }}</p>
                    <p style="margin:0; font-size:14px;"><strong>Overall Rating:</strong> {{ !is_null($feedback->overall_rating) ? number_format($feedback->overall_rating, 1) . '/5' : 'N/A' }}</p>
                </div>

                <div style="margin:24px 0; padding:20px; background:#ffffff; border:1px solid #e2e8f0; border-radius:16px;">
                    <h2 style="margin:0 0 14px; font-size:18px; color:#0f172a;">Your Answers</h2>

                    @foreach($feedback->answers as $answer)
                        <div style="padding:12px 0; border-bottom:1px solid #e2e8f0;">
                            <p style="margin:0 0 6px; font-size:14px; font-weight:700; color:#0f172a;">
                                {{ $answer->display_question }}
                            </p>

                            @if(!is_null($answer->answer_rating))
                                <p style="margin:0; font-size:14px; color:#475569;">
                                    Rating: {{ $answer->answer_rating }}/5
                                </p>
                            @else
                                <p style="margin:0; font-size:14px; color:#475569;">
                                    {{ filled($answer->answer_text) ? $answer->answer_text : '-' }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <p style="margin:24px 0 0; font-size:15px; line-height:1.7;">
                    We appreciate your time and your feedback helps us improve our service.
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
