<template>
	<span>
		<span v-if="state > 0"><i class="fa fa-check" style="color: green;"></i></span>
		<span v-else class="text-muted"><i class="fa fa-times" style="color: red;"></i></span>
	</span>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
	name: 'YesNo',
	props: {
		state: {
			type: Number,
			default: 0
		}
	}
})
</script>
