<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import SalaryChart from '@/components/salary/SalaryChart.vue'
import SalaryStatistics from '@/components/salary/SalaryStatistics.vue'
import SalaryTable from '@/components/salary/SalaryTable.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Plus, TrendingUp, Euro, Calendar } from 'lucide-vue-next'

interface Salary {
	id: number
	base_salary: number
	bonus: number
	overtime_hours: number
	overtime_rate: number
	deductions: number
	net_salary: number
	gross_salary: number
	tax_amount: number
	month: number
	year: number
	notes?: string
	created_at: string
	updated_at: string
}

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
	salaries: Salary[]
	statistics: Statistics
	currentMonthSalary?: Salary
	year: number
}

const props = defineProps<Props>()

const selectedYear = ref(props.year)

const breadcrumbs: BreadcrumbItem[] = [
	{ title: 'Dashboard', href: '/dashboard' },
	{ title: 'Stipendi', href: '/salaries' },
]

const monthNames = [
	'Gennaio',
	'Febbraio',
	'Marzo',
	'Aprile',
	'Maggio',
	'Giugno',
	'Luglio',
	'Agosto',
	'Settembre',
	'Ottobre',
	'Novembre',
	'Dicembre',
]

const currentMonthName = computed(() => {
	const currentMonth = new Date().getMonth()
	return monthNames[currentMonth]
})

const formatCurrency = (amount: number): string => {
	return new Intl.NumberFormat('it-IT', {
		style: 'currency',
		currency: 'EUR',
	}).format(amount)
}

const handleYearChange = (year: string) => {
	selectedYear.value = parseInt(year)
	router.get('/salaries', { year: selectedYear.value })
}

const availableYears = computed(() => {
	const currentYear = new Date().getFullYear()
	const years = []
	for (let i = currentYear - 5; i <= currentYear + 1; i++) {
		years.push(i)
	}
	return years
})
</script>

<template>
	<Head title="Gestione Stipendi" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-1 flex-col gap-6 p-6">
			<!-- Header -->
			<div class="flex items-center justify-between">
				<div>
					<h1 class="text-3xl font-bold tracking-tight">Gestione Stipendi</h1>
					<p class="text-muted-foreground">
						Monitora e gestisci i tuoi stipendi mensili
					</p>
				</div>
				<div class="flex items-center gap-4">
					<Select :model-value="selectedYear.toString()" @update:model-value="handleYearChange">
						<SelectTrigger class="w-32">
							<SelectValue />
						</SelectTrigger>
						<SelectContent>
							<SelectItem v-for="year in availableYears" :key="year" :value="year.toString()">
								{{ year }}
							</SelectItem>
						</SelectContent>
					</Select>
					<Link href="/salaries/create">
						<Button>
							<Plus class="mr-2 h-4 w-4" />
							Nuovo Stipendio
						</Button>
					</Link>
				</div>
			</div>

			<!-- Quick Stats Cards -->
			<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
				<Card>
					<CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
						<CardTitle class="text-sm font-medium">Stipendio {{ currentMonthName }}</CardTitle>
						<Euro class="h-4 w-4 text-muted-foreground" />
					</CardHeader>
					<CardContent>
						<div class="text-2xl font-bold">
							{{ currentMonthSalary ? formatCurrency(currentMonthSalary.net_salary) : 'N/A' }}
						</div>
						<p class="text-xs text-muted-foreground">
							{{ currentMonthSalary ? 'Registrato' : 'Non ancora registrato' }}
						</p>
					</CardContent>
				</Card>

				<Card>
					<CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
						<CardTitle class="text-sm font-medium">Totale Netto {{ selectedYear }}</CardTitle>
						<TrendingUp class="h-4 w-4 text-muted-foreground" />
					</CardHeader>
					<CardContent>
						<div class="text-2xl font-bold">
							{{ formatCurrency(statistics.total_net) }}
						</div>
						<p class="text-xs text-muted-foreground">
							{{ salaries.length }} mesi registrati
						</p>
					</CardContent>
				</Card>

				<Card>
					<CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
						<CardTitle class="text-sm font-medium">Totale Lordo {{ selectedYear }}</CardTitle>
						<Euro class="h-4 w-4 text-muted-foreground" />
					</CardHeader>
					<CardContent>
						<div class="text-2xl font-bold">
							{{ formatCurrency(statistics.total_gross) }}
						</div>
						<p class="text-xs text-muted-foreground">
							Tasse: {{ formatCurrency(statistics.total_taxes) }}
						</p>
					</CardContent>
				</Card>

				<Card>
					<CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
						<CardTitle class="text-sm font-medium">Straordinari {{ selectedYear }}</CardTitle>
						<Calendar class="h-4 w-4 text-muted-foreground" />
					</CardHeader>
					<CardContent>
						<div class="text-2xl font-bold">
							{{ formatCurrency(statistics.total_overtime) }}
						</div>
						<p class="text-xs text-muted-foreground">
							Ore extra pagate
						</p>
					</CardContent>
				</Card>
			</div>

			<!-- Charts and Statistics -->
			<div class="grid gap-6 lg:grid-cols-2">
				<SalaryChart :data="statistics.monthly_data" :year="selectedYear" />
				<SalaryStatistics :statistics="statistics" />
			</div>

			<!-- Salary Table -->
			<SalaryTable :salaries="salaries" />
		</div>
	</AppLayout>
</template>
