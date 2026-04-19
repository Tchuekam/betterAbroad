import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/DATABASE': {
        target: 'http://localhost/BETTERABROAD',
        changeOrigin: true,
      },
    },
  },
});
