Hello {{ $emailData['patient_name'] }},

You have been invited to join the {{ $emailData['business_name'] }} patient portal.

Please click the link below to complete your registration:
{{ $emailData['registration_url'] }}

@if(!empty($emailData['personal_message']))
Personal Message:
{{ $emailData['personal_message'] }}
@endif

This invitation will expire on {{ $emailData['expires_at'] }}.

If you have any questions, please contact {{ $emailData['business_name'] }}.

Thank you,
{{ $emailData['business_name'] }} Team
