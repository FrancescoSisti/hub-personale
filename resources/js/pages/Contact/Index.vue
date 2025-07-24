<template>
    <Head title="Gestione Contatti" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Gestione Contatti</h1>
                    <p class="text-muted-foreground">
                        Gestisci i messaggi ricevuti dai form di contatto esterni
                    </p>
                </div>
                <div class="flex items-center gap-4">
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
                        <Download class="mr-2 h-4 w-4" />
                        Esporta CSV
                    </Button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Totali</CardTitle>
                        <Mail class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                        <p class="text-xs text-muted-foreground">
                            Messaggi ricevuti
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Non letti</CardTitle>
                        <MailOpen class="h-4 w-4 text-orange-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600">{{ stats.unread }}</div>
                        <p class="text-xs text-muted-foreground">
                            Da leggere
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Oggi</CardTitle>
                        <Calendar class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.today }}</div>
                        <p class="text-xs text-muted-foreground">
                            Ricevuti oggi
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Questa settimana</CardTitle>
                        <CalendarDays class="h-4 w-4 text-purple-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-purple-600">{{ stats.this_week }}</div>
                        <p class="text-xs text-muted-foreground">
                            Ultimi 7 giorni
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtri</CardTitle>
                    <CardDescription>
                        Filtra e cerca tra i contatti ricevuti
                    </CardDescription>
                </CardHeader>
                <CardContent>
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
                                    <SelectItem value="all">Tutti</SelectItem>
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
                                    <SelectItem value="all">Tutte</SelectItem>
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
                            <X class="mr-2 h-4 w-4" />
                            Cancella filtri
                        </Button>
                        <div class="text-sm text-muted-foreground">
                            {{ contacts.total }} contatti trovati
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Empty State -->
            <Card v-if="contacts.data.length === 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Mail class="h-5 w-5" />
                        Nessun contatto trovato
                    </CardTitle>
                    <CardDescription>
                        Non ci sono contatti che corrispondono ai filtri selezionati
                    </CardDescription>
                </CardHeader>
                <CardContent class="text-center py-6">
                    <p class="text-muted-foreground mb-4">
                        I contatti ricevuti tramite i form esterni appariranno qui
                    </p>
                    <Button variant="outline" @click="clearFilters">
                        Mostra tutti i contatti
                    </Button>
                </CardContent>
            </Card>

            <!-- Contacts Table -->
            <Card v-else>
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
                                            <ArrowUpDown class="ml-1 h-4 w-4" />
                                        </Button>
                                    </TableHead>
                                    <TableHead>
                                        <Button variant="ghost" @click="sortBy('name')">
                                            Nome
                                            <ArrowUpDown class="ml-1 h-4 w-4" />
                                        </Button>
                                    </TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Oggetto</TableHead>
                                    <TableHead>Azienda</TableHead>
                                    <TableHead>
                                        <Button variant="ghost" @click="sortBy('created_at')">
                                            Data
                                            <ArrowUpDown class="ml-1 h-4 w-4" />
                                        </Button>
                                    </TableHead>
                                    <TableHead class="w-24">Azioni</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="contact in contacts.data" 
                                    :key="contact.id"
                                    :class="!contact.read ? 'bg-blue-50 dark:bg-blue-950/20' : ''"
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
                                        <Link :href="route('contacts.show', contact.id)" class="text-blue-600 hover:underline">
                                            {{ contact.name }}
                                        </Link>
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
                                                <MailOpen v-if="!contact.read" class="h-4 w-4" />
                                                <Mail v-else class="h-4 w-4" />
                                            </Button>
                                            <Button 
                                                size="sm" 
                                                variant="ghost"
                                                @click="deleteContact(contact)"
                                            >
                                                <Trash class="h-4 w-4" />
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
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import { 
    Mail, 
    MailOpen, 
    Calendar, 
    CalendarDays, 
    Download, 
    ArrowUpDown, 
    Trash,
    X
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Contatti', href: '/contacts' },
]

const selectedContacts = ref<number[]>([])

const filters = reactive({
    status: props.filters.status === '' ? 'all' : props.filters.status || 'all',
    search: props.filters.search || '',
    origin: props.filters.origin === '' ? 'all' : props.filters.origin || 'all',
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
    const filterParams = {
        ...filters,
        status: filters.status === 'all' ? '' : filters.status,
        origin: filters.origin === 'all' ? '' : filters.origin
    }

    router.get(route('contacts.index'), filterParams, {
        preserveState: true,
        preserveScroll: true
    })
}

function clearFilters() {
    Object.assign(filters, {
        status: 'all',
        search: '',
        origin: 'all',
        date_from: '',
        date_to: '',
        sort_by: 'created_at',
        sort_order: 'desc'
    })
    applyFilters()
}

function sortBy(column: string) {
    if (filters.sort_by === column) {
        filters.sort_order = filters.sort_order === 'asc' ? 'desc' : 'asc'
    } else {
        filters.sort_by = column
        filters.sort_order = 'asc'
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
    const filterParams = {
        ...filters,
        status: filters.status === 'all' ? '' : filters.status,
        origin: filters.origin === 'all' ? '' : filters.origin
    }
    window.open(route('contacts.export') + '?' + new URLSearchParams(filterParams).toString())
}

function goToPage(page: number) {
    const filterParams = {
        ...filters,
        status: filters.status === 'all' ? '' : filters.status,
        origin: filters.origin === 'all' ? '' : filters.origin,
        page: page.toString()
    }
    
    router.get(route('contacts.index'), filterParams, {
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