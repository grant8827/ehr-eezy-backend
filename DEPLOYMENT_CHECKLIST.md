# EHReezy Backend Deployment Checklist

## ✅ Pre-Deployment Checklist

### 1. Files Ready for Deployment
- [x] `composer.json` - PHP dependencies
- [x] `nixpacks.toml` - Build configuration  
- [x] `railway.json` - Service configuration
- [x] `deploy.sh` - Deployment script
- [x] `.env.railway` - Environment template
- [x] Health check endpoint at `/api/health`

### 2. Required Environment Variables for Railway
Copy these to Railway Dashboard > Your Service > Variables tab:

**Application Settings:**
```
APP_NAME=EHReezy
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:1S3zr3UGfh2CO9XKYi5MzFXO98w82GrEeukg6H7HC4k=
```

**Database (Auto-configured when you add MySQL service):**
```
DB_CONNECTION=mysql
```
The following will be auto-populated by Railway MySQL service:
- DB_HOST → ${{MYSQLHOST}}
- DB_PORT → ${{MYSQLPORT}}
- DB_DATABASE → ${{MYSQLDATABASE}}
- DB_USERNAME → ${{MYSQLUSER}}
- DB_PASSWORD → ${{MYSQLPASSWORD}}

**Session & Cache:**
```
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
CACHE_STORE=database
QUEUE_CONNECTION=database
```

**Email Configuration:**
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=info@safehavenrestorationministries.com
MAIL_PASSWORD=[YOUR_APP_PASSWORD]
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@safehavenrestorationministries.com
MAIL_FROM_NAME=EHReezy
```

**CORS & Security:**
```
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:5173
LOG_CHANNEL=stack
LOG_LEVEL=info
SEED_DATABASE=true
```

## 🚀 Deployment Steps

### Step 1: Upload to GitHub
1. Go to https://github.com
2. Create new repository "ehreezy-backend" 
3. Upload all backend files via web interface

### Step 2: Deploy to Railway
1. Go to https://railway.app/dashboard
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose your "ehreezy-backend" repository

### Step 3: Add MySQL Database
1. In Railway project, click "Add Service"
2. Select "MySQL"
3. Wait for database to initialize

### Step 4: Configure Environment Variables
1. Go to your backend service in Railway
2. Click "Variables" tab
3. Add all environment variables from above

### Step 5: Deploy & Monitor
1. Railway will automatically deploy
2. Check "Logs" tab for deployment progress
3. Look for successful Laravel application start

## 🔍 Testing Deployment

### Health Check
Once deployed, test your backend:
```
https://your-service-name.up.railway.app/api/health
```

Should return:
```json
{
  "status": "ok",
  "timestamp": "2024-01-15T10:30:00.000000Z",
  "app": "EHReezy",
  "environment": "production",
  "database": "connected"
}
```

### API Endpoints
Test these endpoints:
- `/api/health` - Health check
- `/api/auth/login` - Authentication
- `/api/dashboard/stats` - Protected route

## 🔧 Troubleshooting

### Common Issues:
1. **Build fails**: Check PHP version in `nixpacks.toml`
2. **Database connection**: Verify MySQL service is running
3. **Environment variables**: Ensure all variables are set correctly
4. **CORS errors**: Update SANCTUM_STATEFUL_DOMAINS

### Next Steps After Deployment:
1. Update frontend `.env` with Railway backend URL:
   ```
   VITE_API_URL=https://your-service-name.up.railway.app/api
   ```
2. Test all API endpoints from frontend
3. Monitor Railway logs for any issues
