<template>
	<span class="song-status text-nowrap">
		<span v-if="status === 'NEW'" style="color: red;">
			<i class="fa fa-asterisk"></i> Neu
		</span>
		<span v-else-if="status === 'INPROGRESS'" style="color: orange;">
			<i class="fa fa-ellipsis-h"></i> In Arbeit
		</span>
		<span v-else-if="status === 'DONE'" style="color: green;">
			<i class="fa fa-check-circle"></i> Fertig
		</span>
		<span v-else-if="status === 'NO_LICENSE'" style="color: red;">
			<i class="fa fa-minus-circle"></i> Keine Lizenz erhalten
		</span>
		<span v-else>{{ status }}</span>
	</span>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
	name: 'StatusBadge',
	props: {
		status: {
			type: String,
			default: ''
		}
	}
})
</script>
