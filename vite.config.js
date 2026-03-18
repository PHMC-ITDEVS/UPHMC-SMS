// import { defineConfig } from 'vite';
// import laravel from 'laravel-vite-plugin';
// import vue from '@vitejs/plugin-vue';

// export default defineConfig({
//     plugins: [
//         laravel([
//             // 'resources/css/app.css',
//             'resources/js/app.js',
//         ]),
//         vue({
//             template: {
//                 transformAssetUrls: {
//                     base: null,
//                     includeAbsolute: false,
//                 },
//             },
//         }),
//     ],
//     resolve: {
//         alias: {
//             '@': '/resources/js'
//         }
//     }
// });


import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'
import eslintPlugin from 'vite-plugin-eslint'

export default defineConfig({
  plugins: [
    // laravel({
    //     input: 'resources/js/app.js',
    //     refresh: true,
    // }),
    laravel([
        'resources/css/app.css',
        'resources/js/app.js',
    ]),
    vue({
        template: {
            transformAssetUrls: {
                base: null,
                includeAbsolute: false,
            },
        },
    }),
    // vuetify({ autoImport: true }),
    // eslintPlugin(),
  ],
  resolve: {
        extensions: ['.js', '.vue', '.scss', '.css'],
        alias: {
            '@': '/resources/js'
        }
    },
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    origin: 'http://uphmc-sms.test:5173',
    cors: {
      origin: 'http://uphmc-sms.test',
    },
    hmr: {
      host: 'uphmc-sms.test',
      protocol: 'ws',
      port: 5173,
    },
  },
})
