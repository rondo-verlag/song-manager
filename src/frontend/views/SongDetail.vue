<template>
	<div class="container-fluid">
		<div class="row" v-if="song">
			<div class="col-sm-8">
				<form class="form-horizontal" @submit.prevent="save">
					<div class="mb-3">
						<div class="row">
							<div class="col-sm-3 col-form-label text-end mt-3">
								<button type="button" class="btn btn-sm btn-outline-secondary" @click="router.push('/songs/' + prevSongId)"><i class="fa fa-chevron-left"></i></button>
								&nbsp;
								<button type="button" class="btn btn-sm btn-outline-secondary" @click="router.push('/songs/' + nextSongId)"><i class="fa fa-chevron-right"></i></button>
								<div class="mt-2">
									<router-link to="/songs">&laquo; zurück zur Liste</router-link>
								</div>
							</div>
							<div class="col-sm-9">
								<h1 class="mt-5">{{ song.title }}&nbsp; <StatusBadge :status="song.status" style="font-size: 14px" /></h1>
							</div>
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row has-feedback">
						<label for="inputTitle" class="col-md-3 col-form-label text-end">Titel</label>
						<div class="col-md-9">
							<div class="input-group">
								<input type="text" v-model="song.title" class="form-control" id="inputTitle" placeholder="Titel" />
							</div>
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row has-feedback">
						<label for="alternativeTitles" class="col-md-3 col-form-label text-end">Weitere Titel</label>
						<div class="col-md-9">
							<div class="input-group">
								<textarea id="alternativeTitles" class="form-control" rows="2" v-model="song.alternativeTitles"></textarea>
							</div>
							<span class="form-text">
								Alternative Titel unter denen das Lied im Inhaltsverzeichnis aufgelistet wird - die nicht fett gedruckten Titel. z.B. Erste Zeile, Refrain etc.<br>
								Ein Titel pro Zeile. Erster Buchstabe gross, ansonsten wie Text. Alternative Titel sind optional
							</span>
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row has-feedback">
						<label for="inputInterpret" class="col-md-3 col-form-label text-end">Interpret</label>
						<div class="col-md-9">
							<div class="input-group">
								<input type="text" v-model="song.interpret" class="form-control" id="inputInterpret">
							</div>
							<span class="form-text">
								Bandname oder Künstler, damit in der App danach gesucht werden kann.
							</span>
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row has-feedback">
						<label for="text" class="col-md-3 col-form-label text-end">Text</label>
						<div class="col-md-9">
							<div class="input-group">
								<textarea id="text" class="form-control" rows="20" v-model="song.text"></textarea>
							</div>
							<span class="form-text">
								Moll-Akkorde in deutscher Notation (z.B. "Hm" und nicht "h")<br>
								Strophen nicht nummerieren<br>
								Ref. (ohne Doppelpunkt) wird immer wiederholt (nicht nur Wort «Ref.», sondern gesamter Text)
							</span>
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row">
						<div class="col-md-9 offset-md-3">
							<button type="submit" class="btn btn-success mt-1 mb-3 col-12">
								Speichern <i v-if="showSavedIcon" class="fa fa-check-circle"></i>
							</button>
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row has-feedback">
						<label for="inputLang" class="col-md-3 col-form-label text-end">Sprache</label>
						<div class="col-auto">
							<div class="input-group">
								<select v-model="song.lang" class="form-select" id="inputLang">
									<option value=""></option>
									<option v-for="opt in langOptions" :key="opt.id" :value="opt.id">{{ opt.value }}</option>
								</select>
							</div>
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row has-feedback">
						<label for="inputMood" class="col-md-3 col-form-label text-end">Stimmung</label>
						<div class="col-md-9">
							<div class="input-group">
								<input type="text" v-model="song.mood" class="form-control" id="inputMood">
							</div>
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row has-feedback">
						<label for="inputYoutube" class="col-md-3 col-form-label text-end">Youtube Link</label>
						<div class="col-md-7">
							<div class="input-group">
								<input type="text" v-model="song.youtubeLink" class="form-control" id="inputYoutube">
							</div>
							<span class="form-text">
								Zum Anhören wie das Lied klingt. Link im Format: <i>https://www.youtube.com/watch?v=0Fy7opKu46c</i>
							</span>
						</div>
						<div class="col-md-2">
							<a v-if="youtubeVideoId" :href="song.youtubeLink" target="_blank">
								<img :src="'https://img.youtube.com/vi/' + youtubeVideoId + '/default.jpg'" height="52">
							</a>
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row rondo-page-numbers">
						<label class="col-sm-3 col-form-label text-end">Seitenzahlen</label>
						<div class="col-sm-1">
							<span class="badge bg-danger">Rot</span>
							<input type="text" v-model="song.pageRondoRed" class="form-control" placeholder="Seite">
						</div>
						<div class="col-sm-1">
							<span class="badge bg-primary">Blau</span>
							<input type="text" v-model="song.pageRondoBlue" class="form-control" placeholder="Seite">
						</div>
						<div class="col-sm-1">
							<span class="badge" style="background: #99FF99; color: black">Grün</span>
							<input type="text" v-model="song.pageRondoGreen" class="form-control" placeholder="Seite">
						</div>
						<div class="col-sm-1">
							<span class="badge" style="background: orange;">2017</span>
							<input type="text" v-model="song.pageRondo2017" class="form-control" placeholder="Seite">
						</div>
						<div class="col-sm-1">
							<span class="badge" style="background: #fbeb00; color: #d23700;">mova</span>
							<input type="text" v-model="song.pageRondo2021" class="form-control" placeholder="Seite">
						</div>
						<div class="col-sm-1">
							<span class="badge" style="background: #ff3980; color: #ffffff;">2024</span>
							<input type="text" v-model="song.pageRondo2024" class="form-control" placeholder="Seite">
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row">
						<label class="col-sm-3 col-form-label text-end">Auflagen</label>
						<div class="col-sm-9">
							<div class="row" style="padding-top: 6px">
								<div class="col-sm-2">
									<label><input type="checkbox" v-model="song.releaseBook2017" :true-value="1" :false-value="0" disabled> Rondo 2017</label>
								</div>
								<div class="col-sm-2">
									<label><input type="checkbox" v-model="song.releaseBook2021" :true-value="1" :false-value="0" disabled> Mova Rondo</label>
								</div>
								<div class="col-sm-2">
									<label><input type="checkbox" v-model="song.releaseBook2024" :true-value="1" :false-value="0"> Rondo 2024</label>
								</div>
								<div class="col-sm-2">
									<label><input type="checkbox" v-model="song.releaseApp2017" :true-value="1" :false-value="0" disabled> App (bis 2022)</label>
								</div>
								<div class="col-sm-2">
									<label><input type="checkbox" v-model="song.releaseApp2022" :true-value="1" :false-value="0" disabled> App (ab 2022)</label>
								</div>
								<div class="col-sm-2">
									<label><input type="checkbox" v-model="song.releaseApp2024" :true-value="1" :false-value="0"> App (ab 2024)</label>
								</div>
							</div>
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row has-feedback">
						<label for="status" class="col-md-3 col-form-label text-end">Status</label>
						<div class="col-md-9">
							<div class="row g-3">
								<div class="col-auto">
									<select v-model="song.status" id="status" class="form-select">
										<option value="NEW">Neu</option>
										<option value="INPROGRESS">In Arbeit</option>
										<option value="DONE">Fertig</option>
									</select>
								</div>
								<div class="col-auto col-form-label text-end">
									<StatusBadge :status="song.status" />
								</div>
							</div>
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row has-feedback">
						<label for="comments" class="col-md-3 col-form-label text-end">Kommentar zum Status</label>
						<div class="col-md-9">
							<div class="input-group">
								<textarea id="comments" class="form-control" rows="7" v-model="song.comments"></textarea>
							</div>
						</div>
					</div>

					<div class="mb-3"></div>

					<div class="row">
						<div class="col-md-9 offset-md-3">
							<button type="submit" class="btn btn-success mt-1 mb-3 col-12">
								Speichern <i v-if="showSavedIcon" class="fa fa-check-circle"></i>
							</button>
						</div>
					</div>

					<div class="row" style="margin-top: 40px">
						<div class="col-sm-3"></div>
						<div class="col-sm-9"><h2>Copyright</h2></div>
					</div>
					<hr style="margin-top: 0px">

					<div class="row has-feedback">
						<label for="license" class="col-md-3 col-form-label text-end">Lizenz</label>
						<div class="col-md-9">
							<div class="row g-3">
								<div class="col-auto">
									<select v-model="song.license" id="license" class="form-select">
										<option value="UNKNOWN">Unbekannt</option>
										<option value="LICENSED">Lizenziert</option>
										<option value="FREE">Frei</option>
									</select>
								</div>
								<div class="col-auto col-form-label text-end">
									<LicenseBadge :license="song.license" />
								</div>
							</div>
						</div>
					</div>

					<template v-if="song.license === 'LICENSED'">
						<div class="mb-3"></div>

						<div class="row">
							<label for="license-type" class="col-sm-3 col-form-label text-end">Lizenz-Typ</label>
							<div class="col-sm-9">
								<div class="row g-3">
									<div class="col-auto">
										<select v-model="song.license_type" id="license-type" class="form-select">
											<option value="UNKNOWN">Unbekannt</option>
											<option value="ONE_TIME">Einmalig</option>
											<option value="ANNUAL">Jährlich</option>
											<option value="FREE">Gratis</option>
										</select>
									</div>
									<div class="col-auto col-form-label text-end">
										<LicenseTypeBadge :license="song.license_type ?? ''" />
									</div>
								</div>
							</div>
						</div>

						<div class="mb-3"></div>

						<div class="row">
							<label for="copyrightInfoApp" class="col-sm-3 col-form-label text-end">Copyright Text App</label>
							<div class="col-sm-9">
								<textarea id="copyrightInfoApp" class="form-control" rows="4" v-model="song.copyrightInfoApp"></textarea>
							</div>
						</div>

						<div class="mb-3"></div>

						<div class="row">
							<label for="copyrightInfoBook" class="col-sm-3 col-form-label text-end">Copyright Text Buch</label>
							<div class="col-sm-9">
								<textarea id="copyrightInfoBook" class="form-control" rows="4" v-model="song.copyrightInfoBook"></textarea>
							</div>
						</div>

						<div class="mb-3"></div>

						<div class="row has-feedback">
							<label for="inputPublisher" class="col-md-3 col-form-label text-end">Verlag</label>
							<div class="col-md-9">
								<div class="input-group">
									<input type="text" v-model="song.copyrightPublisher" class="form-control" id="inputPublisher">
								</div>
							</div>
						</div>

						<div class="mb-3"></div>

						<div class="row">
							<label for="copyrightContact" class="col-sm-3 col-form-label text-end">Halter / Kontakt</label>
							<div class="col-sm-9">
								<textarea id="copyrightContact" class="form-control" rows="8" v-model="song.copyrightContact"></textarea>
							</div>
						</div>

						<div class="mb-3"></div>

						<div class="row">
							<label class="col-sm-3 col-form-label text-end">Copyright Status Buch 2017</label>
							<div class="col-sm-9" style="padding-top: 6px">
								<StatusBadge :status="song.copyrightStatusBook2017 ?? ''" />
							</div>
						</div>

						<div class="mb-3"></div>

						<div class="row">
							<label class="col-sm-3 col-form-label text-end">Copyright Status Buch 2021</label>
							<div class="col-sm-9" style="padding-top: 6px">
								<StatusBadge :status="song.copyrightStatusBook2021 ?? ''" />
							</div>
						</div>

						<div class="mb-3"></div>

						<div class="row">
							<label for="copyrightStatusBook2024" class="col-sm-3 col-form-label text-end">Copyright Status Buch 2024</label>
							<div class="col-sm-9">
								<div class="row g-3">
									<div class="col-auto">
										<select v-model="song.copyrightStatusBook2024" id="copyrightStatusBook2024" class="form-select">
											<option value="NEW">Neu</option>
											<option value="INPROGRESS">In Arbeit</option>
											<option value="DONE">Fertig</option>
											<option value="NO_LICENSE">Fertig, keine Lizenz erhalten</option>
										</select>
									</div>
									<div class="col-auto col-form-label text-end">
										<StatusBadge :status="song.copyrightStatusBook2024 ?? ''" />
									</div>
								</div>
							</div>
						</div>

						<div class="mb-3"></div>

						<div class="row">
							<label class="col-sm-3 col-form-label text-end"></label>
							<div class="col-sm-9 pb-4"><h5 class="mt-3">Copyright App</h5></div>
						</div>

						<div class="row">
							<label for="copyrightStatusApp" class="col-md-3 col-form-label text-end">Copyright Status App</label>
							<div class="col-sm-9">
								<div class="row g-3">
									<div class="col-auto">
										<select v-model="song.copyrightStatusApp" id="copyrightStatusApp" class="form-select">
											<option value="NEW">Neu</option>
											<option value="INPROGRESS">In Arbeit</option>
											<option value="DONE">Fertig</option>
											<option value="NO_LICENSE">Fertig, keine Lizenz erhalten</option>
										</select>
									</div>
									<div class="col-auto col-form-label text-end">
										<StatusBadge :status="song.copyrightStatusApp ?? ''" />
									</div>
								</div>
							</div>
						</div>

						<div class="mb-3"></div>

						<div class="row">
							<label for="license-type-app" class="col-sm-3 col-form-label text-end">Lizenz-Typ App</label>
							<div class="col-sm-9">
								<div class="row g-3">
									<div class="col-auto">
										<select v-model="song.license_type_app" id="license-type-app" class="form-select">
											<option value="UNKNOWN">Unbekannt</option>
											<option value="ONE_TIME">Einmalig</option>
											<option value="PRO_RATA_50">50% pro rata</option>
											<option value="FREE">Gratis</option>
											<option value="OTHER">Speziell, siehe Bemerkung</option>
										</select>
									</div>
									<div class="col-auto col-form-label text-end">
										<LicenseTypeBadge :license="song.license_type_app ?? ''" />
									</div>
								</div>
							</div>
						</div>

						<div class="mb-3"></div>

						<div class="row">
							<label for="copyrightCommentApp" class="col-sm-3 col-form-label text-end">Bemerkung Copyright App</label>
							<div class="col-sm-9">
								<textarea id="copyrightCommentApp" class="form-control" rows="3" v-model="song.copyrightCommentApp"></textarea>
							</div>
						</div>

						<div class="mb-3"></div>

						<div class="row">
							<label for="licenseAppUntil" class="col-sm-3 col-form-label text-end">Copyright App bis</label>
							<div class="col-sm-2">
								<input v-if="!song.licenseAppUntilIndefinite" type="date" id="licenseAppUntil" class="form-control" v-model="song.licenseAppUntil" />
								<input v-else type="text" class="form-control" disabled />
							</div>
							<div class="col-sm-4 mt-2">
								<label>
									<input type="checkbox" v-model="song.licenseAppUntilIndefinite" :true-value="1" :false-value="0" class="form-check-input"> unbestimmte Dauer
								</label>
							</div>
						</div>

						<div class="mb-3"></div>

						<div class="row">
							<label class="col-sm-3 col-form-label text-end"></label>
							<div class="col-sm-9 pb-4">
								<h5 class="mt-3">Copyright Dateien</h5>
								<div v-if="song.files && song.files.length > 0" class="file-list">
									<div v-for="file in song.files" :key="file.id" class="file-list__file">
										<i class="fa fa-file-pdf-o" style="color: #7e7e7e;" v-if="file.mime === 'application/pdf'"></i>
										<i class="fa fa-file-o" style="color: #7e7e7e;" v-else></i>&nbsp;
										<a :href="'/api/files/' + file.id + '/' + encodeURIComponent(file.name)" target="_blank">{{ file.name }}</a>
										<span class="pull-right">
											<small class="text-muted" :title="'Hochgeladen am ' + file.creationTime">
												{{ file.creationTime ? file.creationTime.slice(0, 10) : '' }}&nbsp;
											</small>
											<a href="" @click.prevent="deleteFile(file)"><i class="fa fa-trash file-list__file-delete-icon"></i></a>
										</span>
									</div>
								</div>
								<div v-else class="text-muted mb-2">Noch keine Dateien hochgeladen</div>
								<input type="file" name="file" @change="uploadGenericFile($event, 'copyright')" />
							</div>
						</div>
					</template>

					<div class="mb-3"></div>

					<div class="row">
						<div class="col-md-9 offset-md-3">
							<button type="submit" class="btn btn-success mt-1 mb-3 col-12">
								Speichern <i v-if="showSavedIcon" class="fa fa-check-circle"></i>
							</button>
						</div>
					</div>
					<router-link to="/songs">&laquo; zurück zur Liste</router-link>
					<br><br>
				</form>
			</div>

			<div class="col-sm-4">
				<br />
				<h4 class="mt-5">Vorschau</h4>
				<div class="preview" :class="{ 'rondo-show-chords': showAccords }" v-html="preview"></div>
				<label>
					<input type="checkbox" v-model="showAccords"> Akkorde anzeigen
				</label>

				<br /><br />

				<h4>Sibelius Screenshot</h4>
				<br>
				<a :href="'/api/songs/' + song.id + '/raw/rawNotesPNG.png'" target="_blank">
					<img :src="'/api/songs/' + song.id + '/raw/rawNotesPNG.png'" width="320">
				</a>
				<br><br>

				<h4>Dateien hochladen</h4>
				<form>
					<div class="card mb-3">
						<div class="card-body">
							<label for="inputImage" class="control-label"><b>Titelbild</b></label>
							1242 x 660 px <i>(png, jpg, gif)</i><br>
							<input type="file" name="file" id="inputImage" @change="uploadFile($event, 'rawImage')" accept="image/png, image/gif, image/jpeg" />
							<div v-if="song.rawImageSize && song.rawImageSize > 0">
								<br>
								<i class="fa fa-check-circle" style="color: green;"></i>
								<a :href="'/api/songs/' + song.id + '/raw/rawImage.png'" target="_blank">Download</a>
								{{ ((song.rawImageSize ?? 0) / 1048576).toFixed(2) }} MB
							</div>
							<div v-else class="text-muted"><br><i class="fa fa-times" style="color: red;"></i> Keine Datei hochgeladen</div>
						</div>
					</div>

					<div class="card mb-3">
						<div class="card-body">
							<label for="sibeliusFile" class="control-label"><b>Sibelius Datei</b></label><br>
							<input type="file" name="file" id="sibeliusFile" @change="uploadFile($event, 'rawSIB')" accept=".sib" />
							<div v-if="song.rawSIBSize && song.rawSIBSize > 0">
								<br>
								<i class="fa fa-check-circle" style="color: green;"></i>
								<a :href="'/api/songs/' + song.id + '/raw/rawSIB.sib'" target="_blank">Download</a>
								{{ ((song.rawSIBSize ?? 0) / 1048576).toFixed(2) }} MB
							</div>
							<div v-else class="text-muted"><br><i class="fa fa-times" style="color: red;"></i> Keine Datei hochgeladen</div>
						</div>
					</div>

					<div class="card mb-3">
						<div class="card-body">
							<label for="notesFile" class="control-label"><b>Noten PDF</b></label><br>
							<input type="file" name="file" id="notesFile" @change="uploadFile($event, 'rawNotesPDF')" accept=".pdf" />
							<div v-if="song.rawNotesPDFSize && song.rawNotesPDFSize > 0">
								<br>
								<i class="fa fa-check-circle" style="color: green;"></i>
								<a :href="'/api/songs/' + song.id + '/raw/rawNotesPDF.pdf'" target="_blank">Download</a>
								{{ ((song.rawNotesPDFSize ?? 0) / 1048576).toFixed(2) }} MB
							</div>
							<div v-else class="text-muted"><br><i class="fa fa-times" style="color: red;"></i> Keine Datei hochgeladen</div>
						</div>
					</div>

					<div class="card mb-3">
						<div class="card-body">
							<label for="midiFile" class="control-label"><b>Midi Datei</b></label><br>
							<input type="file" name="file" id="midiFile" @change="uploadFile($event, 'rawMidi')" accept=".mid" />
							<div v-if="song.rawMidiSize && song.rawMidiSize > 0">
								<br>
								<i class="fa fa-check-circle" style="color: green;"></i>
								<a :href="'/api/songs/' + song.id + '/raw/rawMidi.mid'" target="_blank">Download</a>
								{{ ((song.rawMidiSize ?? 0) / 1048576).toFixed(2) }} MB
							</div>
							<div v-else class="text-muted"><br><i class="fa fa-times" style="color: red;"></i> Keine Datei hochgeladen</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { Song, SongFile } from '../types/Song'
