<template>
	<span class="song-license text-nowrap">
		<span v-if="license === 'UNKNOWN'" style="color: gray;">
			<i class="fa fa-question-circle"></i> Unbekannt
		</span>
		<span v-else-if="license === 'ONE_TIME'" style="color: green;">
			<i class="fa fa-check-circle"></i> Einmalig
		</span>
		<span v-else-if="license === 'ANNUAL'" style="color: orange;">
			<i class="fa fa-repeat"></i> Jährlich
		</span>
		<span v-else-if="license === 'PRO_RATA_50'" style="color: orange;">
			<i class="fa fa-repeat"></i> Pro Rata 50%
		</span>
		<span v-else-if="license === 'FREE'" style="color: green;">
			<i class="fa fa-gift"></i> Gratis
		</span>
		<span v-else-if="license === 'OTHER'" style="color: #000069;">
			<i class="fa fa-comment"></i> Sonstiges
		</span>
		<span v-else>{{ license }}</span>
	</span>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
	name: 'LicenseTypeBadge',
	props: {
		license: {
			type: String,
			default: ''
		}
	}
})
</script>
