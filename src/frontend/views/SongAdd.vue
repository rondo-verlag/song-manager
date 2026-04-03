<template>
	<div class="container-fluid">
		<div class="row">
			<div class="col-sm-8">
				<h1>Song hinzufügen</h1>
				<div v-if="showLoading">
					Song hinzufügen ...
				</div>
				<div v-else>
					<form class="form-horizontal" @submit.prevent="add">
						<input type="text" v-model="song.title" class="form-control" placeholder="Titel">
						<br>
						<input type="text" v-model="song.interpret" class="form-control" placeholder="Interpret">
						<br>
						<button type="submit" class="btn btn-success">Hinzufügen</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent, ref, reactive } from 'vue'

export default defineComponent({
	name: 'SongAdd',
	setup() {
		const song = reactive({ title: '', interpret: '' })
		const showLoading = ref(false)

		function add() {
			if (song.title !== '') {
				showLoading.value = true
				fetch('/api/songs', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(song)
				})
					.then(() => {
						showLoading.value = false
						song.title = ''
						song.interpret = ''
					})
					.catch(err => {
						console.error('AJAX failed!', err)
						showLoading.value = false
					})
			}
		}

		return { song, showLoading, add }
	}
})
</script>
