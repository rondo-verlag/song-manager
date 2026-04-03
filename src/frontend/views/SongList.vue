<template>
	<div class="container-fluid">
		<div class="mt-2">
			<h1>Rondo Lieder</h1>
			<div>
				<a href="/api/export/songs.xlsx" target="_blank" class="btn btn-secondary me-2">Lieder als Excel exportieren</a>
				<div class="d-inline-block me-2">
					<a class="btn btn-outline-secondary dropdown-toggle" @click="isExportDropdownOpen = !isExportDropdownOpen" role="button">
						Exportieren
					</a>
					<ul class="dropdown-menu" :class="{ show: isExportDropdownOpen }" @click="isExportDropdownOpen = false">
						<li><a class="dropdown-item" href="/api/export/songs.csv" target="_blank">CSV Export</a></li>
						<li><a class="dropdown-item" href="/api/export/indesign.zip" target="_blank">InDesign ZIP</a></li>
						<li><a class="dropdown-item" href="/api/export/bookindex.csv" target="_blank">Buch Inhaltsverzeichnis als CSV</a></li>
						<li><hr class="dropdown-divider"></li>
						<div class="px-3 py-1 text-muted">App</div>
						<li><a class="dropdown-item" href="/api/export/zip" target="_blank">App Daten als ZIP exportieren</a></li>
						<li><a class="dropdown-item" href="/api/export/html" target="_blank">HTML &amp; Bilder</a></li>
						<li><a class="dropdown-item" href="/api/export/index" target="_blank">App Index</a></li>
					</ul>
				</div>
				<a href="/api/validate2024" target="_blank" class="btn btn-outline-secondary me-2">Fehlerprüfung 2024</a>
				<div class="d-inline-block me-2">
					<a class="btn btn-outline-secondary dropdown-toggle" @click="isMoreDropdownOpen = !isMoreDropdownOpen" role="button">
						Mehr
					</a>
					<ul class="dropdown-menu" :class="{ show: isMoreDropdownOpen }" @click="isMoreDropdownOpen = false">
						<li><a class="dropdown-item" href="/api/export/listchords" target="_blank">Alle Akkorde auflisten</a></li>
						<li><a class="dropdown-item" href="#/add" target="_blank">Lied hinzufügen</a></li>
					</ul>
				</div>
			</div>

			<div class="row mt-3">
				<div class="col">
					<input type="text" class="form-control" v-model="search" placeholder="Suchen...">
				</div>
			</div>

			<form class="d-flex flex-row align-items-center mt-2 flex-wrap">
				<div style="color: #555" class="me-2">Nach Ausgabe filtern:</div>
				<div class="checkbox ausgabe-filter" :class="{ 'ausgabe-filter--active': filter2017Active }">
					<label><input type="checkbox" v-model="filter2017Active"> 2017</label>
				</div>
				<div class="checkbox ausgabe-filter" :class="{ 'ausgabe-filter--active': filter2021Active }">
					<label><input type="checkbox" v-model="filter2021Active"> mova</label>
				</div>
				<div class="checkbox ausgabe-filter" :class="{ 'ausgabe-filter--active': filterAppActive }">
					<label><input type="checkbox" v-model="filterAppActive"> App</label>
				</div>
				<div class="checkbox ausgabe-filter" :class="{ 'ausgabe-filter--active': filter2024Active }">
					<label><input type="checkbox" v-model="filter2024Active"> 2024</label>
				</div>
			</form>

			<br />

			<div class="table-responsive">
				<table class="table table-striped table-sm">
					<thead>
						<tr>
							<th @click="setOrder('id')" style="cursor:pointer">ID</th>
							<th @click="setOrder('title')" style="cursor:pointer">Titel</th>
							<th @click="setOrder('interpret')" style="cursor:pointer">Interpret</th>
							<th @click="setOrder('license')" style="cursor:pointer">Lizenz</th>
							<th @click="setOrder('status')" style="cursor:pointer">Status</th>
							<th @click="setOrder('hasImage')" style="cursor:pointer">Bild</th>
							<th @click="setOrder('hasNotesPDF')" style="cursor:pointer">Noten</th>
							<th @click="setOrder('hasMidi')" style="cursor:pointer">Midi</th>
							<th @click="setOrder('copyrightStatusApp')" style="cursor:pointer">&copy; App</th>
							<th @click="setOrder('copyrightStatusBook2024')" style="cursor:pointer">&copy; Buch2024</th>
							<th @click="setOrder('copyrightStatusBook2021')" style="cursor:pointer">&copy; BuchMova</th>
							<th @click="setOrder('releaseApp2024')" style="cursor:pointer">App</th>
							<th @click="setOrder('releaseBook2024')" style="cursor:pointer">Buch2024</th>
							<th @click="setOrder('releaseBook2021')" style="cursor:pointer">mova</th>
							<th @click="setOrder('releaseBook2017')" style="cursor:pointer">Buch2017</th>
							<th @click="setOrder('licenseAppUntil')" style="cursor:pointer">App Lizenz</th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="song in filteredSongs" :key="song.id" @dblclick="editSong(song.id)">
							<td>{{ song.id }}</td>
							<td>{{ song.title }}</td>
							<td>{{ song.interpret }}</td>
							<td><LicenseBadge :license="song.license" /></td>
							<td><StatusBadge :status="song.status" /></td>
							<td><YesNo :state="song.hasImage ?? 0" /></td>
							<td><YesNo :state="song.hasNotesPDF ?? 0" /></td>
							<td><YesNo :state="song.hasMidi ?? 0" /></td>
							<td>
								<StatusBadge v-if="song.license !== 'FREE'" :status="song.copyrightStatusApp ?? ''" />
								<span v-else style="color: green;">-</span>
							</td>
							<td>
								<StatusBadge v-if="song.license !== 'FREE'" :status="song.copyrightStatusBook2024 ?? ''" />
								<span v-else style="color: green;">-</span>
							</td>
							<td>
								<StatusBadge v-if="song.license !== 'FREE'" :status="song.copyrightStatusBook2021 ?? ''" />
								<span v-else style="color: green;">-</span>
							</td>
							<td><YesNo :state="song.releaseApp2024 ?? 0" /></td>
							<td><YesNo :state="song.releaseBook2024 ?? 0" /></td>
							<td><YesNo :state="song.releaseBook2021 ?? 0" /></td>
							<td><YesNo :state="song.releaseBook2017 ?? 0" /></td>
							<td class="text-nowrap">{{ song.licenseAppUntil }}</td>
							<td><button type="button" class="btn btn-sm btn-success" @click="editSong(song.id)">Bearbeiten</button></td>
						</tr>
					</tbody>
				</table>
			</div>
			<p class="text-muted">{{ filteredSongs.length }} Lieder gefunden</p>
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import type { Song } from '../types/Song'
import StatusBadge from '../components/StatusBadge.vue'
import LicenseBadge from '../components/LicenseBadge.vue'
import YesNo from '../components/YesNo.vue'

