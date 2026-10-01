import { defineConfig } from 'vite';
import { fileURLToPath } from 'url';
import tailwindcss from '@tailwindcss/vite';

/*
 * Build workspace IS the theme directory — the Docker mount is
 * ./themes:/var/www/html/wp-content/themes, so there is no parent workspace to
 * build from. Output lands in assets/dist and is read back by viteAsset() in
 * app/includes/html.php via the generated manifest.
 */
export default defineConfig({
  plugins: [tailwindcss()],
  base: './',
  resolve: {
    alias: {
      // Mirrors the Storybook aliases so ported JS imports resolve unchanged.
      '@assets': fileURLToPath(new URL('./assets', import.meta.url)),
      '@assets/utils': fileURLToPath(new URL('./assets/js/utils', import.meta.url)),
      '@motion': fileURLToPath(new URL('./assets/js/motion', import.meta.url)),
    },
  },
  build: {
    outDir: 'assets/dist',
    emptyOutDir: true,
    target: 'es2022',
    manifest: true,
    rollupOptions: {
      input: {
        main: 'assets/css/main.css',
        app: 'assets/js/app.js',
      },
      output: {
        format: 'es',
        entryFileNames: '[name]-[hash].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: '[name]-[hash][extname]',
      },
    },
  },
});
