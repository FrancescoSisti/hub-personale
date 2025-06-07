<script setup lang="ts">
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'

interface Props {
	class?: string
}

const props = defineProps<Props>()
</script>

<template>
	<Button
		:class="cn('', props.class)"
		@click="$emit('click')"
	>
		<slot />
	</Button>
</template>
