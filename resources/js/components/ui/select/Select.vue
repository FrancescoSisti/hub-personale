<script setup lang="ts">
import { SelectRoot } from 'reka-ui'

interface Props {
	modelValue?: string
	defaultValue?: string
	disabled?: boolean
}

interface Emits {
	(e: 'update:modelValue', value: string): void
}

defineProps<Props>()
defineEmits<Emits>()
</script>

<template>
	<SelectRoot
		:model-value="modelValue"
		:default-value="defaultValue"
		:disabled="disabled"
		@update:model-value="$emit('update:modelValue', $event)"
	>
		<slot />
	</SelectRoot>
</template>
