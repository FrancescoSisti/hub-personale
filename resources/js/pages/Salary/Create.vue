<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Save, Calculator } from 'lucide-vue-next'

const breadcrumbs: BreadcrumbItem[] = [
	{ title: 'Dashboard', href: '/dashboard' },
	{ title: 'Stipendi', href: '/salaries' },
	{ title: 'Nuovo Stipendio', href: '/salaries/create' },
]

const form = useForm({
	base_salary: '',
	bonus: '',
	overtime_hours: '',
	overtime_rate: '',
	deductions: '',
	tax_amount: '',
	month: new Date().getMonth() + 1,
	year: new Date().getFullYear(),
	notes: '',
})

const monthOptions = [
	{ value: 1, label: 'Gennaio' },
	{ value: 2, label: 'Febbraio' },
	{ value: 3, label: 'Marzo' },
	{ value: 4, label: 'Aprile' },
	{ value: 5, label: 'Maggio' },
	{ value: 6, label: 'Giugno' },
	{ value: 7, label: 'Luglio' },
	{ value: 8, label: 'Agosto' },
	{ value: 9, label: 'Settembre' },
	{ value: 10, label: 'Ottobre' },
	{ value: 11, label: 'Novembre' },
	{ value: 12, label: 'Dicembre' },
]

const yearOptions = computed(() => {
	const currentYear = new Date().getFullYear()
	const years = []
	for (let i = currentYear - 2; i <= currentYear + 1; i++) {
		years.push({ value: i, label: i.toString() })
	}
	return years
})

const formatCurrency = (amount: number): string => {
	return new Intl.NumberFormat('it-IT', {
		style: 'currency',
		currency: 'EUR',
	}).format(amount)
}

const calculatedGross = computed(() => {
	const base = parseFloat(form.base_salary) || 0
	const bonus = parseFloat(form.bonus) || 0
	const overtimeHours = parseFloat(form.overtime_hours) || 0
	const overtimeRate = parseFloat(form.overtime_rate) || 0
	return base + bonus + overtimeHours * overtimeRate
})

const calculatedNet = computed(() => {
	const gross = calculatedGross.value
	const taxes = parseFloat(form.tax_amount) || 0
	const deductions = parseFloat(form.deductions) || 0
	return gross - taxes - deductions
})

const submit = () => {
	form.post('/salaries', {
		onSuccess: () => {
			// Redirect handled by Laravel
		},
	})
}
</script>

