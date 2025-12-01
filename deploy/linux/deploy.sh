#!/bin/bash

# Face Verification Presence - Full Deployment Script

set -e  # Exit on any error

echo "🚀 Starting full deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Resolve paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
print_status "Project root detected: $PROJECT_ROOT"

# Change to project root for all operations
cd "$PROJECT_ROOT"

# Check and install requirements
print_status "Checking system requirements..."

# Check Node.js
if ! command -v node &> /dev/null; then
    print_error "Node.js is not installed"
    echo "Installing Node.js..."
    curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
    sudo apt-get install -y nodejs
    print_success "Node.js installed successfully"
else
    NODE_VERSION=$(node --version | cut -d'v' -f2 | cut -d'.' -f1)
    if [ "$NODE_VERSION" -lt 18 ]; then
        print_warning "Node.js version is outdated. Recommended: v18+"
    fi
    print_success "Node.js $(node --version) found"
fi

# Check npm
if ! command -v npm &> /dev/null; then
    print_error "npm is not installed"
    sudo apt-get install -y npm
    print_success "npm installed successfully"
else
    print_success "npm $(npm --version) found"
fi

# Check PHP
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed"
    echo "Installing PHP and required extensions..."
    sudo apt-get update
    sudo apt-get install -y php php-cli php-fpm php-mysql php-xml php-zip php-mbstring php-curl php-bcmath php-gd php-json php-tokenizer
    print_success "PHP and extensions installed successfully"
else
    PHP_VERSION=$(php --version | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)
    print_success "PHP $PHP_VERSION found"
fi

# Check Composer
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed"
    echo "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
    print_success "Composer installed successfully"
else
    print_success "Composer $(composer --version | cut -d' ' -f3) found"
fi

# Check PM2
if ! command -v pm2 &> /dev/null; then
    print_error "PM2 is not installed"
    echo "Installing PM2..."
    sudo npm install -g pm2
    print_success "PM2 installed successfully"
else
    print_success "PM2 $(pm2 --version | head -n1) found"
fi

# Check Git
if ! command -v git &> /dev/null; then
    print_error "Git is not installed"
    sudo apt-get install -y git
    print_success "Git installed successfully"
else
    print_success "Git $(git --version | cut -d' ' -f3) found"
fi

print_success "✅ All requirements satisfied!"

# Install/update Node.js dependencies
print_status "Installing Node.js dependencies..."
npm install

# Build frontend assets
print_status "Building frontend assets..."
npm run build

# Install/update PHP dependencies
print_status "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Clear Laravel caches
print_status "Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Optimize for production
print_status "Optimizing application for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Stop existing PM2 process if it exists
if pm2 list | grep -q "face-verification-app"; then
    print_status "Stopping existing PM2 process..."
    pm2 stop face-verification-app
    pm2 delete face-verification-app
fi

# Update ecosystem config with project root directory
print_status "Updating PM2 ecosystem configuration with project root: $PROJECT_ROOT"

# Create ecosystem config with dynamic path in deploy/linux folder
cat > "$SCRIPT_DIR/ecosystem.config.cjs" << EOF
module.exports = {
  apps: [{
    name: 'face-verification-app',
    script: 'php',
    args: 'artisan serve --host=0.0.0.0 --port=8000',
    cwd: '$PROJECT_ROOT',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production'
    },
    env_production: {
      NODE_ENV: 'production'
    },
    log_file: '$PROJECT_ROOT/storage/logs/pm2-combined.log',
    out_file: '$PROJECT_ROOT/storage/logs/pm2-out.log',
    error_file: '$PROJECT_ROOT/storage/logs/pm2-error.log',
    log_date_format: 'YYYY-MM-DD HH:mm Z',
    merge_logs: true,
    time: true
  }]
};
EOF

# Start application with PM2
print_status "Starting application with PM2..."
pm2 start "$SCRIPT_DIR/ecosystem.config.cjs"

# Save PM2 configuration
pm2 save

# Setup PM2 startup script (run only once)
print_status "Setting up PM2 startup script..."
pm2 startup | tail -n 1 | bash 2>/dev/null || echo "PM2 startup already configured"

# Show PM2 status
print_status "Current PM2 status:"
pm2 status

print_success "🎉 Deployment completed successfully!"
print_status "Your application is now running at: http://0.0.0.0:8000"
print_status "To view logs: pm2 logs face-verification-app"
print_status "To restart: pm2 restart face-verification-app"
