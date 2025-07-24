<template>
    <Head title="Dettaglio Contatto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" @click="goBack">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Indietro
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Contatto da {{ contact.name }}</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <Badge :variant="contactState.read ? 'secondary' : 'default'">
                                {{ contactState.read ? 'Letto' : 'Non letto' }}
                            </Badge>
                            <span class="text-sm text-muted-foreground">
                                Ricevuto il {{ formatDate(contact.created_at) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <Button variant="outline" @click="toggleReadStatus">
                        <MailOpen v-if="!contactState.read" class="h-4 w-4 mr-2" />
                        <Mail v-else class="h-4 w-4 mr-2" />
                        {{ contactState.read ? 'Marca come non letto' : 'Marca come letto' }}
                    </Button>
                    <Button variant="destructive" @click="deleteContact">
                        <Trash class="h-4 w-4 mr-2" />
                        Elimina
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
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
                                <p class="whitespace-pre-wrap text-sm leading-relaxed">{{ contact.message }}</p>
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
                                    class="flex items-center justify-between py-3 border-b last:border-b-0"
                                >
                                    <Label class="font-medium capitalize">{{ formatFieldName(key) }}</Label>
                                    <span class="text-sm text-muted-foreground">{{ value }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
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
                                <p class="text-sm font-medium mt-1">{{ contact.name }}</p>
                            </div>
                            
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Indirizzo email</Label>
                                <p class="text-sm mt-1">
                                    <a 
                                        :href="`mailto:${contact.email}`" 
                                        class="text-blue-600 hover:underline flex items-center gap-1"
                                    >
                                        <Mail class="h-3 w-3" />
                                        {{ contact.email }}
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
                                        <Phone class="h-3 w-3" />
                                        {{ contact.phone }}
                                    </a>
                                </p>
                            </div>
                            
                            <div v-if="contact.company">
                                <Label class="text-sm font-medium text-muted-foreground">Azienda</Label>
                                <p class="text-sm mt-1 flex items-center gap-1">
                                    <Building class="h-3 w-3" />
                                    {{ contact.company }}
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
                                <p class="text-sm mt-1 break-all">
                                    <a 
                                        v-if="isValidUrl(contact.origin)"
                                        :href="contact.origin" 
                                        target="_blank"
                                        class="text-blue-600 hover:underline flex items-center gap-1"
                                    >
                                        <ExternalLink class="h-3 w-3" />
                                        {{ contact.origin }}
                                    </a>
                                    <span v-else class="flex items-center gap-1">
                                        <Globe class="h-3 w-3" />
                                        {{ contact.origin }}
                                    </span>
                                </p>
                            </div>
                            
                            <div v-if="contact.ip_address">
                                <Label class="text-sm font-medium text-muted-foreground">Indirizzo IP</Label>
                                <p class="text-sm mt-1 font-mono flex items-center gap-1">
                                    <Wifi class="h-3 w-3" />
                                    {{ contact.ip_address }}
                                </p>
                            </div>
                            
                            <div v-if="contact.user_agent">
                                <Label class="text-sm font-medium text-muted-foreground">Browser utilizzato</Label>
                                <p class="text-xs text-muted-foreground mt-1 break-all leading-relaxed">{{ contact.user_agent }}</p>
                            </div>
                            
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Data e ora ricezione</Label>
                                <p class="text-sm mt-1 flex items-center gap-1">
                                    <Calendar class="h-3 w-3" />
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
                                class="w-full justify-start"
                                @click="composeEmail"
                            >
                                <Reply class="h-4 w-4 mr-2" />
                                Rispondi via email
                            </Button>
                            
                            <Button 
                                variant="outline" 
                                class="w-full justify-start"
                                @click="copyToClipboard(contact.email)"
                            >
                                <Copy class="h-4 w-4 mr-2" />
                                Copia indirizzo email
                            </Button>
                            
                            <Button 
                                variant="outline" 
                                class="w-full justify-start"
                                @click="exportContact"
                            >
                                <Download class="h-4 w-4 mr-2" />
                                Esporta contatto (JSON)
                            </Button>
                            
                            <div class="pt-2 border-t">
                                <Button 
                                    variant="destructive" 
                                    class="w-full justify-start"
                                    @click="deleteContact"
                                >
                                    <Trash class="h-4 w-4 mr-2" />
                                    Elimina contatto
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
import { ref, reactive } from 'vue'
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

// Local reactive state for the contact read status
const contactState = reactive({
    read: props.contact.read
})

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
        preserveState: true,
        onSuccess: () => {
            contactState.read = !contactState.read
        },
        onError: (errors) => {
            console.error('Errore nel cambio stato:', errors)
        }
    })
}

function deleteContact() {
    if (confirm('Sei sicuro di voler eliminare questo contatto? Questa azione non può essere annullata.')) {
        router.delete(route('contacts.destroy', props.contact.id), {
            onSuccess: () => {
                router.visit(route('contacts.index'))
            },
            onError: (errors) => {
                console.error('Errore nell\'eliminazione:', errors)
                alert('Errore durante l\'eliminazione del contatto')
            }
        })
    }
}

function composeEmail() {
    const subject = props.contact.subject ? 
        `Re: ${props.contact.subject}` : 
        'Risposta alla tua richiesta di contatto'
    
    const body = `Ciao ${props.contact.name},

Grazie per averci contattato tramite il nostro sito web.

Abbiamo ricevuto il tuo messaggio e ti risponderemo al più presto.

Cordiali saluti,
Il nostro team`
    
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
        stato: contactState.read ? 'Letto' : 'Non letto',
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