<template>
	<Head title="Nuovo Stipendio" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-1 flex-col gap-6 p-6">
			<div class="flex items-center justify-between">
				<div>
					<h1 class="text-3xl font-bold tracking-tight">Nuovo Stipendio</h1>
					<p class="text-muted-foreground">Registra un nuovo stipendio mensile</p>
				</div>
			</div>

			<div class="grid gap-6 lg:grid-cols-3">
				<!-- Form -->
				<div class="lg:col-span-2">
					<Card>
						<CardHeader>
							<CardTitle>Dettagli Stipendio</CardTitle>
							<CardDescription>Inserisci i dettagli del tuo stipendio mensile</CardDescription>
						</CardHeader>
						<CardContent>
							<form @submit.prevent="submit" class="space-y-6">
								<!-- Periodo -->
								<div class="grid gap-4 md:grid-cols-2">
									<div class="space-y-2">
										<Label for="month">Mese</Label>
										<Select
											:model-value="form.month.toString()"
											@update:model-value="(value: string) => (form.month = parseInt(value))"
										>
											<SelectTrigger>
												<SelectValue placeholder="Seleziona mese" />
											</SelectTrigger>
											<SelectContent>
												<SelectItem
													v-for="month in monthOptions"
													:key="month.value"
													:value="month.value.toString()"
												>
													{{ month.label }}
												</SelectItem>
											</SelectContent>
										</Select>
										<div v-if="form.errors.month" class="text-sm text-red-600">
											{{ form.errors.month }}
										</div>
									</div>

									<div class="space-y-2">
										<Label for="year">Anno</Label>
										<Select
											:model-value="form.year.toString()"
											@update:model-value="(value: string) => (form.year = parseInt(value))"
										>
											<SelectTrigger>
												<SelectValue placeholder="Seleziona anno" />
											</SelectTrigger>
											<SelectContent>
												<SelectItem
													v-for="year in yearOptions"
													:key="year.value"
													:value="year.value.toString()"
												>
													{{ year.label }}
												</SelectItem>
											</SelectContent>
										</Select>
										<div v-if="form.errors.year" class="text-sm text-red-600">
											{{ form.errors.year }}
										</div>
									</div>
								</div>

								<!-- Stipendio Base -->
								<div class="space-y-2">
									<Label for="base_salary">Stipendio Base *</Label>
									<Input
										id="base_salary"
										v-model="form.base_salary"
										type="number"
										step="0.01"
										placeholder="0.00"
										required
									/>
									<div v-if="form.errors.base_salary" class="text-sm text-red-600">
										{{ form.errors.base_salary }}
									</div>
								</div>

								<!-- Bonus e Straordinari -->
								<div class="grid gap-4 md:grid-cols-2">
									<div class="space-y-2">
										<Label for="bonus">Bonus</Label>
										<Input
											id="bonus"
											v-model="form.bonus"
											type="number"
											step="0.01"
											placeholder="0.00"
										/>
										<div v-if="form.errors.bonus" class="text-sm text-red-600">
											{{ form.errors.bonus }}
										</div>
									</div>

									<div class="space-y-2">
										<Label for="overtime_hours">Ore Straordinari</Label>
										<Input
											id="overtime_hours"
											v-model="form.overtime_hours"
											type="number"
											step="0.01"
											placeholder="0.00"
										/>
										<div v-if="form.errors.overtime_hours" class="text-sm text-red-600">
											{{ form.errors.overtime_hours }}
										</div>
									</div>
								</div>

								<div class="space-y-2">
									<Label for="overtime_rate">Tariffa Straordinari (€/ora)</Label>
									<Input
										id="overtime_rate"
										v-model="form.overtime_rate"
										type="number"
										step="0.01"
										placeholder="0.00"
									/>
									<div v-if="form.errors.overtime_rate" class="text-sm text-red-600">
										{{ form.errors.overtime_rate }}
									</div>
								</div>

								<!-- Tasse e Detrazioni -->
								<div class="grid gap-4 md:grid-cols-2">
									<div class="space-y-2">
										<Label for="tax_amount">Tasse</Label>
										<Input
											id="tax_amount"
											v-model="form.tax_amount"
											type="number"
											step="0.01"
											placeholder="0.00"
										/>
										<div v-if="form.errors.tax_amount" class="text-sm text-red-600">
											{{ form.errors.tax_amount }}
										</div>
									</div>

									<div class="space-y-2">
										<Label for="deductions">Detrazioni</Label>
										<Input
											id="deductions"
											v-model="form.deductions"
											type="number"
											step="0.01"
											placeholder="0.00"
										/>
										<div v-if="form.errors.deductions" class="text-sm text-red-600">
											{{ form.errors.deductions }}
										</div>
									</div>
								</div>

								<!-- Note -->
								<div class="space-y-2">
									<Label for="notes">Note</Label>
									<Textarea
										id="notes"
										v-model="form.notes"
										placeholder="Note aggiuntive..."
										rows="3"
									/>
									<div v-if="form.errors.notes" class="text-sm text-red-600">
										{{ form.errors.notes }}
									</div>
								</div>

								<!-- Submit Button -->
								<Button type="submit" :disabled="form.processing" class="w-full">
									<Save class="mr-2 h-4 w-4" />
									{{ form.processing ? 'Salvataggio...' : 'Salva Stipendio' }}
								</Button>
							</form>
						</CardContent>
					</Card>
				</div>

				<!-- Preview -->
				<div>
					<Card>
						<CardHeader>
							<CardTitle class="flex items-center gap-2">
								<Calculator class="h-5 w-5" />
								Anteprima Calcoli
							</CardTitle>
							<CardDescription>Riepilogo automatico dei calcoli</CardDescription>
						</CardHeader>
						<CardContent class="space-y-4">
							<div class="space-y-3">
								<div class="flex justify-between">
									<span class="text-sm text-muted-foreground">Stipendio Base:</span>
									<span class="font-medium">
										{{ formatCurrency(parseFloat(form.base_salary) || 0) }}
									</span>
								</div>

								<div class="flex justify-between">
									<span class="text-sm text-muted-foreground">Bonus:</span>
									<span class="font-medium">
										{{ formatCurrency(parseFloat(form.bonus) || 0) }}
									</span>
								</div>

								<div class="flex justify-between">
									<span class="text-sm text-muted-foreground">Straordinari:</span>
									<span class="font-medium">
										{{
											formatCurrency(
												(parseFloat(form.overtime_hours) || 0) *
													(parseFloat(form.overtime_rate) || 0)
											)
										}}
									</span>
								</div>

								<hr />

								<div class="flex justify-between">
									<span class="font-medium">Stipendio Lordo:</span>
									<span class="font-bold text-lg">{{ formatCurrency(calculatedGross) }}</span>
								</div>

								<div class="flex justify-between">
									<span class="text-sm text-muted-foreground">Tasse:</span>
									<span class="font-medium text-red-600">
										-{{ formatCurrency(parseFloat(form.tax_amount) || 0) }}
									</span>
								</div>

								<div class="flex justify-between">
									<span class="text-sm text-muted-foreground">Detrazioni:</span>
									<span class="font-medium text-orange-600">
										-{{ formatCurrency(parseFloat(form.deductions) || 0) }}
									</span>
								</div>

								<hr />

								<div class="flex justify-between">
									<span class="font-medium">Stipendio Netto:</span>
									<span class="font-bold text-xl text-green-600">
										{{ formatCurrency(calculatedNet) }}
									</span>
								</div>
							</div>
						</CardContent>
					</Card>
				</div>
			</div>
		</div>
	</AppLayout>
</template>
