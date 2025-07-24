<template>
    <Head title="Dettaglio Contatto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 lg:gap-6 lg:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <!-- Back button and title section -->
                <div class="flex flex-col gap-3">
                    <Button variant="ghost" @click="goBack" class="self-start">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Indietro
                    </Button>
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold tracking-tight">Contatto da {{ contact.name }}</h1>
                        <div class="flex flex-col gap-2 mt-2 sm:flex-row sm:items-center sm:gap-4">
                            <Badge :variant="contact.read ? 'secondary' : 'default'" class="self-start">
                                {{ contact.read ? 'Letto' : 'Non letto' }}
                            </Badge>
                            <span class="text-sm text-muted-foreground">
                                Ricevuto il {{ formatDate(contact.created_at) }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Action buttons -->
                <div class="flex flex-col gap-2 sm:flex-row sm:gap-4 lg:items-center">
                    <Button variant="outline" @click="toggleReadStatus" size="sm" class="justify-start sm:justify-center">
                        <MailOpen v-if="!contact.read" class="h-4 w-4 mr-2" />
                        <Mail v-else class="h-4 w-4 mr-2" />
                        <span class="hidden sm:inline">{{ contact.read ? 'Marca come non letto' : 'Marca come letto' }}</span>
                        <span class="sm:hidden">{{ contact.read ? 'Non letto' : 'Letto' }}</span>
                    </Button>
                    <Button variant="destructive" @click="deleteContact" size="sm" class="justify-start sm:justify-center">
                        <Trash class="h-4 w-4 mr-2" />
                        Elimina
                    </Button>
                </div>
            </div>

            <div class="flex flex-col gap-6 lg:grid lg:grid-cols-3 lg:gap-6">
                <!-- Main Content -->
                <div class="order-1 lg:order-1 lg:col-span-2 space-y-6">
                    <!-- Message -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <MessageSquare class="h-5 w-5" />
                                Messaggio
                            </CardTitle>
                            <CardDescription v-if="contact.subject">
                                <span class="font-medium">Oggetto:</span> {{ contact.subject }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="prose prose-sm max-w-none dark:prose-invert">
                                <p class="whitespace-pre-wrap text-sm leading-relaxed break-words">{{ contact.message }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Extra Data -->
                    <Card v-if="contact.extra_data && Object.keys(contact.extra_data).length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Database class="h-5 w-5" />
                                Dati aggiuntivi
                            </CardTitle>
                            <CardDescription>
                                Informazioni extra inviate dal form di contatto
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div 
                                    v-for="(value, key) in contact.extra_data" 
                                    :key="key"
                                    class="flex flex-col gap-2 py-3 border-b last:border-b-0 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <Label class="font-medium capitalize text-sm">{{ formatFieldName(key) }}</Label>
                                    <span class="text-sm text-muted-foreground break-words sm:text-right sm:max-w-[60%]">{{ value }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="order-2 lg:order-2 space-y-4 lg:space-y-6">
                    <!-- Contact Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                Informazioni contatto
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Nome completo</Label>
                                <p class="text-sm font-medium mt-1 break-words">{{ contact.name }}</p>
                            </div>
                            
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Indirizzo email</Label>
                                <p class="text-sm mt-1">
                                    <a 
                                        :href="`mailto:${contact.email}`" 
                                        class="text-blue-600 hover:underline flex items-center gap-1 break-all"
                                    >
                                        <Mail class="h-3 w-3 flex-shrink-0" />
                                        <span class="truncate lg:break-all lg:whitespace-normal">{{ contact.email }}</span>
                                    </a>
                                </p>
                            </div>
                            
                            <div v-if="contact.phone">
                                <Label class="text-sm font-medium text-muted-foreground">Numero di telefono</Label>
                                <p class="text-sm mt-1">
                                    <a 
                                        :href="`tel:${contact.phone}`" 
                                        class="text-blue-600 hover:underline flex items-center gap-1"
                                    >
                                        <Phone class="h-3 w-3 flex-shrink-0" />
                                        {{ contact.phone }}
                                    </a>
                                </p>
                            </div>
                            
                            <div v-if="contact.company">
                                <Label class="text-sm font-medium text-muted-foreground">Azienda</Label>
                                <p class="text-sm mt-1 flex items-center gap-1">
                                    <Building class="h-3 w-3 flex-shrink-0" />
                                    <span class="break-words">{{ contact.company }}</span>
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Technical Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Settings class="h-5 w-5" />
                                Informazioni tecniche
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="contact.origin">
                                <Label class="text-sm font-medium text-muted-foreground">Sito di origine</Label>
                                <p class="text-sm mt-1">
                                    <a 
                                        v-if="isValidUrl(contact.origin)"
                                        :href="contact.origin" 
                                        target="_blank"
                                        class="text-blue-600 hover:underline flex items-start gap-1 break-all"
                                    >
                                        <ExternalLink class="h-3 w-3 flex-shrink-0 mt-0.5" />
                                        <span class="break-all">{{ contact.origin }}</span>
                                    </a>
                                    <span v-else class="flex items-start gap-1">
                                        <Globe class="h-3 w-3 flex-shrink-0 mt-0.5" />
                                        <span class="break-all">{{ contact.origin }}</span>
                                    </span>
                                </p>
                            </div>
                            
                            <div v-if="contact.ip_address">
                                <Label class="text-sm font-medium text-muted-foreground">Indirizzo IP</Label>
                                <p class="text-sm mt-1 font-mono flex items-center gap-1">
                                    <Wifi class="h-3 w-3 flex-shrink-0" />
                                    {{ contact.ip_address }}
                                </p>
                            </div>
                            
                            <div v-if="contact.user_agent">
                                <Label class="text-sm font-medium text-muted-foreground">Browser utilizzato</Label>
                                <div class="mt-1 p-2 bg-gray-50 dark:bg-gray-900 rounded-md">
                                    <p class="text-xs text-muted-foreground break-words leading-relaxed">{{ contact.user_agent }}</p>
                                </div>
                            </div>
                            
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Data e ora ricezione</Label>
                                <p class="text-sm mt-1 flex items-center gap-1">
                                    <Calendar class="h-3 w-3 flex-shrink-0" />
                                    {{ formatDate(contact.created_at) }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Zap class="h-5 w-5" />
                                Azioni rapide
                            </CardTitle>
                            <CardDescription>
                                Strumenti per gestire questo contatto
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <Button 
                                variant="outline" 
                                size="sm"
                                class="w-full justify-start"
                                @click="composeEmail"
                            >
                                <Reply class="h-4 w-4 mr-2 flex-shrink-0" />
                                <span class="truncate">Rispondi via email</span>
                            </Button>
                            
                            <Button 
                                variant="outline"
                                size="sm" 
                                class="w-full justify-start"
                                @click="copyToClipboard(contact.email)"
                            >
                                <Copy class="h-4 w-4 mr-2 flex-shrink-0" />
                                <span class="truncate">Copia indirizzo email</span>
                            </Button>
                            
                            <Button 
                                variant="outline"
                                size="sm" 
                                class="w-full justify-start"
                                @click="exportContact"
                            >
                                <Download class="h-4 w-4 mr-2 flex-shrink-0" />
                                <span class="hidden sm:inline">Esporta contatto (JSON)</span>
                                <span class="sm:hidden">Esporta contatto</span>
                            </Button>
                            
                            <div class="pt-2 border-t">
                                <Button 
                                    variant="destructive"
                                    size="sm" 
                                    class="w-full justify-start"
                                    @click="deleteContact"
                                >
                                    <Trash class="h-4 w-4 mr-2 flex-shrink-0" />
                                    <span class="truncate">Elimina contatto</span>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { 
    ArrowLeft,
    Mail, 
    MailOpen, 
    Trash,
    MessageSquare,
    Database,
    User,
    Phone,
    Building,
    Settings,
    ExternalLink,
    Globe,
    Wifi,
    Calendar,
    Zap,
    Reply,
    Copy,
    Download
} from 'lucide-vue-next'

interface Contact {
    id: number
    name: string
    email: string
    subject: string
    message: string
    phone: string
    company: string
    origin: string
    ip_address: string
    user_agent: string
    read: boolean
    created_at: string
    extra_data: Record<string, any> | null
}

interface Props {
    contact: Contact
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Contatti', href: '/contacts' },
    { title: props.contact.name, href: `/contacts/${props.contact.id}` },
]

// Remove local state - Inertia handles data updates automatically

function goBack() {
    // Use router.get with preserveState to maintain updated contact status
    router.get(route('contacts.index'), {}, {
        preserveState: false, // We want to refresh data to show updated read status
        preserveScroll: true,
        only: ['contacts', 'stats'] // Only reload necessary data
    })
}

function toggleReadStatus() {
    router.patch(route('contacts.toggle-read', props.contact.id), {}, {
        preserveState: true
    })
}

function deleteContact() {
    if (confirm('Sei sicuro di voler eliminare questo contatto? Questa azione non può essere annullata.')) {
        router.delete(route('contacts.destroy', props.contact.id))
    }
}

function composeEmail() {
    const subject = props.contact.subject ? 
        `Re: ${props.contact.subject}` : 
        'Risposta alla tua richiesta di contatto'
    
    const body = `Ciao ${props.contact.name},

Grazie per avermi contattato tramite il mio portfolio.

Ho ricevuto il tuo messaggio e ti risponderò al più presto.

Cordiali saluti,
Francesco`
    
    const mailtoUrl = `mailto:${props.contact.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
    window.open(mailtoUrl)
}

async function copyToClipboard(text: string) {
    try {
        await navigator.clipboard.writeText(text)
        // In a real app, you'd show a toast notification here
        alert('Indirizzo email copiato negli appunti!')
    } catch (err) {
        console.error('Errore nella copia:', err)
        alert('Errore nella copia dell\'email')
    }
}

function exportContact() {
    const contactData = {
        id: props.contact.id,
        nome: props.contact.name,
        email: props.contact.email,
        oggetto: props.contact.subject || 'Nessun oggetto',
        messaggio: props.contact.message,
        telefono: props.contact.phone || 'Non fornito',
        azienda: props.contact.company || 'Non fornita',
        origine: props.contact.origin || 'Sconosciuta',
        indirizzo_ip: props.contact.ip_address || 'Non disponibile',
        user_agent: props.contact.user_agent || 'Non disponibile',
        stato: props.contact.read ? 'Letto' : 'Non letto',
        data_ricezione: formatDate(props.contact.created_at),
        ...(props.contact.extra_data || {})
    }
    
    const dataStr = JSON.stringify(contactData, null, 2)
    const dataBlob = new Blob([dataStr], { type: 'application/json' })
    const url = URL.createObjectURL(dataBlob)
    
    const link = document.createElement('a')
    link.href = url
    link.download = `contatto_${props.contact.id}_${props.contact.name.replace(/\s+/g, '_')}.json`
    link.click()
    
    URL.revokeObjectURL(url)
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('it-IT', {
        day: '2-digit',
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

function formatFieldName(key: string) {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

function isValidUrl(string: string) {
    try {
        new URL(string)
        return true
    } catch (_) {
        return false
    }
}
</script> 