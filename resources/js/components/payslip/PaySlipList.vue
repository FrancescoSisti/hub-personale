<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
} from '@/components/ui/table'
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
	AlertDialog,
	AlertDialogAction,
	AlertDialogCancel,
	AlertDialogContent,
	AlertDialogDescription,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
	FileText,
	Download,
	Trash2,
	MoreHorizontal,
	CheckCircle,
	XCircle,
	Clock,
	Zap,
	Euro,
} from 'lucide-vue-next'
import { ref } from 'vue'

interface PaySlip {
	id: number
	file_name: string
	file_size: number
	processed: boolean
	processed_at?: string
	processing_error?: string
	month?: number
	year?: number
	created_at: string
	salary?: {
		id: number
		net_salary: number
		gross_salary: number
	}
}

interface Props {
	paySlips: PaySlip[]
}

const props = defineProps<Props>()

const deletePaySlipId = ref<number | null>(null)

const formatFileSize = (bytes: number): string => {
	if (bytes === 0) return '0 Bytes'
	const k = 1024
	const sizes = ['Bytes', 'KB', 'MB', 'GB']
	const i = Math.floor(Math.log(bytes) / Math.log(k))
	return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (dateString: string): string => {
	return new Date(dateString).toLocaleDateString('it-IT', {
		day: '2-digit',
		month: '2-digit',
		year: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
	})
}

const formatPeriod = (month?: number, year?: number): string => {
	if (!month || !year) return 'Non determinato'

	const monthNames = [
		'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
		'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'
	]

	return `${monthNames[month - 1]} ${year}`
}

const formatCurrency = (amount: number): string => {
	return new Intl.NumberFormat('it-IT', {
		style: 'currency',
		currency: 'EUR',
	}).format(amount)
}

const getStatusBadge = (paySlip: PaySlip) => {
	if (paySlip.processing_error) {
		return {
			variant: 'destructive' as const,
			icon: XCircle,
			text: 'Errore',
		}
	}

	if (paySlip.processed) {
		return {
			variant: 'default' as const,
			icon: CheckCircle,
			text: 'Elaborata',
		}
	}

	return {
		variant: 'secondary' as const,
		icon: Clock,
		text: 'In elaborazione',
	}
}

const downloadPaySlip = (paySlip: PaySlip) => {
	const link = document.createElement('a')
	link.href = `/pay-slips/${paySlip.id}/download`
	link.click()
}

const processPaySlip = async (paySlip: PaySlip) => {
	try {
		await router.post(`/pay-slips/${paySlip.id}/process`)
		// La pagina si ricaricherà automaticamente grazie a Inertia
	} catch (error) {
		console.error('Errore nel processing:', error)
	}
}

const confirmDelete = (paySlipId: number) => {
	deletePaySlipId.value = paySlipId
}

const deletePaySlip = async () => {
	if (!deletePaySlipId.value) return

	try {
		await router.delete(`/pay-slips/${deletePaySlipId.value}`)
		deletePaySlipId.value = null
	} catch (error) {
		console.error('Errore nella cancellazione:', error)
	}
}

const viewSalary = (salaryId: number) => {
	router.get(`/salaries`)
}
</script>

<template>
	<Card>
		<CardHeader>
			<CardTitle class="flex items-center gap-2">
				<FileText class="h-5 w-5" />
				Buste Paga Caricate
			</CardTitle>
		</CardHeader>
		<CardContent>
			<Table>
				<TableHeader>
					<TableRow>
						<TableHead>File</TableHead>
						<TableHead>Periodo</TableHead>
						<TableHead>Stato</TableHead>
						<TableHead>Stipendio</TableHead>
						<TableHead>Caricato</TableHead>
						<TableHead class="text-right">Azioni</TableHead>
					</TableRow>
				</TableHeader>
				<TableBody>
					<TableRow v-for="paySlip in paySlips" :key="paySlip.id">
						<TableCell>
							<div class="space-y-1">
								<p class="font-medium text-sm">{{ paySlip.file_name }}</p>
								<p class="text-xs text-muted-foreground">
									{{ formatFileSize(paySlip.file_size) }}
								</p>
							</div>
						</TableCell>

						<TableCell>
							{{ formatPeriod(paySlip.month, paySlip.year) }}
						</TableCell>

						<TableCell>
							<Badge
								:variant="getStatusBadge(paySlip).variant"
								class="flex items-center gap-1 w-fit"
							>
								<component
									:is="getStatusBadge(paySlip).icon"
									class="h-3 w-3"
								/>
								{{ getStatusBadge(paySlip).text }}
							</Badge>

							<p
								v-if="paySlip.processing_error"
								class="text-xs text-red-600 mt-1"
							>
								{{ paySlip.processing_error }}
							</p>
						</TableCell>

						<TableCell>
							<div v-if="paySlip.salary" class="space-y-1">
								<p class="text-sm font-medium text-green-600">
									{{ formatCurrency(paySlip.salary.net_salary) }}
								</p>
								<p class="text-xs text-muted-foreground">
									Netto
								</p>
							</div>
							<span v-else class="text-xs text-muted-foreground">
								Non disponibile
							</span>
						</TableCell>

						<TableCell>
							<div class="space-y-1">
								<p class="text-sm">{{ formatDate(paySlip.created_at) }}</p>
								<p
									v-if="paySlip.processed_at"
									class="text-xs text-muted-foreground"
								>
									Elaborata: {{ formatDate(paySlip.processed_at) }}
								</p>
							</div>
						</TableCell>

						<TableCell class="text-right">
							<DropdownMenu>
								<DropdownMenuTrigger as-child>
									<Button variant="ghost" size="sm">
										<MoreHorizontal class="h-4 w-4" />
									</Button>
								</DropdownMenuTrigger>
								<DropdownMenuContent align="end">
									<DropdownMenuItem @click="downloadPaySlip(paySlip)">
										<Download class="mr-2 h-4 w-4" />
										Scarica PDF
									</DropdownMenuItem>

									<DropdownMenuItem
										v-if="!paySlip.processed && !paySlip.processing_error"
										@click="processPaySlip(paySlip)"
									>
										<Zap class="mr-2 h-4 w-4" />
										Elabora Ora
									</DropdownMenuItem>

									<DropdownMenuItem
										v-if="paySlip.salary"
										@click="viewSalary(paySlip.salary.id)"
									>
										<Euro class="mr-2 h-4 w-4" />
										Vedi Stipendio
									</DropdownMenuItem>

									<DropdownMenuItem
										@click="confirmDelete(paySlip.id)"
										class="text-red-600"
									>
										<Trash2 class="mr-2 h-4 w-4" />
										Elimina
									</DropdownMenuItem>
								</DropdownMenuContent>
							</DropdownMenu>
						</TableCell>
					</TableRow>
				</TableBody>
			</Table>
		</CardContent>
	</Card>

	<!-- Delete Confirmation Dialog -->
	<AlertDialog :open="deletePaySlipId !== null" @update:open="deletePaySlipId = null">
		<AlertDialogContent>
			<AlertDialogHeader>
				<AlertDialogTitle>Conferma Eliminazione</AlertDialogTitle>
				<AlertDialogDescription>
					Sei sicuro di voler eliminare questa busta paga? Questa azione non può essere annullata.
					Se esiste uno stipendio collegato generato automaticamente, verrà anch'esso eliminato.
				</AlertDialogDescription>
			</AlertDialogHeader>
			<AlertDialogFooter>
				<AlertDialogCancel>Annulla</AlertDialogCancel>
				<AlertDialogAction
					@click="deletePaySlip"
					class="bg-red-600 hover:bg-red-700"
				>
					Elimina
				</AlertDialogAction>
			</AlertDialogFooter>
		</AlertDialogContent>
	</AlertDialog>
</template>
