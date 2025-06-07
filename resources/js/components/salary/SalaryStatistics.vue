<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { TrendingUp, TrendingDown, Minus } from 'lucide-vue-next'

interface Statistics {
	total_gross: number
	total_net: number
	total_taxes: number
	total_deductions: number
	total_overtime: number
	monthly_data: Array<{
		month: number
		gross_salary: number
		net_salary: number
		tax_amount: number
		deductions: number
		overtime_pay: number
	}>
}

interface Props {
	statistics: Statistics
}

const props = defineProps<Props>()

const formatCurrency = (amount: number): string => {
	return new Intl.NumberFormat('it-IT', {
		style: 'currency',
		currency: 'EUR',
	}).format(amount)
}

const formatPercentage = (value: number): string => {
	return `${value.toFixed(1)}%`
}

const averageMonthlyGross = computed(() => {
	if (props.statistics.monthly_data.length === 0) return 0
	return props.statistics.total_gross / props.statistics.monthly_data.length
})

const averageMonthlyNet = computed(() => {
	if (props.statistics.monthly_data.length === 0) return 0
	return props.statistics.total_net / props.statistics.monthly_data.length
})

const taxRate = computed(() => {
	if (props.statistics.total_gross === 0) return 0
	return (props.statistics.total_taxes / props.statistics.total_gross) * 100
})

const deductionRate = computed(() => {
	if (props.statistics.total_gross === 0) return 0
	return (props.statistics.total_deductions / props.statistics.total_gross) * 100
})

const netRate = computed(() => {
	if (props.statistics.total_gross === 0) return 0
	return (props.statistics.total_net / props.statistics.total_gross) * 100
})

const monthlyTrend = computed(() => {
	if (props.statistics.monthly_data.length < 2) return 0

	const sortedData = [...props.statistics.monthly_data].sort((a, b) => a.month - b.month)
	const firstMonth = sortedData[0]
	const lastMonth = sortedData[sortedData.length - 1]

	if (firstMonth.net_salary === 0) return 0
	return ((lastMonth.net_salary - firstMonth.net_salary) / firstMonth.net_salary) * 100
})

const getTrendIcon = (trend: number) => {
	if (trend > 0) return TrendingUp
	if (trend < 0) return TrendingDown
	return Minus
}

const getTrendColor = (trend: number) => {
	if (trend > 0) return 'text-green-600'
	if (trend < 0) return 'text-red-600'
	return 'text-gray-600'
}
</script>

<template>
	<Card>
		<CardHeader>
			<CardTitle>Statistiche Dettagliate</CardTitle>
			<CardDescription>Analisi approfondita dei tuoi stipendi</CardDescription>
		</CardHeader>
		<CardContent class="space-y-6">
			<!-- Medie Mensili -->
			<div class="grid gap-4 md:grid-cols-2">
				<div class="space-y-2">
					<p class="text-sm font-medium text-muted-foreground">Media Mensile Lordo</p>
					<p class="text-2xl font-bold">{{ formatCurrency(averageMonthlyGross) }}</p>
				</div>
				<div class="space-y-2">
					<p class="text-sm font-medium text-muted-foreground">Media Mensile Netto</p>
					<p class="text-2xl font-bold">{{ formatCurrency(averageMonthlyNet) }}</p>
				</div>
			</div>

			<!-- Percentuali -->
			<div class="space-y-4">
				<div class="flex items-center justify-between">
					<span class="text-sm font-medium">Aliquota Fiscale</span>
					<span class="text-sm font-bold text-red-600">{{ formatPercentage(taxRate) }}</span>
				</div>
				<div class="w-full bg-gray-200 rounded-full h-2">
					<div
						class="bg-red-600 h-2 rounded-full"
						:style="{ width: `${taxRate}%` }"
					></div>
				</div>

				<div class="flex items-center justify-between">
					<span class="text-sm font-medium">Detrazioni</span>
					<span class="text-sm font-bold text-orange-600">{{ formatPercentage(deductionRate) }}</span>
				</div>
				<div class="w-full bg-gray-200 rounded-full h-2">
					<div
						class="bg-orange-600 h-2 rounded-full"
						:style="{ width: `${deductionRate}%` }"
					></div>
				</div>

				<div class="flex items-center justify-between">
					<span class="text-sm font-medium">Stipendio Netto</span>
					<span class="text-sm font-bold text-green-600">{{ formatPercentage(netRate) }}</span>
				</div>
				<div class="w-full bg-gray-200 rounded-full h-2">
					<div
						class="bg-green-600 h-2 rounded-full"
						:style="{ width: `${netRate}%` }"
					></div>
				</div>
			</div>

			<!-- Trend Mensile -->
			<div class="flex items-center justify-between p-4 bg-muted rounded-lg">
				<div>
					<p class="text-sm font-medium text-muted-foreground">Trend Mensile</p>
					<p class="text-lg font-bold" :class="getTrendColor(monthlyTrend)">
						{{ monthlyTrend > 0 ? '+' : '' }}{{ formatPercentage(monthlyTrend) }}
					</p>
				</div>
				<component :is="getTrendIcon(monthlyTrend)" :class="getTrendColor(monthlyTrend)" class="h-8 w-8" />
			</div>

			<!-- Totali Annuali -->
			<div class="grid gap-2 text-sm">
				<div class="flex justify-between">
					<span class="text-muted-foreground">Totale Straordinari:</span>
					<span class="font-medium">{{ formatCurrency(statistics.total_overtime) }}</span>
				</div>
				<div class="flex justify-between">
					<span class="text-muted-foreground">Totale Tasse:</span>
					<span class="font-medium text-red-600">{{ formatCurrency(statistics.total_taxes) }}</span>
				</div>
				<div class="flex justify-between">
					<span class="text-muted-foreground">Totale Detrazioni:</span>
					<span class="font-medium text-orange-600">{{ formatCurrency(statistics.total_deductions) }}</span>
				</div>
			</div>
		</CardContent>
	</Card>
</template>
