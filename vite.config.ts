import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [
    laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.jsx',
                'resources/js/home.tsx',
                'resources/js/navbar.tsx',
                'resources/js/shops.tsx',
                'resources/js/shop-dashboard.tsx',
                'resources/js/offers.tsx',
                'resources/js/offer-new.tsx',
                'resources/js/offer-detail.tsx',
                'resources/js/offer-add-manifest.tsx',
                'resources/js/offer-profitability.tsx',
                'resources/js/offer-metafields.tsx',
                'resources/js/offer-manifests.tsx',
                'resources/js/admin-users.tsx',
                'resources/js/admin-user-detail.tsx',
                'resources/js/admin-store-detail.tsx',
            ],
      refresh: true,
    }),
    react(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js'),
      'react': path.resolve(__dirname, 'node_modules/react'),
      'react-dom': path.resolve(__dirname, 'node_modules/react-dom'),
    },
  },
  build: {
    rollupOptions: {
      external: (id) => /\.test\.[tj]sx?$/.test(id) || id.includes('/__tests__/'),
      output: {
        manualChunks: {
          vendor: ['react', 'react-dom'],
          'ui-core': ['@radix-ui/react-slot', 'class-variance-authority', 'clsx', 'tailwind-merge'],
          'ui-components': [
            '@radix-ui/react-dialog',
            '@radix-ui/react-alert-dialog',
            '@radix-ui/react-popover',
            '@radix-ui/react-tabs',
            '@radix-ui/react-label',
            '@radix-ui/react-checkbox',
            '@/components/ui/alert-dialog',
            '@/components/ui/alert',
            '@/components/ui/badge',
            '@/components/ui/breadcrumb',
            '@/components/ui/button',
            '@/components/ui/calendar',
            '@/components/ui/card',
            '@/components/ui/checkbox',
            '@/components/ui/dialog',
            '@/components/ui/form',
            '@/components/ui/input',
            '@/components/ui/label',
            '@/components/ui/popover',
            '@/components/ui/skeleton',
            '@/components/ui/spinner',
            '@/components/ui/table',
            '@/components/ui/tabs',
            '@/components/ui/textarea',
            '@/components/ui/tooltip'
          ],
        }
      }
    }
  }
});
