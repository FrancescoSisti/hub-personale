<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
	class?: string
}

const props = withDefaults(defineProps<Props>(), {})

const classes = computed(() => cn('text-sm [&_p]:leading-relaxed', props.class))
</script>

<template>
	<div :class="classes">
		<slot />
	</div>
</template>
