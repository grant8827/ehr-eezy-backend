#!/bin/bash

echo "🚀 Starting EHReezy deployment..."

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Clear and cache configuration
echo "⚙️ Optimizing Laravel..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Seed database if needed (only on first deploy)
if [ "$SEED_DATABASE" = "true" ]; then
    echo "🌱 Seeding database..."
    php artisan db:seed --class=BusinessSeeder --force
fi

echo "✅ Deployment completed successfully!"

# Start the application
echo "🌐 Starting application server..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
