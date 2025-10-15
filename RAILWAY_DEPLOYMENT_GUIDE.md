# EHReezy Backend - Railway Deployment Guide

## Prerequisites
- Railway account: https://railway.app
- GitHub repository with your backend code
- MySQL database service on Railway

## Step-by-Step Deployment

### 1. Access Railway Dashboard
- Go to https://railway.app/dashboard
- Login to your Railway account

### 2. Create New Project (if not exists)
1. Click "New Project"
2. Select "Deploy from GitHub repo"
3. Connect your GitHub account if not connected
4. Select your backend repository

### 3. Configure Environment Variables
In your Railway project dashboard, go to Variables tab and add:

```
APP_NAME=EHReezy
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:1S3zr3UGfh2CO9XKYi5MzFXO98w82GrEeukg6H7HC4k=
DB_CONNECTION=mysql
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=info@safehavenrestorationministries.com
MAIL_PASSWORD=[YOUR_EMAIL_PASSWORD]
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@safehavenrestorationministries.com
MAIL_FROM_NAME=EHReezy
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:5173
LOG_CHANNEL=stack
LOG_LEVEL=info
SEED_DATABASE=true
```

### 4. Add MySQL Database
1. In your project, click "Add Service"
2. Select "MySQL"
3. Railway will automatically create database variables:
   - MYSQLHOST
   - MYSQLPORT
   - MYSQLDATABASE
   - MYSQLUSER
   - MYSQLPASSWORD

### 5. Configure Build Settings
Your project should automatically detect the Laravel app using the files:
- `nixpacks.toml` - Build configuration
- `railway.json` - Service configuration
- `composer.json` - PHP dependencies

### 6. Deploy
1. Push your code to GitHub
2. Railway will automatically deploy
3. Check the deployment logs for any errors

### 7. Get Your Backend URL
- After successful deployment, Railway will provide a URL like:
- `https://[your-service-name].up.railway.app`

### 8. Update Frontend Configuration
Update your frontend .env file with the Railway backend URL:
```
VITE_API_URL=https://[your-service-name].up.railway.app/api
```

## Troubleshooting

### Common Issues:
1. **Build Fails**: Check composer.json and PHP version in nixpacks.toml
2. **Database Connection**: Verify MySQL service is running and variables are set
3. **Environment Variables**: Ensure all required variables are set in Railway dashboard
4. **CORS Issues**: Update SANCTUM_STATEFUL_DOMAINS with your frontend domain

### Checking Logs:
- In Railway dashboard, go to your service
- Click "Logs" tab to see deployment and runtime logs
- Look for PHP errors or database connection issues

## Files Included in This Repository:
- `nixpacks.toml` - Nixpacks build configuration for PHP/Laravel
- `railway.json` - Railway service configuration
- `deploy.sh` - Custom deployment script
- `.env.railway` - Template environment variables for Railway

## Next Steps After Deployment:
1. Test API endpoints: `https://your-app.up.railway.app/api/health`
2. Update frontend to use production backend URL
3. Test authentication and all features
4. Monitor logs for any issues
