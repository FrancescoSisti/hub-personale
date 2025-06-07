<script setup lang="ts">
import { cn } from '@/lib/utils'

interface Props {
	class?: string
}

const props = defineProps<Props>()
</script>

<template>
	<div
		class="fixed left-[50%] top-[50%] z-50 grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 border bg-background p-6 shadow-lg duration-200 sm:rounded-lg"
		:class="cn('', props.class)"
		@click.stop
	>
		<slot />
	</div>
</template>
