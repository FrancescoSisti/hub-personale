<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
	variant?: 'default' | 'secondary' | 'destructive' | 'outline'
	class?: string
}

const props = withDefaults(defineProps<Props>(), {
	variant: 'default',
})

const badgeVariants = computed(() => {
	const base = 'inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2'

	const variants = {
		default: 'border-transparent bg-primary text-primary-foreground shadow hover:bg-primary/80',
		secondary: 'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80',
		destructive: 'border-transparent bg-destructive text-destructive-foreground shadow hover:bg-destructive/80',
		outline: 'text-foreground',
	}

	return cn(base, variants[props.variant], props.class)
})
</script>

<template>
	<div :class="badgeVariants">
		<slot />
	</div>
</template>
