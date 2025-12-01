#!/bin/bash

# Face Verification Presence - Quick Update Script

set -e  # Exit on any error

echo "⚡ Starting quick update..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
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

# Resolve paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
print_status "Project root detected: $PROJECT_ROOT"

# Change to project root for all operations
cd "$PROJECT_ROOT"

# Build frontend assets
print_status "Building frontend assets..."
npm run build

# Clear Laravel config cache
print_status "Clearing Laravel config cache..."
php artisan config:clear

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

# Restart PM2 process
print_status "Restarting PM2 process..."
pm2 restart face-verification-app

print_success "⚡ Quick update completed successfully!"
print_status "Application has been restarted with latest changes"
