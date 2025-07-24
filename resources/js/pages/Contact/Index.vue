<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Heading title="Contatti" />
                    <p class="text-muted-foreground mt-1">
                        Gestisci i messaggi ricevuti dai form di contatto esterni
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button 
                        v-if="selectedContacts.length > 0"
                        variant="outline"
                        @click="markSelectedAsRead"
                    >
                        Marca come letti ({{ selectedContacts.length }})
                    </Button>
                    <Button 
                        v-if="selectedContacts.length > 0"
                        variant="destructive"
                        @click="deleteSelected"
                    >
                        Elimina selezionati
                    </Button>
                    <Button 
                        variant="outline"
                        @click="exportContacts"
                    >
                        Esporta CSV
                    </Button>
                </div>
            </div>
        </template>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center">
                        <Icon name="mail" class="h-5 w-5 text-blue-500" />
                        <div class="ml-4">
                            <p class="text-sm font-medium text-muted-foreground">Totali</p>
                            <p class="text-2xl font-bold">{{ stats.total }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center">
                        <Icon name="mail-open" class="h-5 w-5 text-orange-500" />
                        <div class="ml-4">
                            <p class="text-sm font-medium text-muted-foreground">Non letti</p>
                            <p class="text-2xl font-bold">{{ stats.unread }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center">
                        <Icon name="calendar" class="h-5 w-5 text-green-500" />
                        <div class="ml-4">
                            <p class="text-sm font-medium text-muted-foreground">Oggi</p>
                            <p class="text-2xl font-bold">{{ stats.today }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center">
                        <Icon name="calendar-days" class="h-5 w-5 text-purple-500" />
                        <div class="ml-4">
                            <p class="text-sm font-medium text-muted-foreground">Questa settimana</p>
                            <p class="text-2xl font-bold">{{ stats.this_week }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Filters -->
        <Card class="mb-6">
            <CardContent class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <Label for="search">Ricerca</Label>
                        <Input
                            id="search"
                            v-model="filters.search"
                            placeholder="Nome, email, messaggio..."
                            @input="debouncedSearch"
                        />
                    </div>
                    <div>
                        <Label for="status">Stato</Label>
                        <Select v-model="filters.status" @update:model-value="applyFilters">
                            <SelectTrigger>
                                <SelectValue placeholder="Tutti" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Tutti</SelectItem>
                                <SelectItem value="unread">Non letti</SelectItem>
                                <SelectItem value="read">Letti</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label for="origin">Origine</Label>
                        <Select v-model="filters.origin" @update:model-value="applyFilters">
                            <SelectTrigger>
                                <SelectValue placeholder="Tutte" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Tutte</SelectItem>
                                <SelectItem v-for="origin in origins" :key="origin.origin" :value="origin.origin">
                                    {{ origin.origin }} ({{ origin.count }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label for="date_from">Data da</Label>
                        <Input
                            id="date_from"
                            v-model="filters.date_from"
                            type="date"
                            @change="applyFilters"
                        />
                    </div>
                </div>
                <div class="flex justify-between items-center mt-4">
                    <Button variant="outline" @click="clearFilters">
                        Cancella filtri
                    </Button>
                    <div class="text-sm text-muted-foreground">
                        {{ contacts.total }} contatti trovati
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Contacts Table -->
        <Card>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-12">
                                    <Checkbox 
                                        :checked="allSelected"
                                        @update:checked="toggleSelectAll"
                                    />
                                </TableHead>
                                <TableHead>
                                    <Button variant="ghost" @click="sortBy('read')">
                                        Stato
                                        <Icon name="arrow-up-down" class="ml-1 h-4 w-4" />
                                    </Button>
                                </TableHead>
                                <TableHead>
                                    <Button variant="ghost" @click="sortBy('name')">
                                        Nome
                                        <Icon name="arrow-up-down" class="ml-1 h-4 w-4" />
                                    </Button>
                                </TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Oggetto</TableHead>
                                <TableHead>Azienda</TableHead>
                                <TableHead>
                                    <Button variant="ghost" @click="sortBy('created_at')">
                                        Data
                                        <Icon name="arrow-up-down" class="ml-1 h-4 w-4" />
                                    </Button>
                                </TableHead>
                                <TableHead class="w-24">Azioni</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="contact in contacts.data" 
                                :key="contact.id"
                                :class="!contact.read ? 'bg-blue-50' : ''"
                            >
                                <TableCell>
                                    <Checkbox 
                                        :checked="selectedContacts.includes(contact.id)"
                                        @update:checked="toggleContact(contact.id)"
                                    />
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="contact.read ? 'secondary' : 'default'">
                                        {{ contact.read ? 'Letto' : 'Non letto' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="font-medium">
                                    <TextLink :href="route('contacts.show', contact.id)">
                                        {{ contact.name }}
                                    </TextLink>
                                </TableCell>
                                <TableCell>{{ contact.email }}</TableCell>
                                <TableCell>
                                    <span class="truncate max-w-40 block">
                                        {{ contact.subject || 'Nessun oggetto' }}
                                    </span>
                                </TableCell>
                                <TableCell>{{ contact.company || '-' }}</TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        {{ formatDate(contact.created_at) }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex gap-1">
                                        <Button 
                                            size="sm" 
                                            variant="ghost"
                                            @click="toggleReadStatus(contact)"
                                        >
                                            <Icon :name="contact.read ? 'mail' : 'mail-open'" class="h-4 w-4" />
                                        </Button>
                                        <Button 
                                            size="sm" 
                                            variant="ghost"
                                            @click="deleteContact(contact)"
                                        >
                                            <Icon name="trash" class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                
                <!-- Pagination -->
                <div v-if="contacts.last_page > 1" class="border-t p-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Mostrando {{ contacts.from }} - {{ contacts.to }} di {{ contacts.total }} risultati
                        </div>
                        <div class="flex gap-2">
                            <Button 
                                variant="outline" 
                                size="sm"
                                :disabled="!contacts.prev_page_url"
                                @click="goToPage(contacts.current_page - 1)"
                            >
                                Precedente
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm"
                                :disabled="!contacts.next_page_url"
                                @click="goToPage(contacts.current_page + 1)"
                            >
                                Successiva
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import Icon from '@/components/Icon.vue'
import TextLink from '@/components/TextLink.vue'

interface Contact {
    id: number
    name: string
    email: string
    subject: string
    message: string
    phone: string
    company: string
    origin: string
    read: boolean
    created_at: string
}

interface ContactPagination {
    data: Contact[]
    current_page: number
    last_page: number
    from: number
    to: number
    total: number
    prev_page_url: string | null
    next_page_url: string | null
}

interface Props {
    contacts: ContactPagination
    stats: {
        total: number
        unread: number
        today: number
        this_week: number
    }
    origins: Array<{ origin: string, count: number }>
    filters: {
        status?: string
        search?: string
        origin?: string
        date_from?: string
        date_to?: string
        sort_by?: string
        sort_order?: string
    }
}

const props = defineProps<Props>()

const selectedContacts = ref<number[]>([])
const filters = ref({
    status: props.filters.status || '',
    search: props.filters.search || '',
    origin: props.filters.origin || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    sort_by: props.filters.sort_by || 'created_at',
    sort_order: props.filters.sort_order || 'desc'
})

const allSelected = computed(() => {
    return props.contacts.data.length > 0 && 
           selectedContacts.value.length === props.contacts.data.length
})

// Simple debounce function
function debounce(func: Function, wait: number) {
    let timeout: number
    return function executedFunction(...args: any[]) {
        const later = () => {
            clearTimeout(timeout)
            func(...args)
        }
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
    }
}

const debouncedSearch = debounce(() => {
    applyFilters()
}, 500)

function applyFilters() {
    router.get(route('contacts.index'), filters.value, {
        preserveState: true,
        preserveScroll: true
    })
}

function clearFilters() {
    filters.value = {
        status: '',
        search: '',
        origin: '',
        date_from: '',
        date_to: '',
        sort_by: 'created_at',
        sort_order: 'desc'
    }
    applyFilters()
}

function sortBy(column: string) {
    if (filters.value.sort_by === column) {
        filters.value.sort_order = filters.value.sort_order === 'asc' ? 'desc' : 'asc'
    } else {
        filters.value.sort_by = column
        filters.value.sort_order = 'asc'
    }
    applyFilters()
}

function toggleSelectAll() {
    if (allSelected.value) {
        selectedContacts.value = []
    } else {
        selectedContacts.value = props.contacts.data.map(contact => contact.id)
    }
}

function toggleContact(contactId: number) {
    const index = selectedContacts.value.indexOf(contactId)
    if (index > -1) {
        selectedContacts.value.splice(index, 1)
    } else {
        selectedContacts.value.push(contactId)
    }
}

function toggleReadStatus(contact: Contact) {
    router.patch(route('contacts.toggle-read', contact.id), {}, {
        preserveState: true,
        onSuccess: () => {
            contact.read = !contact.read
        }
    })
}

function deleteContact(contact: Contact) {
    if (confirm('Sei sicuro di voler eliminare questo contatto?')) {
        router.delete(route('contacts.destroy', contact.id), {
            preserveState: true
        })
    }
}

function markSelectedAsRead() {
    router.post(route('contacts.mark-multiple-read'), {
        contact_ids: selectedContacts.value
    }, {
        preserveState: true,
        onSuccess: () => {
            selectedContacts.value = []
        }
    })
}

function deleteSelected() {
    if (confirm(`Sei sicuro di voler eliminare ${selectedContacts.value.length} contatti?`)) {
        router.delete(route('contacts.destroy-multiple'), {
            data: { contact_ids: selectedContacts.value },
            preserveState: true,
            onSuccess: () => {
                selectedContacts.value = []
            }
        })
    }
}

function exportContacts() {
    window.open(route('contacts.export') + '?' + new URLSearchParams(filters.value).toString())
}

function goToPage(page: number) {
    router.get(route('contacts.index'), { ...filters.value, page }, {
        preserveState: true,
        preserveScroll: true
    })
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
</script> 