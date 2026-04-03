import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
	plugins: [vue()],
	root: 'src/frontend',
	base: '/frontend/dist/',
	publicDir: false,
	build: {
		outDir: 'dist',
		emptyOutDir: true,
		copyPublicDir: false,
		rollupOptions: {
			output: {
				entryFileNames: 'main.js',
				chunkFileNames: '[name].js',
				assetFileNames: '[name].[ext]'
			}
		}
	},
	server: {
		proxy: {
			'/api': {
				// Local path, change if yours is different
				target: 'http://localhost/projects/song-manager/src/',
				changeOrigin: true
			}
		}
	}
})
