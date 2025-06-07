<script setup lang="ts">
import { SelectItem as RekaSelectItem, SelectItemIndicator, SelectItemText } from 'reka-ui'
import { cn } from '@/lib/utils'
import { Check } from 'lucide-vue-next'

interface Props {
	value: string
	disabled?: boolean
	class?: string
}

defineProps<Props>()
</script>

<template>
	<RekaSelectItem
		:value="value"
		:disabled="disabled"
		:class="
			cn(
				'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
				$props.class
			)
		"
	>
		<span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
			<SelectItemIndicator>
				<Check class="h-4 w-4" />
			</SelectItemIndicator>
		</span>
		<SelectItemText>
			<slot />
		</SelectItemText>
	</RekaSelectItem>
</template>
