<script setup lang="ts">
import { ref, watch } from 'vue'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Edit, Save, X } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'

interface PaySlip {
	id: number
	file_name: string
	extracted_data?: {
		base_salary?: number
		bonus?: number
		overtime_hours?: number
		overtime_rate?: number
		taxes?: number
		deductions?: number
		gross_salary?: number
		net_salary?: number
		month?: number
		year?: number
	}
}

interface Props {
	paySlip: PaySlip | null
	open: boolean
}

interface Emits {
	(e: 'update:open', value: boolean): void
	(e: 'saved'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const formData = ref({
	base_salary: 0,
	bonus: 0,
	overtime_hours: 0,
	overtime_rate: 0,
	taxes: 0,
	deductions: 0,
	gross_salary: 0,
	net_salary: 0,
	month: new Date().getMonth() + 1,
	year: new Date().getFullYear(),
})

const loading = ref(false)

const months = [
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

const currentYear = new Date().getFullYear()
const years = Array.from({ length: 10 }, (_, i) => currentYear - 5 + i)

watch(() => props.paySlip, (paySlip) => {
	if (paySlip?.extracted_data) {
		formData.value = {
			base_salary: paySlip.extracted_data.base_salary || 0,
			bonus: paySlip.extracted_data.bonus || 0,
			overtime_hours: paySlip.extracted_data.overtime_hours || 0,
			overtime_rate: paySlip.extracted_data.overtime_rate || 0,
			taxes: paySlip.extracted_data.taxes || 0,
			deductions: paySlip.extracted_data.deductions || 0,
			gross_salary: paySlip.extracted_data.gross_salary || 0,
			net_salary: paySlip.extracted_data.net_salary || 0,
			month: paySlip.extracted_data.month || new Date().getMonth() + 1,
			year: paySlip.extracted_data.year || new Date().getFullYear(),
		}
	}
}, { immediate: true })

const closeDialog = () => {
	emit('update:open', false)
}

const saveData = async () => {
	if (!props.paySlip) return

	loading.value = true

	try {
		await router.patch(`/pay-slips/${props.paySlip.id}/update-data`, formData.value, {
			onSuccess: () => {
				emit('saved')
				closeDialog()
			},
		})
	} catch (error) {
		console.error('Errore nel salvataggio:', error)
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<Dialog :open="open" @update:open="closeDialog">
		<DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
			<DialogHeader>
				<DialogTitle class="flex items-center gap-2">
					<Edit class="h-5 w-5" />
					Modifica Dati Estratti
				</DialogTitle>
				<DialogDescription>
					Modifica manualmente i dati estratti dalla busta paga:
					<strong>{{ paySlip?.file_name }}</strong>
				</DialogDescription>
			</DialogHeader>

			<div class="grid gap-6 py-4">
				<!-- Periodo -->
				<div class="grid grid-cols-2 gap-4">
					<div class="space-y-2">
						<Label for="month">Mese</Label>
						<Select v-model="formData.month">
							<SelectTrigger>
								<SelectValue placeholder="Seleziona mese" />
							</SelectTrigger>
							<SelectContent>
								<SelectItem
									v-for="month in months"
									:key="month.value"
									:value="month.value.toString()"
								>
									{{ month.label }}
								</SelectItem>
							</SelectContent>
						</Select>
					</div>
					<div class="space-y-2">
						<Label for="year">Anno</Label>
						<Select v-model="formData.year">
							<SelectTrigger>
								<SelectValue placeholder="Seleziona anno" />
							</SelectTrigger>
							<SelectContent>
								<SelectItem
									v-for="year in years"
									:key="year"
									:value="year.toString()"
								>
									{{ year }}
								</SelectItem>
							</SelectContent>
						</Select>
					</div>
				</div>

				<!-- Stipendi -->
				<div class="grid grid-cols-2 gap-4">
					<div class="space-y-2">
						<Label for="base_salary">Stipendio Base (€)</Label>
						<Input
							id="base_salary"
							v-model.number="formData.base_salary"
							type="number"
							step="0.01"
							min="0"
							placeholder="0.00"
						/>
					</div>
					<div class="space-y-2">
						<Label for="bonus">Bonus/Premi (€)</Label>
						<Input
							id="bonus"
							v-model.number="formData.bonus"
							type="number"
							step="0.01"
							min="0"
							placeholder="0.00"
						/>
					</div>
				</div>

				<!-- Straordinari -->
				<div class="grid grid-cols-2 gap-4">
					<div class="space-y-2">
						<Label for="overtime_hours">Ore Straordinari</Label>
						<Input
							id="overtime_hours"
							v-model.number="formData.overtime_hours"
							type="number"
							step="0.01"
							min="0"
							placeholder="0.00"
						/>
					</div>
					<div class="space-y-2">
						<Label for="overtime_rate">Tariffa Straordinari (€/h)</Label>
						<Input
							id="overtime_rate"
							v-model.number="formData.overtime_rate"
							type="number"
							step="0.01"
							min="0"
							placeholder="0.00"
						/>
					</div>
				</div>

				<!-- Tasse e Trattenute -->
				<div class="grid grid-cols-2 gap-4">
					<div class="space-y-2">
						<Label for="taxes">Tasse/IRPEF (€)</Label>
						<Input
							id="taxes"
							v-model.number="formData.taxes"
							type="number"
							step="0.01"
							min="0"
							placeholder="0.00"
						/>
					</div>
					<div class="space-y-2">
						<Label for="deductions">Contributi/Trattenute (€)</Label>
						<Input
							id="deductions"
							v-model.number="formData.deductions"
							type="number"
							step="0.01"
							min="0"
							placeholder="0.00"
						/>
					</div>
				</div>

				<!-- Totali -->
				<div class="grid grid-cols-2 gap-4">
					<div class="space-y-2">
						<Label for="gross_salary">Totale Lordo (€)</Label>
						<Input
							id="gross_salary"
							v-model.number="formData.gross_salary"
							type="number"
							step="0.01"
							min="0"
							placeholder="0.00"
						/>
					</div>
					<div class="space-y-2">
						<Label for="net_salary">Totale Netto (€)</Label>
						<Input
							id="net_salary"
							v-model.number="formData.net_salary"
							type="number"
							step="0.01"
							min="0"
							placeholder="0.00"
						/>
					</div>
				</div>
			</div>

			<DialogFooter>
				<Button variant="outline" @click="closeDialog" :disabled="loading">
					<X class="mr-2 h-4 w-4" />
					Annulla
				</Button>
				<Button @click="saveData" :disabled="loading">
					<Save class="mr-2 h-4 w-4" />
					{{ loading ? 'Salvataggio...' : 'Salva Modifiche' }}
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
