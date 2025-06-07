<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { CheckCircle, XCircle, AlertCircle, Info, X } from 'lucide-vue-next'

interface Props {
	id: string
	type: 'success' | 'error' | 'warning' | 'info'
	title: string
	description?: string
	duration?: number
	persistent?: boolean
}

interface Emits {
	(e: 'close', id: string): void
}

const props = withDefaults(defineProps<Props>(), {
	duration: 5000,
	persistent: false,
})

const emit = defineEmits<Emits>()

const visible = ref(false)

const icon = computed(() => {
	switch (props.type) {
		case 'success':
			return CheckCircle
		case 'error':
			return XCircle
		case 'warning':
			return AlertCircle
		case 'info':
			return Info
		default:
			return Info
	}
})

const classes = computed(() => {
	const baseClasses = 'pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 transition-all duration-300 ease-in-out'

	if (!visible.value) {
		return `${baseClasses} translate-x-full opacity-0`
	}

	return `${baseClasses} translate-x-0 opacity-100`
})

const iconClasses = computed(() => {
	switch (props.type) {
		case 'success':
			return 'text-green-400'
		case 'error':
			return 'text-red-400'
		case 'warning':
			return 'text-yellow-400'
		case 'info':
			return 'text-blue-400'
		default:
			return 'text-gray-400'
	}
})

const close = () => {
	visible.value = false
	setTimeout(() => {
		emit('close', props.id)
	}, 300)
}

onMounted(() => {
	visible.value = true

	if (!props.persistent && props.duration > 0) {
		setTimeout(() => {
			close()
		}, props.duration)
	}
})
</script>

<template>
	<div :class="classes">
		<div class="p-4">
			<div class="flex items-start">
				<div class="flex-shrink-0">
					<component :is="icon" :class="iconClasses" class="h-6 w-6" />
				</div>
				<div class="ml-3 w-0 flex-1 pt-0.5">
					<p class="text-sm font-medium text-gray-900">
						{{ title }}
					</p>
					<p v-if="description" class="mt-1 text-sm text-gray-500">
						{{ description }}
					</p>
				</div>
				<div class="ml-4 flex flex-shrink-0">
					<button
						@click="close"
						class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
					>
						<span class="sr-only">Chiudi</span>
						<X class="h-5 w-5" />
					</button>
				</div>
			</div>
		</div>
	</div>
</template>
