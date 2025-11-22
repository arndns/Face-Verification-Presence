import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import os from 'os';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const devServerPort = Number(env.VITE_DEV_SERVER_PORT ?? 5173);
    const devServerHost = resolveDevServerHost(env.VITE_DEV_SERVER_HOST);

    return {
        server: {
            host: devServerHost,
            port: devServerPort,
            strictPort: true,
            hmr: {
                host: devServerHost,
                port: devServerPort,
            },
        },
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                    'resources/css/login.css',
                    'resources/js/login.js',
                    'resources/css/employee.css',
                    'resources/js/employee.js',
                    'resources/css/admin.css',
                    'resources/js/admin.js',
                ],
                refresh: true,
            }),
            tailwindcss(),
        ],
    };
});

function resolveDevServerHost(configuredHost) {
    const trimmed = configuredHost?.trim();
    if (trimmed) {
        return trimmed;
    }

    const interfaces = os.networkInterfaces();
    for (const name of Object.keys(interfaces)) {
        for (const details of interfaces[name] ?? []) {
            if (details && details.family === 'IPv4' && !details.internal) {
                return details.address;
            }
        }
    }

    return '127.0.0.1';
}
