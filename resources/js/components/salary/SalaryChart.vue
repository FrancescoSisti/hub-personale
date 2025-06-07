<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
	Chart as ChartJS,
	CategoryScale,
	LinearScale,
	PointElement,
	LineElement,
	Title,
	Tooltip,
	Legend,
	BarElement,
} from 'chart.js'
import { Line, Bar } from 'vue-chartjs'

ChartJS.register(
	CategoryScale,
	LinearScale,
	PointElement,
	LineElement,
	BarElement,
	Title,
	Tooltip,
	Legend
)

interface MonthlyData {
	month: number
	gross_salary: number
	net_salary: number
	tax_amount: number
	deductions: number
	overtime_pay: number
}

interface Props {
	data: MonthlyData[]
	year: number
}

const props = defineProps<Props>()

const monthNames = [
	'Gen',
	'Feb',
	'Mar',
	'Apr',
	'Mag',
	'Giu',
	'Lug',
	'Ago',
	'Set',
	'Ott',
	'Nov',
	'Dic',
]

const chartData = computed(() => {
	const sortedData = [...props.data].sort((a, b) => a.month - b.month)

	return {
		labels: sortedData.map((item) => monthNames[item.month - 1]),
		datasets: [
			{
				label: 'Stipendio Lordo',
				data: sortedData.map((item) => item.gross_salary),
				borderColor: 'rgb(59, 130, 246)',
				backgroundColor: 'rgba(59, 130, 246, 0.1)',
				tension: 0.4,
			},
			{
				label: 'Stipendio Netto',
				data: sortedData.map((item) => item.net_salary),
				borderColor: 'rgb(34, 197, 94)',
				backgroundColor: 'rgba(34, 197, 94, 0.1)',
				tension: 0.4,
			},
		],
	}
})

const chartOptions = {
	responsive: true,
	maintainAspectRatio: false,
	plugins: {
		legend: {
			position: 'top' as const,
		},
		title: {
			display: false,
		},
	},
	scales: {
		y: {
			beginAtZero: true,
			ticks: {
				callback: function (value: any) {
					return '€' + value.toLocaleString('it-IT')
				},
			},
		},
	},
}

const barChartData = computed(() => {
	const sortedData = [...props.data].sort((a, b) => a.month - b.month)

	return {
		labels: sortedData.map((item) => monthNames[item.month - 1]),
		datasets: [
			{
				label: 'Tasse',
				data: sortedData.map((item) => item.tax_amount),
				backgroundColor: 'rgba(239, 68, 68, 0.8)',
			},
			{
				label: 'Detrazioni',
				data: sortedData.map((item) => item.deductions),
				backgroundColor: 'rgba(245, 158, 11, 0.8)',
			},
			{
				label: 'Straordinari',
				data: sortedData.map((item) => item.overtime_pay),
				backgroundColor: 'rgba(168, 85, 247, 0.8)',
			},
		],
	}
})

const barChartOptions = {
	responsive: true,
	maintainAspectRatio: false,
	plugins: {
		legend: {
			position: 'top' as const,
		},
	},
	scales: {
		y: {
			beginAtZero: true,
			ticks: {
				callback: function (value: any) {
					return '€' + value.toLocaleString('it-IT')
				},
			},
		},
	},
}
</script>

<template>
	<div class="grid gap-4">
		<Card>
			<CardHeader>
				<CardTitle>Andamento Stipendi {{ year }}</CardTitle>
				<CardDescription>Confronto tra stipendio lordo e netto mensile</CardDescription>
			</CardHeader>
			<CardContent>
				<div class="h-[300px]">
					<Line :data="chartData" :options="chartOptions" />
				</div>
			</CardContent>
		</Card>

		<Card>
			<CardHeader>
				<CardTitle>Dettaglio Componenti</CardTitle>
				<CardDescription>Tasse, detrazioni e straordinari per mese</CardDescription>
			</CardHeader>
			<CardContent>
				<div class="h-[300px]">
					<Bar :data="barChartData" :options="barChartOptions" />
				</div>
			</CardContent>
		</Card>
	</div>
</template>
