<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal Invitation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $emailData['business_name'] }}</h1>
        <p>Patient Portal Invitation</p>
    </div>

    <div class="content">
        <p>Dear {{ $emailData['patient_name'] }},</p>

        <p>You have been invited to access our patient portal where you can:</p>
        <ul>
            <li>View your medical records</li>
            <li>Schedule appointments</li>
            <li>View billing information</li>
            <li>Communicate with our staff</li>
        </ul>

        @if(isset($emailData['personal_message']) && $emailData['personal_message'])
        <div style="background-color: white; padding: 15px; border-left: 4px solid #2563eb; margin: 20px 0;">
            <p><strong>Personal Message:</strong></p>
            <p>{{ $emailData['personal_message'] }}</p>
        </div>
        @endif

        <p>To complete your registration, please click the button below:</p>

        <a href="{{ $emailData['registration_url'] }}" class="button">Complete Registration</a>

        <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
        <p><a href="{{ $emailData['registration_url'] }}">{{ $emailData['registration_url'] }}</a></p>
        
        <p><strong>Important:</strong> This invitation will expire on {{ $emailData['expires_at'] }}.</p>

        <div class="footer">
            <p>If you have any questions, please contact us.</p>
            <p>Best regards,<br>{{ $emailData['business_name'] }} Team</p>
            <p><small>Sent on {{ $emailData['sent_at'] }}</small></p>
        </div>
    </div>
</body>
</html>
