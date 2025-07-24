<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" @click="goBack">
                        <Icon name="arrow-left" class="h-4 w-4 mr-2" />
                        Indietro
                    </Button>
                    <div>
                        <Heading :title="`Contatto da ${contact.name}`" />
                        <div class="flex items-center gap-2 mt-1">
                            <Badge :variant="contact.read ? 'secondary' : 'default'">
                                {{ contact.read ? 'Letto' : 'Non letto' }}
                            </Badge>
                            <span class="text-sm text-muted-foreground">
                                {{ formatDate(contact.created_at) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="toggleReadStatus">
                        <Icon :name="contact.read ? 'mail' : 'mail-open'" class="h-4 w-4 mr-2" />
                        {{ contact.read ? 'Marca come non letto' : 'Marca come letto' }}
                    </Button>
                    <Button variant="destructive" @click="deleteContact">
                        <Icon name="trash" class="h-4 w-4 mr-2" />
                        Elimina
                    </Button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Message -->
                <Card>
                    <CardHeader>
                        <CardTitle>Messaggio</CardTitle>
                        <CardDescription v-if="contact.subject">
                            Oggetto: {{ contact.subject }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="prose prose-sm max-w-none">
                            <p class="whitespace-pre-wrap">{{ contact.message }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Extra Data -->
                <Card v-if="contact.extra_data && Object.keys(contact.extra_data).length > 0">
                    <CardHeader>
                        <CardTitle>Dati aggiuntivi</CardTitle>
                        <CardDescription>
                            Informazioni extra inviate dal form
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div 
                                v-for="(value, key) in contact.extra_data" 
                                :key="key"
                                class="flex justify-between py-2 border-b last:border-b-0"
                            >
                                <span class="font-medium capitalize">{{ key.replace('_', ' ') }}:</span>
                                <span>{{ value }}</span>
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
                        <CardTitle>Informazioni contatto</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label class="font-medium">Nome</Label>
                            <p class="text-sm">{{ contact.name }}</p>
                        </div>
                        
                        <div>
                            <Label class="font-medium">Email</Label>
                            <p class="text-sm">
                                <a 
                                    :href="`mailto:${contact.email}`" 
                                    class="text-blue-600 hover:underline"
                                >
                                    {{ contact.email }}
                                </a>
                            </p>
                        </div>
                        
                        <div v-if="contact.phone">
                            <Label class="font-medium">Telefono</Label>
                            <p class="text-sm">
                                <a 
                                    :href="`tel:${contact.phone}`" 
                                    class="text-blue-600 hover:underline"
                                >
                                    {{ contact.phone }}
                                </a>
                            </p>
                        </div>
                        
                        <div v-if="contact.company">
                            <Label class="font-medium">Azienda</Label>
                            <p class="text-sm">{{ contact.company }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Technical Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Informazioni tecniche</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="contact.origin">
                            <Label class="font-medium">Origine</Label>
                            <p class="text-sm break-all">
                                <a 
                                    v-if="isValidUrl(contact.origin)"
                                    :href="contact.origin" 
                                    target="_blank"
                                    class="text-blue-600 hover:underline"
                                >
                                    {{ contact.origin }}
                                </a>
                                <span v-else>{{ contact.origin }}</span>
                            </p>
                        </div>
                        
                        <div v-if="contact.ip_address">
                            <Label class="font-medium">Indirizzo IP</Label>
                            <p class="text-sm font-mono">{{ contact.ip_address }}</p>
                        </div>
                        
                        <div v-if="contact.user_agent">
                            <Label class="font-medium">User Agent</Label>
                            <p class="text-xs text-muted-foreground break-all">{{ contact.user_agent }}</p>
                        </div>
                        
                        <div>
                            <Label class="font-medium">Data ricezione</Label>
                            <p class="text-sm">{{ formatDate(contact.created_at) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Quick Actions -->
                <Card>
                    <CardHeader>
                        <CardTitle>Azioni rapide</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Button 
                            variant="outline" 
                            class="w-full justify-start"
                            @click="composeEmail"
                        >
                            <Icon name="reply" class="h-4 w-4 mr-2" />
                            Rispondi via email
                        </Button>
                        
                        <Button 
                            variant="outline" 
                            class="w-full justify-start"
                            @click="copyToClipboard(contact.email)"
                        >
                            <Icon name="copy" class="h-4 w-4 mr-2" />
                            Copia email
                        </Button>
                        
                        <Button 
                            variant="outline" 
                            class="w-full justify-start"
                            @click="exportContact"
                        >
                            <Icon name="download" class="h-4 w-4 mr-2" />
                            Esporta contatto
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import Icon from '@/components/Icon.vue'

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

function goBack() {
    window.history.back()
}

function toggleReadStatus() {
    router.patch(route('contacts.toggle-read', props.contact.id), {}, {
        preserveState: true,
        onSuccess: () => {
            props.contact.read = !props.contact.read
        }
    })
}

function deleteContact() {
    if (confirm('Sei sicuro di voler eliminare questo contatto?')) {
        router.delete(route('contacts.destroy', props.contact.id), {
            onSuccess: () => {
                router.visit(route('contacts.index'))
            }
        })
    }
}

function composeEmail() {
    const subject = props.contact.subject ? 
        `Re: ${props.contact.subject}` : 
        'Risposta alla tua richiesta di contatto'
    
    const body = `Ciao ${props.contact.name},\n\nGrazie per averci contattato.\n\nCordiali saluti`
    
    const mailtoUrl = `mailto:${props.contact.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
    window.open(mailtoUrl)
}

function copyToClipboard(text: string) {
    navigator.clipboard.writeText(text).then(() => {
        // In a real app, you'd show a toast notification here
        alert('Email copiata negli appunti!')
    })
}

function exportContact() {
    const contactData = {
        nome: props.contact.name,
        email: props.contact.email,
        oggetto: props.contact.subject,
        messaggio: props.contact.message,
        telefono: props.contact.phone,
        azienda: props.contact.company,
        origine: props.contact.origin,
        ip: props.contact.ip_address,
        data: formatDate(props.contact.created_at),
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

function isValidUrl(string: string) {
    try {
        new URL(string)
        return true
    } catch (_) {
        return false
    }
}
</script> 