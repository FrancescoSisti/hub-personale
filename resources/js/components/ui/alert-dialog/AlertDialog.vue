<script setup lang="ts">
interface Props {
	open?: boolean
}

interface Emits {
	(e: 'update:open', value: boolean): void
}

const props = withDefaults(defineProps<Props>(), {
	open: false,
})

const emit = defineEmits<Emits>()

const handleClose = () => {
	emit('update:open', false)
}
</script>

<template>
	<!-- Overlay -->
	<div
		v-if="open"
		class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
		@click="handleClose"
	>
		<slot />
	</div>
</template>
