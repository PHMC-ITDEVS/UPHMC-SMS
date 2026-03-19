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


import { defineConfig, loadEnv } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'
import eslintPlugin from 'vite-plugin-eslint'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const appUrl = env.APP_URL || 'http://localhost'
  const appOrigin = new URL(appUrl)
  const devServerHost = env.VITE_DEV_SERVER_HOST || appOrigin.hostname
  const devServerPort = Number(env.VITE_DEV_SERVER_PORT || 5173)
  const devServerProtocol = env.VITE_DEV_SERVER_HTTPS === 'true' ? 'https' : 'http'
  const hmrProtocol = devServerProtocol === 'https' ? 'wss' : 'ws'
  const devServerOrigin = `${devServerProtocol}://${devServerHost}:${devServerPort}`

  return {
    plugins: [
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
        '@': '/resources/js',
      },
    },
    server: {
      host: '0.0.0.0',
      port: devServerPort,
      strictPort: true,
      origin: devServerOrigin,
      cors: {
        origin: appUrl,
      },
      hmr: {
        host: devServerHost,
        protocol: hmrProtocol,
        port: devServerPort,
      },
    },
  }
})
