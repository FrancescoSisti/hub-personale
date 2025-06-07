<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import PaySlipUpload from '@/components/payslip/PaySlipUpload.vue'
import PaySlipList from '@/components/payslip/PaySlipList.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Upload, FileText, Zap, CheckCircle, XCircle } from 'lucide-vue-next'
import { Alert, AlertDescription } from '@/components/ui/alert'

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
	message?: string
	errors?: Record<string, string[]>
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
	{ title: 'Dashboard', href: '/dashboard' },
	{ title: 'Stipendi', href: '/salaries' },
	{ title: 'Buste Paga', href: '/pay-slips' },
]

const showUpload = ref(false)

const formatFileSize = (bytes: number): string => {
	if (bytes === 0) return '0 Bytes'
	const k = 1024
	const sizes = ['Bytes', 'KB', 'MB', 'GB']
	const i = Math.floor(Math.log(bytes) / Math.log(k))
	return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const handleUploadComplete = () => {
	showUpload.value = false
	// Ricarica la pagina per mostrare la nuova busta paga
	router.reload()
}

const processedCount = props.paySlips.filter(p => p.processed).length
const errorCount = props.paySlips.filter(p => p.processing_error).length
const pendingCount = props.paySlips.filter(p => !p.processed && !p.processing_error).length
</script>

<template>
	<Head title="Buste Paga" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-1 flex-col gap-6 p-6">
			<!-- Header -->
			<div class="flex items-center justify-between">
				<div>
					<h1 class="text-3xl font-bold tracking-tight">Buste Paga</h1>
					<p class="text-muted-foreground">
						Carica le tue buste paga per generare automaticamente i dati degli stipendi
					</p>
				</div>
				<Button @click="showUpload = true">
					<Upload class="mr-2 h-4 w-4" />
					Carica Busta Paga
				</Button>
			</div>

			<!-- Messages -->
			<Alert v-if="message" class="border-green-200 bg-green-50">
				<CheckCircle class="h-4 w-4 text-green-600" />
				<AlertDescription class="text-green-800">
					{{ message }}
				</AlertDescription>
			</Alert>

			<Alert v-if="errors && Object.keys(errors).length > 0" variant="destructive">
				<XCircle class="h-4 w-4" />
				<AlertDescription>
					<div v-for="(errorList, field) in errors" :key="field">
						<span v-for="error in errorList" :key="error">{{ error }}</span>
					</div>
				</AlertDescription>
			</Alert>

			<!-- Statistics Cards -->
			<div class="grid gap-4 md:grid-cols-3">
				<Card>
					<CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
						<CardTitle class="text-sm font-medium">Elaborate</CardTitle>
						<Zap class="h-4 w-4 text-green-600" />
					</CardHeader>
					<CardContent>
						<div class="text-2xl font-bold text-green-600">{{ processedCount }}</div>
						<p class="text-xs text-muted-foreground">
							Buste paga elaborate con successo
						</p>
					</CardContent>
				</Card>

				<Card>
					<CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
						<CardTitle class="text-sm font-medium">In Elaborazione</CardTitle>
						<FileText class="h-4 w-4 text-blue-600" />
					</CardHeader>
					<CardContent>
						<div class="text-2xl font-bold text-blue-600">{{ pendingCount }}</div>
						<p class="text-xs text-muted-foreground">
							In attesa di elaborazione
						</p>
					</CardContent>
				</Card>

				<Card>
					<CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
						<CardTitle class="text-sm font-medium">Errori</CardTitle>
						<FileText class="h-4 w-4 text-red-600" />
					</CardHeader>
					<CardContent>
						<div class="text-2xl font-bold text-red-600">{{ errorCount }}</div>
						<p class="text-xs text-muted-foreground">
							Elaborazione fallita
						</p>
					</CardContent>
				</Card>
			</div>

			<!-- Info Card -->
			<Card v-if="paySlips.length === 0">
				<CardHeader>
					<CardTitle class="flex items-center gap-2">
						<Upload class="h-5 w-5" />
						Come Funziona
					</CardTitle>
					<CardDescription>
						Il sistema di elaborazione automatica delle buste paga
					</CardDescription>
				</CardHeader>
				<CardContent class="space-y-4">
					<div class="grid gap-4 md:grid-cols-3">
						<div class="space-y-2">
							<div class="flex items-center gap-2">
								<div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-600">
									1
								</div>
								<h3 class="font-medium">Carica PDF</h3>
							</div>
							<p class="text-sm text-muted-foreground">
								Carica la tua busta paga in formato PDF
							</p>
						</div>

						<div class="space-y-2">
							<div class="flex items-center gap-2">
								<div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-green-600">
									2
								</div>
								<h3 class="font-medium">Elaborazione</h3>
							</div>
							<p class="text-sm text-muted-foreground">
								Il sistema estrae automaticamente i dati
							</p>
						</div>

						<div class="space-y-2">
							<div class="flex items-center gap-2">
								<div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 text-purple-600">
									3
								</div>
								<h3 class="font-medium">Stipendio Creato</h3>
							</div>
							<p class="text-sm text-muted-foreground">
								I dati vengono salvati nella gestione stipendi
							</p>
						</div>
					</div>

					<div class="mt-6">
						<Button @click="showUpload = true" size="lg" class="w-full">
							<Upload class="mr-2 h-5 w-5" />
							Inizia Subito - Carica Prima Busta Paga
						</Button>
					</div>
				</CardContent>
			</Card>

			<!-- Pay Slips List -->
			<PaySlipList v-if="paySlips.length > 0" :paySlips="paySlips" />

			<!-- Upload Modal -->
			<PaySlipUpload
				:show="showUpload"
				@close="showUpload = false"
				@upload-complete="handleUploadComplete"
			/>
		</div>
	</AppLayout>
</template>
