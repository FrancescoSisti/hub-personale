<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog'
import { Upload, FileX, Loader2, CheckCircle, XCircle } from 'lucide-vue-next'

interface Props {
	show: boolean
}

interface Emits {
	(e: 'close'): void
	(e: 'upload-complete'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const fileInput = ref<HTMLInputElement>()
const selectedFile = ref<File>()
const uploading = ref(false)
const uploadProgress = ref(0)
const uploadError = ref<string>()
const uploadSuccess = ref(false)
const isDragOver = ref(false)

const acceptedFileTypes = computed(() => ['application/pdf'])
const maxFileSize = computed(() => 10 * 1024 * 1024) // 10MB

const handleFileSelect = (event: Event) => {
	const target = event.target as HTMLInputElement
	const file = target.files?.[0]
	if (file) {
		validateAndSetFile(file)
	}
}

const handleDrop = (event: DragEvent) => {
	event.preventDefault()
	isDragOver.value = false

	const file = event.dataTransfer?.files[0]
	if (file) {
		validateAndSetFile(file)
	}
}

const handleDragOver = (event: DragEvent) => {
	event.preventDefault()
	isDragOver.value = true
}

const handleDragLeave = () => {
	isDragOver.value = false
}

const validateAndSetFile = (file: File) => {
	uploadError.value = undefined

	// Verifica tipo file
	if (!acceptedFileTypes.value.includes(file.type)) {
		uploadError.value = 'Sono accettati solo file PDF'
		return
	}

	// Verifica dimensione
	if (file.size > maxFileSize.value) {
		uploadError.value = 'Il file non può essere più grande di 10MB'
		return
	}

	selectedFile.value = file
}

const triggerFileInput = () => {
	fileInput.value?.click()
}

const removeFile = () => {
	selectedFile.value = undefined
	uploadError.value = undefined
	if (fileInput.value) {
		fileInput.value.value = ''
	}
}

const uploadFile = async () => {
	if (!selectedFile.value) return

	uploading.value = true
	uploadError.value = undefined
	uploadProgress.value = 0

	const formData = new FormData()
	formData.append('file', selectedFile.value)

	try {
		// Simula progresso upload
		const progressInterval = setInterval(() => {
			uploadProgress.value = Math.min(uploadProgress.value + 10, 90)
		}, 200)

		const response = await fetch('/pay-slips/upload', {
			method: 'POST',
			body: formData,
			headers: {
				'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
			},
		})

		clearInterval(progressInterval)
		uploadProgress.value = 100

		if (!response.ok) {
			const errorData = await response.json()
			throw new Error(errorData.message || 'Errore durante l\'upload')
		}

		const result = await response.json()
		uploadSuccess.value = true

		// Chiudi il modal dopo un breve delay
		setTimeout(() => {
			resetForm()
			emit('upload-complete')
		}, 1500)

	} catch (error) {
		uploadError.value = error instanceof Error ? error.message : 'Errore durante l\'upload'
		uploadProgress.value = 0
	} finally {
		uploading.value = false
	}
}

const resetForm = () => {
	selectedFile.value = undefined
	uploading.value = false
	uploadProgress.value = 0
	uploadError.value = undefined
	uploadSuccess.value = false
	isDragOver.value = false
	if (fileInput.value) {
		fileInput.value.value = ''
	}
}

const handleClose = () => {
	if (!uploading.value) {
		resetForm()
		emit('close')
	}
}

const formatFileSize = (bytes: number): string => {
	if (bytes === 0) return '0 Bytes'
	const k = 1024
	const sizes = ['Bytes', 'KB', 'MB', 'GB']
	const i = Math.floor(Math.log(bytes) / Math.log(k))
	return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>

<template>
	<Dialog :open="show" @update:open="handleClose">
		<DialogContent class="sm:max-w-md">
			<DialogHeader>
				<DialogTitle>Carica Busta Paga</DialogTitle>
				<DialogDescription>
					Carica un file PDF della tua busta paga per l'elaborazione automatica
				</DialogDescription>
			</DialogHeader>

			<div class="space-y-4">
				<!-- Upload Area -->
				<div
					v-if="!selectedFile"
					class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
					:class="{
						'border-primary bg-primary/5': isDragOver,
						'border-muted-foreground/25': !isDragOver,
					}"
					@drop="handleDrop"
					@dragover="handleDragOver"
					@dragleave="handleDragLeave"
					@click="triggerFileInput"
				>
					<Upload class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
					<div class="space-y-2">
						<p class="text-sm font-medium">
							Trascina qui il file PDF o clicca per selezionare
						</p>
						<p class="text-xs text-muted-foreground">
							Formato: PDF • Dimensione massima: 10MB
						</p>
					</div>
				</div>

				<!-- Selected File -->
				<Card v-if="selectedFile && !uploadSuccess">
					<CardContent class="p-4">
						<div class="flex items-center justify-between">
							<div class="flex items-center space-x-3">
								<div class="p-2 bg-red-100 rounded">
									<FileX class="h-4 w-4 text-red-600" />
								</div>
								<div>
									<p class="text-sm font-medium">{{ selectedFile.name }}</p>
									<p class="text-xs text-muted-foreground">
										{{ formatFileSize(selectedFile.size) }}
									</p>
								</div>
							</div>
							<Button
								v-if="!uploading"
								variant="ghost"
								size="sm"
								@click="removeFile"
							>
								<XCircle class="h-4 w-4" />
							</Button>
						</div>

						<!-- Progress Bar -->
						<div v-if="uploading" class="mt-4 space-y-2">
							<div class="flex items-center justify-between text-xs">
								<span>Caricamento...</span>
								<span>{{ uploadProgress }}%</span>
							</div>
							<div class="w-full bg-gray-200 rounded-full h-2">
								<div
									class="bg-primary h-2 rounded-full transition-all duration-300"
									:style="{ width: `${uploadProgress}%` }"
								></div>
							</div>
						</div>
					</CardContent>
				</Card>

				<!-- Success Message -->
				<Card v-if="uploadSuccess" class="border-green-200 bg-green-50">
					<CardContent class="p-4">
						<div class="flex items-center space-x-3">
							<CheckCircle class="h-5 w-5 text-green-600" />
							<div>
								<p class="text-sm font-medium text-green-800">
									Busta paga caricata con successo!
								</p>
								<p class="text-xs text-green-600">
									Il sistema sta elaborando il file...
								</p>
							</div>
						</div>
					</CardContent>
				</Card>

				<!-- Error Message -->
				<Card v-if="uploadError" class="border-red-200 bg-red-50">
					<CardContent class="p-4">
						<div class="flex items-center space-x-3">
							<XCircle class="h-5 w-5 text-red-600" />
							<div>
								<p class="text-sm font-medium text-red-800">Errore</p>
								<p class="text-xs text-red-600">{{ uploadError }}</p>
							</div>
						</div>
					</CardContent>
				</Card>

				<!-- Actions -->
				<div class="flex justify-end space-x-2">
					<Button
						variant="outline"
						@click="handleClose"
						:disabled="uploading"
					>
						Annulla
					</Button>
					<Button
						@click="uploadFile"
						:disabled="!selectedFile || uploading || uploadSuccess"
					>
						<Loader2 v-if="uploading" class="mr-2 h-4 w-4 animate-spin" />
						<Upload v-else class="mr-2 h-4 w-4" />
						{{ uploading ? 'Caricamento...' : 'Carica' }}
					</Button>
				</div>

				<!-- Hidden File Input -->
				<input
					ref="fileInput"
					type="file"
					accept=".pdf"
					class="hidden"
					@change="handleFileSelect"
				/>
			</div>
		</DialogContent>
	</Dialog>
</template>
