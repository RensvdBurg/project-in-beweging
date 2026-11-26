import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { nativephpMobile, nativephpHotFile } from './vendor/nativephp/mobile/resources/js/vite-plugin.js';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/footer-nav.js',
                'resources/js/login.js',
                'resources/js/home.js',
                'resources/js/settings-page.js',
                'resources/js/account-page.js',
                'resources/js/activities.js',
                'resources/js/community.js',
                'resources/js/learn.js',
                'resources/css/learn-page-transition.css',
            ],
            refresh: true,
            hotFile: nativephpHotFile(),
            // Montserrat wordt bij het bouwen gedownload en lokaal meegebundeld,
            // zodat de app ook zonder internet het juiste lettertype toont.
            fonts: [
                google('Montserrat', {
                    weights: [400, 500, 600, 700],
                    optimizedFallbacks: false,
                }),
            ],
        }),
        tailwindcss(),
        nativephpMobile(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
