import { createRouter, createWebHashHistory } from 'vue-router'
import SongList from '../views/SongList.vue'
import SongDetail from '../views/SongDetail.vue'
import SongAdd from '../views/SongAdd.vue'

const router = createRouter({
	history: createWebHashHistory(),
	routes: [
		{ path: '/', redirect: '/songs' },
		{ path: '/songs', component: SongList },
		{ path: '/songs/:songId', component: SongDetail },
		{ path: '/add', component: SongAdd }
	]
})

export default router
