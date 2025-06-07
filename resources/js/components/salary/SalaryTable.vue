<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Link } from '@inertiajs/vue3'
import { Edit, Eye, Trash2 } from 'lucide-vue-next'

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

interface Props {
	salaries: Salary[]
}

const props = defineProps<Props>()

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

const formatCurrency = (amount: number): string => {
	return new Intl.NumberFormat('it-IT', {
		style: 'currency',
		currency: 'EUR',
	}).format(amount)
}

const getMonthName = (month: number): string => {
	return monthNames[month - 1] || 'N/A'
}

const sortedSalaries = computed(() => {
	return [...props.salaries].sort((a, b) => {
		if (a.year !== b.year) return b.year - a.year
		return b.month - a.month
	})
})
</script>

<template>
	<Card>
		<CardHeader>
			<CardTitle>Storico Stipendi</CardTitle>
			<CardDescription>Elenco completo degli stipendi registrati</CardDescription>
		</CardHeader>
		<CardContent>
			<div v-if="salaries.length === 0" class="text-center py-8">
				<p class="text-muted-foreground">Nessuno stipendio registrato</p>
				<Link href="/salaries/create">
					<Button class="mt-4">Aggiungi il primo stipendio</Button>
				</Link>
			</div>

			<div v-else class="overflow-x-auto">
				<table class="w-full">
					<thead>
						<tr class="border-b">
							<th class="text-left py-3 px-4 font-medium">Periodo</th>
							<th class="text-right py-3 px-4 font-medium">Lordo</th>
							<th class="text-right py-3 px-4 font-medium">Netto</th>
							<th class="text-right py-3 px-4 font-medium">Tasse</th>
							<th class="text-right py-3 px-4 font-medium">Bonus</th>
							<th class="text-right py-3 px-4 font-medium">Straordinari</th>
							<th class="text-center py-3 px-4 font-medium">Azioni</th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-for="salary in sortedSalaries"
							:key="salary.id"
							class="border-b hover:bg-muted/50 transition-colors"
						>
							<td class="py-3 px-4">
								<div>
									<p class="font-medium">{{ getMonthName(salary.month) }} {{ salary.year }}</p>
									<p v-if="salary.notes" class="text-sm text-muted-foreground truncate max-w-32">
										{{ salary.notes }}
									</p>
								</div>
							</td>
							<td class="text-right py-3 px-4 font-medium">
								{{ formatCurrency(salary.gross_salary) }}
							</td>
							<td class="text-right py-3 px-4 font-medium text-green-600">
								{{ formatCurrency(salary.net_salary) }}
							</td>
							<td class="text-right py-3 px-4 text-red-600">
								{{ formatCurrency(salary.tax_amount) }}
							</td>
							<td class="text-right py-3 px-4">
								{{ salary.bonus > 0 ? formatCurrency(salary.bonus) : '-' }}
							</td>
							<td class="text-right py-3 px-4">
								{{
									salary.overtime_hours > 0
										? formatCurrency(salary.overtime_hours * salary.overtime_rate)
										: '-'
								}}
							</td>
							<td class="text-center py-3 px-4">
								<div class="flex items-center justify-center gap-2">
									<Link :href="`/salaries/${salary.id}`">
										<Button variant="ghost" size="sm">
											<Eye class="h-4 w-4" />
										</Button>
									</Link>
									<Link :href="`/salaries/${salary.id}/edit`">
										<Button variant="ghost" size="sm">
											<Edit class="h-4 w-4" />
										</Button>
									</Link>
									<Button variant="ghost" size="sm" class="text-red-600 hover:text-red-700">
										<Trash2 class="h-4 w-4" />
									</Button>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</CardContent>
	</Card>
</template>
