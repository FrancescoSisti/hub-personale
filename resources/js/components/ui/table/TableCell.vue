<script setup lang="ts">
import { cn } from '@/lib/utils'

interface Props {
	class?: string
}

const props = defineProps<Props>()
</script>

<template>
	<td :class="cn('p-2 align-middle [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]', props.class)">
		<slot />
	</td>
</template>
