module.exports = {
  apps: [{
    name: 'zmc-website',
    script: './index.cjs',
    cwd: '/home/zmc/app',
    instances: 1,
    exec_mode: 'fork',
    env: {
      NODE_ENV: 'production',
      PORT: 3000,
      DATABASE_URL: process.env.DATABASE_URL || ''
    },
    error_file: '/home/zmc/app/logs/error.log',
    out_file: '/home/zmc/app/logs/out.log',
    log_file: '/home/zmc/app/logs/combined.log',
    time: true,
    max_memory_restart: '500M',
    restart_delay: 3000,
    watch: false
  }]
};