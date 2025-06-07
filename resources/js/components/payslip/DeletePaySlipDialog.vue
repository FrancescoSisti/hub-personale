<script setup lang="ts">
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog'
import { Button } from '@/components/ui/button'
import { AlertTriangle, Trash2 } from 'lucide-vue-next'

interface PaySlip {
	id: number
	file_name: string
	processed: boolean
	salary?: {
		id: number
		auto_generated: boolean
	}
}

interface Props {
	paySlip: PaySlip | null
	open: boolean
}

interface Emits {
	(e: 'update:open', value: boolean): void
	(e: 'confirm'): void
}

defineProps<Props>()
const emit = defineEmits<Emits>()

const closeDialog = () => {
	emit('update:open', false)
}

const confirmDelete = () => {
	emit('confirm')
	closeDialog()
}
</script>

<template>
	<AlertDialog :open="open" @update:open="closeDialog">
		<AlertDialogContent>
			<AlertDialogHeader>
				<AlertDialogTitle class="flex items-center gap-2 text-red-600">
					<AlertTriangle class="h-5 w-5" />
					Conferma Eliminazione
				</AlertDialogTitle>
				<AlertDialogDescription class="space-y-2">
					<p>
						Sei sicuro di voler eliminare la busta paga
						<strong>{{ paySlip?.file_name }}</strong>?
					</p>

					<div v-if="paySlip?.salary?.auto_generated" class="rounded-md bg-amber-50 p-3 border border-amber-200">
						<div class="flex items-start gap-2">
							<AlertTriangle class="h-4 w-4 text-amber-600 mt-0.5" />
							<div class="text-sm">
								<p class="font-medium text-amber-800">Attenzione!</p>
								<p class="text-amber-700">
									Questa busta paga ha generato automaticamente un record stipendio.
									Eliminando la busta paga, anche il record stipendio verrà eliminato.
								</p>
							</div>
						</div>
					</div>

					<p class="text-sm text-muted-foreground">
						Questa azione è irreversibile.
					</p>
				</AlertDialogDescription>
			</AlertDialogHeader>
			<AlertDialogFooter>
				<AlertDialogCancel @click="closeDialog">
					Annulla
				</AlertDialogCancel>
				<AlertDialogAction
					@click="confirmDelete"
					class="bg-red-600 hover:bg-red-700 focus:ring-red-600"
				>
					<Trash2 class="mr-2 h-4 w-4" />
					Elimina Definitivamente
				</AlertDialogAction>
			</AlertDialogFooter>
		</AlertDialogContent>
	</AlertDialog>
</template>
