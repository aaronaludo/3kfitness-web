<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Membership Approved</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f5f7; padding: 24px 12px; font-family: Arial, Helvetica, sans-serif;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width: 620px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background-color: #a40000; padding: 24px 28px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="color: #ffffff; font-size: 20px; font-weight: 700;">
                                        3K Fitness
                                    </td>
                                    <td align="right">
                                        <span style="display: inline-block; background-color: rgba(255,255,255,0.2); color: #ffffff; font-size: 12px; font-weight: 700; padding: 6px 10px; border-radius: 999px;">
                                            Membership Approved
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 28px;">
                            <h2 style="margin: 0 0 8px; font-size: 22px; color: #1f1f1f;">
                                Hello {{ $memberName }},
                            </h2>
                            <p style="margin: 0 0 18px; font-size: 15px; color: #444444; line-height: 1.6;">
                                Congratulations! Your <strong>{{ $membershipName }}</strong> membership has been approved.
                                You can now enjoy your membership benefits and start booking your sessions.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f9fafb; border-radius: 12px; padding: 16px; border: 1px solid #ececec;">
                                <tr>
                                    <td style="font-size: 13px; color: #7a7a7a; width: 140px;">Status</td>
                                    <td style="font-size: 14px; font-weight: 700; color: #10b981;">Approved</td>
                                </tr>
                                @if(!empty($approvedAt))
                                <tr>
                                    <td style="font-size: 13px; color: #7a7a7a; padding-top: 8px;">Approved on</td>
                                    <td style="font-size: 14px; color: #1f1f1f; padding-top: 8px;">{{ $approvedAt }}</td>
                                </tr>
                                @endif
                                @if(!empty($expirationAt))
                                <tr>
                                    <td style="font-size: 13px; color: #7a7a7a; padding-top: 8px;">Expiration date</td>
                                    <td style="font-size: 14px; color: #1f1f1f; padding-top: 8px;">{{ $expirationAt }}</td>
                                </tr>
                                @endif
                                @if(!empty($approvedBy))
                                <tr>
                                    <td style="font-size: 13px; color: #7a7a7a; padding-top: 8px;">Approved by</td>
                                    <td style="font-size: 14px; color: #1f1f1f; padding-top: 8px;">{{ $approvedBy }}</td>
                                </tr>
                                @endif
                            </table>

                            <div style="margin: 20px 0 0;">
                                <p style="margin: 0 0 14px; font-size: 14px; color: #444444;">
                                    Next steps:
                                </p>
                                <ul style="margin: 0; padding-left: 18px; color: #444444; font-size: 14px; line-height: 1.6;">
                                    <li>Open the 3K Fitness app to browse classes.</li>
                                    <li>Use your QR code for fast check-ins.</li>
                                    <li>Track your attendance and progress inside your profile.</li>
                                </ul>
                            </div>

                            <div style="margin: 24px 0 0;">
                                <span style="display: inline-block; background-color: #a40000; color: #ffffff; font-size: 14px; font-weight: 700; padding: 10px 18px; border-radius: 10px;">
                                    Welcome to the 3K Fitness community
                                </span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 18px 28px 28px; border-top: 1px solid #eeeeee; color: #6b7280; font-size: 12px;">
                            If you have questions, reply to this email and our team will be happy to help.
                            <br>
                            Thank you,<br>3K Fitness Team
                        </td>
                    </tr>
                </table>
                <div style="font-size: 11px; color: #9ca3af; margin-top: 12px;">
                    This is an automated message. Please do not share your account credentials.
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
