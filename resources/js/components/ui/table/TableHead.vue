<script setup lang="ts">
import { cn } from '@/lib/utils'

interface Props {
	class?: string
}

const props = defineProps<Props>()
</script>

<template>
	<th :class="cn('h-10 px-2 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]', props.class)">
		<slot />
	</th>
</template>
