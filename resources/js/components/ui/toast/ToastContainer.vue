<script setup lang="ts">
import { ref } from 'vue'
import Toast from './Toast.vue'

interface ToastItem {
	id: string
	type: 'success' | 'error' | 'warning' | 'info'
	title: string
	description?: string
	duration?: number
	persistent?: boolean
}

const toasts = ref<ToastItem[]>([])

const addToast = (toast: Omit<ToastItem, 'id'>) => {
	const id = Date.now().toString() + Math.random().toString(36).substr(2, 9)
	toasts.value.push({ ...toast, id })
	return id
}

const removeToast = (id: string) => {
	const index = toasts.value.findIndex(toast => toast.id === id)
	if (index > -1) {
		toasts.value.splice(index, 1)
	}
}

const clearAll = () => {
	toasts.value = []
}

// Esponi i metodi per l'uso globale
defineExpose({
	addToast,
	removeToast,
	clearAll,
})
</script>

<template>
	<div
		aria-live="assertive"
		class="pointer-events-none fixed inset-0 z-50 flex items-end px-4 py-6 sm:items-start sm:p-6"
	>
		<div class="flex w-full flex-col items-center space-y-4 sm:items-end">
			<TransitionGroup
				name="toast"
				tag="div"
				class="flex w-full flex-col items-center space-y-4 sm:items-end"
			>
				<Toast
					v-for="toast in toasts"
					:key="toast.id"
					:id="toast.id"
					:type="toast.type"
					:title="toast.title"
					:description="toast.description"
					:duration="toast.duration"
					:persistent="toast.persistent"
					@close="removeToast"
				/>
			</TransitionGroup>
		</div>
	</div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
	transition: all 0.3s ease;
}

.toast-enter-from {
	opacity: 0;
	transform: translateX(100%);
}

.toast-leave-to {
	opacity: 0;
	transform: translateX(100%);
}

.toast-move {
	transition: transform 0.3s ease;
}
</style>
