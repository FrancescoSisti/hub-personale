<script setup lang="ts">
import { SelectValue as RekaSelectValue } from 'reka-ui'

interface Props {
	placeholder?: string
}

defineProps<Props>()
</script>

<template>
	<RekaSelectValue :placeholder="placeholder" />
</template>
