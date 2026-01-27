<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>3K Fitness Contact</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f6f8; font-family: 'Arial', sans-serif; color:#1f2937;">
    <div style="width:100%; padding:24px 12px; background-color:#f5f6f8;">
        <div style="max-width:600px; margin:0 auto; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 12px 24px rgba(17, 24, 39, 0.08);">
            <div style="background:linear-gradient(135deg, #9b1c1c 0%, #b91c1c 45%, #ef4444 100%); padding:24px;">
                <h1 style="margin:0; font-size:22px; color:#ffffff; letter-spacing:0.3px;">3K Fitness Contact</h1>
                <p style="margin:8px 0 0; color:rgba(255,255,255,0.85); font-size:14px;">New message received from the landing page</p>
            </div>

            <div style="padding:24px;">
                <h2 style="margin:0 0 12px; font-size:18px; color:#111827;">Contact Details</h2>
                <div style="background:#f9fafb; border-radius:12px; padding:16px; border:1px solid #e5e7eb;">
                    <p style="margin:0 0 8px; font-size:14px;"><strong style="color:#111827;">Name:</strong> {{ $name }}</p>
                    <p style="margin:0 0 8px; font-size:14px;"><strong style="color:#111827;">Email:</strong> {{ $email }}</p>
                </div>

                <h2 style="margin:24px 0 12px; font-size:18px; color:#111827;">Message</h2>
                <div style="background:#ffffff; border-radius:12px; padding:16px; border:1px solid #e5e7eb;">
                    <p style="margin:0; font-size:14px; line-height:1.6; white-space:pre-line;">{{ $content }}</p>
                </div>

                <div style="margin-top:24px; padding-top:16px; border-top:1px solid #e5e7eb; display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                    <span style="font-size:12px; color:#6b7280;">Sent from 3K Fitness Landing Page</span>
                    <span style="font-size:12px; color:#6b7280;">Reply to this email to contact the sender</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
