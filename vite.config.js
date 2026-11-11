import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // Inter: main interface font (English)
                bunny('Inter', {
                    weights: [400, 500, 600, 700],
                }),
                // Hind Siliguri: Bangla text
                bunny('Hind Siliguri', {
                    weights: [400, 500, 600],
                    subsets: ['bengali'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