import StatusBadge from '../components/StatusBadge.vue'
import LicenseBadge from '../components/LicenseBadge.vue'
import LicenseTypeBadge from '../components/LicenseTypeBadge.vue'

export default defineComponent({
	name: 'SongDetail',
	components: { StatusBadge, LicenseBadge, LicenseTypeBadge },
	setup() {
		const route = useRoute()
		const router = useRouter()
		const songId = computed(() => route.params.songId as string)
		const song = ref<Song | null>(null)
		const preview = ref('')
		const showAccords = ref(true)
		const showSavedIcon = ref(false)
		const prevSongId = computed(() => parseInt(songId.value) - 1)
		const nextSongId = computed(() => parseInt(songId.value) + 1)

		const langOptions = [
			{ id: 'de', value: 'Deutsch' },
			{ id: 'ch', value: 'Mundart' },
			{ id: 'fr', value: 'Französisch' },
			{ id: 'it', value: 'Italienisch' },
			{ id: 'en', value: 'Englisch' },
			{ id: 'other', value: 'Andere' }
		]

		const youtubeVideoId = computed(() => {
			const link = song.value?.youtubeLink
			if (!link) return null
			const parts = link.split('=')
			return parts.length === 2 ? parts[1] : null
		})

		function loadData() {
			fetch('/api/songs/' + songId.value)
				.then(r => r.json())
				.then((data: Song) => { song.value = data })
				.catch(() => console.error('AJAX failed!'))

			fetch('/api/songs/' + songId.value + '/html')
				.then(r => r.text())
				.then(html => { preview.value = html })
				.catch(() => console.error('AJAX failed!'))
		}

		function save() {
			fetch('/api/songs/' + songId.value, {
				method: 'PUT',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(song.value)
			})
				.then(() => {
					loadData()
					showSavedIcon.value = true
					setTimeout(() => { showSavedIcon.value = false }, 3000)
				})
				.catch(err => {
					console.error('AJAX failed!', err)
					loadData()
				})
		}

		function uploadFile(event: Event, type: string) {
			const input = event.target as HTMLInputElement
			if (!input.files || input.files.length === 0) return
			const fd = new FormData()
			fd.append('file', input.files[0])
			fetch('/api/songs/' + songId.value + '/' + type, { method: 'POST', body: fd })
				.then(() => loadData())
				.catch(() => { alert('Datei konnte nicht hochgeladen werden.'); loadData() })
		}

		function uploadGenericFile(event: Event, type: string) {
			const input = event.target as HTMLInputElement
			if (!input.files || input.files.length === 0) return
			const fd = new FormData()
			fd.append('file', input.files[0])
			fetch('/api/files?songId=' + songId.value + '&type=' + type, { method: 'POST', body: fd })
				.then(() => loadData())
				.catch(() => { alert('Datei konnte nicht hochgeladen werden.'); loadData() })
		}

		function deleteFile(file: SongFile) {
			if (confirm('Datei ' + file.name + ' wirklich löschen?')) {
				fetch('/api/files/' + file.id, { method: 'DELETE' })
					.then(() => loadData())
					.catch(() => { alert('Datei konnte nicht gelöscht werden.'); loadData() })
			}
		}

		onMounted(loadData)
		watch(songId, loadData)

		return {
			song, preview, showAccords, showSavedIcon,
			prevSongId, nextSongId, langOptions, youtubeVideoId, router,
			save, uploadFile, uploadGenericFile, deleteFile
		}
	}
})
</script>
