module.exports = {
  apps: [{
    name: 'face-verification-app',
    script: 'php',
    args: 'artisan serve --host=0.0.0.0 --port=8000',
    cwd: '/home/fata/Face-Verification-Presence',
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
    log_file: '/home/fata/Face-Verification-Presence/storage/logs/pm2-combined.log',
    out_file: '/home/fata/Face-Verification-Presence/storage/logs/pm2-out.log',
    error_file: '/home/fata/Face-Verification-Presence/storage/logs/pm2-error.log',
    log_date_format: 'YYYY-MM-DD HH:mm Z',
    merge_logs: true,
    time: true
  }]
};
