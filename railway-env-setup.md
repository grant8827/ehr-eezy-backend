# Railway Environment Variables Setup for Email

## Issue
The production environment is trying to authenticate with Gmail SMTP servers instead of your custom SMTP server.

## Solution Options

### Option 1: Use Your Custom SMTP Server (Recommended)
Update these Railway environment variables:

```
MAIL_MAILER=smtp
MAIL_HOST=webhosting2023.is.cc
MAIL_PORT=465
MAIL_USERNAME=info@safehavenrestorationministries.com
MAIL_PASSWORD=Safehaven2025
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=info@safehavenrestorationministries.com
MAIL_FROM_NAME=EHReezy
```

### Option 2: Use Gmail with App Password (Alternative)
If you prefer Gmail, you'll need to:
1. Enable 2-Factor Authentication on the Gmail account
2. Generate an App Password
3. Use these settings:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-gmail@gmail.com
MAIL_FROM_NAME=EHReezy
```

## How to Update Railway Environment Variables

1. Go to https://railway.app/dashboard
2. Select your project (ehr-eezy backend)
3. Go to Variables tab
4. Add/Update the MAIL_* variables listed above
5. Deploy the changes

## Testing
After updating, test the email functionality by sending a patient invitation.
