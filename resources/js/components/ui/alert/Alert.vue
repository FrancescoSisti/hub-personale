<script setup lang="ts">
import { computed } from 'vue'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const alertVariants = cva(
	'relative w-full rounded-lg border px-4 py-3 text-sm [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 [&>svg]:text-foreground [&>svg~*]:pl-7',
	{
		variants: {
			variant: {
				default: 'bg-background text-foreground',
				destructive:
					'border-destructive/50 text-destructive dark:border-destructive [&>svg]:text-destructive',
			},
		},
		defaultVariants: {
			variant: 'default',
		},
	},
)

interface Props {
	variant?: VariantProps<typeof alertVariants>['variant']
	class?: string
}

const props = withDefaults(defineProps<Props>(), {
	variant: 'default',
})

const classes = computed(() => cn(alertVariants({ variant: props.variant }), props.class))
</script>

<template>
	<div :class="classes">
		<slot />
	</div>
</template>
