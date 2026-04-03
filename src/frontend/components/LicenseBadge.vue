<template>
	<span class="song-license text-nowrap">
		<span v-if="license === 'LICENSED'" style="color: red;">Lizenziert</span>
		<span v-else-if="license === 'UNKNOWN'" style="color: orange;">Unbekannt</span>
		<span v-else-if="license === 'FREE'" style="color: green;">Frei</span>
		<span v-else>{{ license }}</span>
	</span>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
	name: 'LicenseBadge',
	props: {
		license: {
			type: String,
			default: ''
		}
	}
})
</script>
