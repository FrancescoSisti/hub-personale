<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Link } from '@inertiajs/vue3'
import { Euro, TrendingUp, Calendar, Plus } from 'lucide-vue-next'

interface SalaryData {
	currentMonthSalary?: {
		net_salary: number
		gross_salary: number
		month: number
		year: number
	}
	yearlyTotal: number
	monthsRecorded: number
	averageMonthly: number
}

interface Props {
	data: SalaryData
}

const props = defineProps<Props>()

const formatCurrency = (amount: number): string => {
	return new Intl.NumberFormat('it-IT', {
		style: 'currency',
		currency: 'EUR',
	}).format(amount)
}

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
	if (!props.data.currentMonthSalary) return 'N/A'
	return monthNames[props.data.currentMonthSalary.month - 1]
})
</script>

<template>
	<Card>
		<CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
			<div>
				<CardTitle class="text-lg font-semibold">Gestione Stipendi</CardTitle>
				<CardDescription>Riepilogo dei tuoi guadagni</CardDescription>
			</div>
			<Euro class="h-6 w-6 text-muted-foreground" />
		</CardHeader>
		<CardContent class="space-y-4">
			<!-- Stipendio Corrente -->
			<div class="flex items-center justify-between">
				<div>
					<p class="text-sm font-medium text-muted-foreground">{{ currentMonthName }}</p>
					<p class="text-2xl font-bold text-green-600">
						{{
							data.currentMonthSalary
								? formatCurrency(data.currentMonthSalary.net_salary)
								: 'Non registrato'
						}}
					</p>
				</div>
				<div class="text-right">
					<p class="text-xs text-muted-foreground">Lordo</p>
					<p class="text-sm font-medium">
						{{
							data.currentMonthSalary
								? formatCurrency(data.currentMonthSalary.gross_salary)
								: '-'
						}}
					</p>
				</div>
			</div>

			<!-- Statistiche Annuali -->
			<div class="grid grid-cols-2 gap-4 pt-2">
				<div class="space-y-1">
					<div class="flex items-center gap-1">
						<TrendingUp class="h-3 w-3 text-muted-foreground" />
						<p class="text-xs text-muted-foreground">Totale Anno</p>
					</div>
					<p class="text-lg font-bold">{{ formatCurrency(data.yearlyTotal) }}</p>
				</div>
				<div class="space-y-1">
					<div class="flex items-center gap-1">
						<Calendar class="h-3 w-3 text-muted-foreground" />
						<p class="text-xs text-muted-foreground">Media Mensile</p>
					</div>
					<p class="text-lg font-bold">{{ formatCurrency(data.averageMonthly) }}</p>
				</div>
			</div>

			<!-- Progress Bar -->
			<div class="space-y-2">
				<div class="flex justify-between text-xs text-muted-foreground">
					<span>Mesi registrati</span>
					<span>{{ data.monthsRecorded }}/12</span>
				</div>
				<div class="w-full bg-gray-200 rounded-full h-2">
					<div
						class="bg-blue-600 h-2 rounded-full transition-all duration-300"
						:style="{ width: `${(data.monthsRecorded / 12) * 100}%` }"
					></div>
				</div>
			</div>

			<!-- Actions -->
			<div class="flex gap-2 pt-2">
				<Link href="/salaries" class="flex-1">
					<Button variant="outline" size="sm" class="w-full">
						<Euro class="mr-2 h-4 w-4" />
						Visualizza Tutto
					</Button>
				</Link>
				<Link href="/salaries/create">
					<Button size="sm">
						<Plus class="mr-2 h-4 w-4" />
						Aggiungi
					</Button>
				</Link>
			</div>
		</CardContent>
	</Card>
</template>