export default defineComponent({
	name: 'SongList',
	components: { StatusBadge, LicenseBadge, YesNo },
	setup() {
		const router = useRouter()
		const list = ref<Song[]>([])
		const search = ref('')
		const LS_KEY = 'songlist_prefs'
		function loadPrefs() {
			try { return JSON.parse(localStorage.getItem(LS_KEY) ?? '{}') } catch { return {} }
		}
		const prefs = loadPrefs()

		const orderBy = ref<keyof Song>(prefs.orderBy ?? 'title')
		const orderReversed = ref<boolean>(prefs.orderReversed ?? false)
		const filter2017Active = ref<boolean>(prefs.filter2017Active ?? false)
		const filter2021Active = ref<boolean>(prefs.filter2021Active ?? false)
		const filter2024Active = ref<boolean>(prefs.filter2024Active ?? false)
		const filterAppActive = ref<boolean>(prefs.filterAppActive ?? false)
		const isExportDropdownOpen = ref(false)
		const isMoreDropdownOpen = ref(false)

		function savePrefs() {
			localStorage.setItem(LS_KEY, JSON.stringify({
				orderBy: orderBy.value,
				orderReversed: orderReversed.value,
				filter2017Active: filter2017Active.value,
				filter2021Active: filter2021Active.value,
				filter2024Active: filter2024Active.value,
				filterAppActive: filterAppActive.value,
			}))
		}

		watch([orderBy, orderReversed, filter2017Active, filter2021Active, filter2024Active, filterAppActive], savePrefs)

		function setOrder(field: keyof Song) {
			if (orderBy.value === field) {
				orderReversed.value = !orderReversed.value
			} else {
				orderBy.value = field
				orderReversed.value = false
			}
		}

		function releaseFilter(song: Song): boolean {
			if (filter2017Active.value || filter2021Active.value || filter2024Active.value || filterAppActive.value) {
				let show = false
				if (filter2017Active.value && song.releaseBook2017 == 1) show = true
				if (filter2021Active.value && song.releaseBook2021 == 1) show = true
				if (filter2024Active.value && song.releaseBook2024 == 1) show = true
				if (filterAppActive.value && song.releaseApp2024 == 1) show = true
				return show
			}
			return true
		}

		const filteredSongs = computed(() => {
			let result = list.value.filter(releaseFilter)
			const q = search.value.toLowerCase()
			if (q) {
				result = result.filter(s =>
					(s.title ?? '').toLowerCase().includes(q) ||
					(s.interpret ?? '').toLowerCase().includes(q)
				)
			}
			const key = orderBy.value
			result = [...result].sort((a, b) => {
				const av = a[key] ?? ''
				const bv = b[key] ?? ''
				const cmp = String(av).localeCompare(String(bv), undefined, { numeric: true })
				return orderReversed.value ? -cmp : cmp
			})
			return result
		})

		function editSong(id: string) {
			router.push('/songs/' + id)
		}

		onMounted(() => {
			fetch('/api/songs')
				.then(r => r.json())
				.then((data: Song[]) => { list.value = data })
				.catch(() => console.error('AJAX failed!'))
		})

		return {
			search, orderBy, orderReversed,
			filter2017Active, filter2021Active, filter2024Active, filterAppActive,
			isExportDropdownOpen, isMoreDropdownOpen,
			filteredSongs, setOrder, editSong
		}
	}
})
</script>
