<template>
    <Head title="Gestione Contatti" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold tracking-tight">Gestione Contatti</h1>
                    <p class="text-sm lg:text-base text-muted-foreground">
                        Gestisci i messaggi ricevuti dai form di contatto esterni
                    </p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                    <div class="flex gap-2 sm:gap-4" v-if="selectedContacts.length > 0">
                        <Button 
                            variant="outline"
                            size="sm"
                            @click="markSelectedAsRead"
                            class="flex-1 sm:flex-initial"
                        >
                            <span class="hidden sm:inline">Marca come letti ({{ selectedContacts.length }})</span>
                            <span class="sm:hidden">Letti ({{ selectedContacts.length }})</span>
                        </Button>
                        <Button 
                            variant="destructive"
                            size="sm"
                            @click="deleteSelected"
                            class="flex-1 sm:flex-initial"
                        >
                            <span class="hidden sm:inline">Elimina selezionati</span>
                            <span class="sm:hidden">Elimina</span>
                        </Button>
                    </div>
                    <Button 
                        variant="outline"
                        size="sm"
                        @click="exportContacts"
                        class="self-start sm:self-auto"
                    >
                        <Download class="mr-2 h-4 w-4" />
                        <span class="hidden sm:inline">Esporta CSV</span>
                        <span class="sm:hidden">Esporta</span>
                    </Button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Totali</CardTitle>
                        <Mail class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-xl lg:text-2xl font-bold">{{ stats.total }}</div>
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
                        <div class="text-xl lg:text-2xl font-bold text-orange-600">{{ stats.unread }}</div>
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
                        <div class="text-xl lg:text-2xl font-bold text-green-600">{{ stats.today }}</div>
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
                        <div class="text-xl lg:text-2xl font-bold text-purple-600">{{ stats.this_week }}</div>
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
                    <div class="space-y-4">
                        <!-- Search - Full width on mobile -->
                        <div>
                            <Label for="search">Ricerca</Label>
                            <Input
                                id="search"
                                v-model="filters.search"
                                placeholder="Nome, email, messaggio..."
                                @input="debouncedSearch"
                            />
                        </div>
                        
                        <!-- Filters grid - responsive layout -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
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

            <!-- Contacts Table/Cards -->
            <Card v-else>
                <CardContent class="p-0">
                    <!-- Desktop Table View -->
                    <div class="hidden lg:block overflow-x-auto">
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

                    <!-- Mobile Card View -->
                    <div class="lg:hidden p-4 space-y-4">
                        <!-- Mobile Bulk Actions -->
                        <div v-if="selectedContacts.length > 0" class="flex gap-2 p-3 bg-blue-50 dark:bg-blue-950/20 rounded-lg">
                            <Button size="sm" variant="outline" @click="markSelectedAsRead">
                                Marca come letti ({{ selectedContacts.length }})
                            </Button>
                            <Button size="sm" variant="destructive" @click="deleteSelected">
                                Elimina
                            </Button>
                        </div>

                        <!-- Mobile Select All -->
                        <div class="flex items-center justify-between p-2 border-b">
                            <div class="flex items-center gap-2">
                                <Checkbox 
                                    :checked="allSelected"
                                    @update:checked="toggleSelectAll"
                                />
                                <span class="text-sm text-muted-foreground">Seleziona tutti</span>
                            </div>
                            <div class="text-sm text-muted-foreground">
                                {{ contacts.data.length }} contatti
                            </div>
                        </div>

                        <!-- Contact Cards -->
                        <div 
                            v-for="contact in contacts.data" 
                            :key="contact.id"
                            class="border rounded-lg p-4 space-y-3"
                            :class="!contact.read ? 'bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800' : ''"
                        >
                            <!-- Card Header -->
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-3 flex-1">
                                    <Checkbox 
                                        :checked="selectedContacts.includes(contact.id)"
                                        @update:checked="toggleContact(contact.id)"
                                        class="mt-1"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <Badge :variant="contact.read ? 'secondary' : 'default'" class="text-xs">
                                                {{ contact.read ? 'Letto' : 'Non letto' }}
                                            </Badge>
                                        </div>
                                        <Link :href="route('contacts.show', contact.id)" class="font-medium text-blue-600 hover:underline">
                                            {{ contact.name }}
                                        </Link>
                                        <div class="text-sm text-muted-foreground">
                                            {{ contact.email }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-1 ml-2">
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
                            </div>

                            <!-- Card Content -->
                            <div class="space-y-2 text-sm">
                                <div v-if="contact.subject">
                                    <span class="font-medium">Oggetto:</span>
                                    <span class="text-muted-foreground ml-1">{{ contact.subject }}</span>
                                </div>
                                <div v-if="contact.company">
                                    <span class="font-medium">Azienda:</span>
                                    <span class="text-muted-foreground ml-1">{{ contact.company }}</span>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ formatDate(contact.created_at) }}
                                </div>
                            </div>

                            <!-- Message Preview -->
                            <div v-if="contact.message" class="text-sm text-muted-foreground">
                                <div class="bg-gray-50 dark:bg-gray-900 p-2 rounded text-xs">
                                    {{ contact.message.length > 120 ? contact.message.substring(0, 120) + '...' : contact.message }}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <div v-if="contacts.last_page > 1" class="border-t p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-muted-foreground text-center sm:text-left">
                                Mostrando {{ contacts.from }} - {{ contacts.to }} di {{ contacts.total }} risultati
                            </div>
                            <div class="flex gap-2 justify-center sm:justify-end">
                                <Button 
                                    variant="outline" 
                                    size="sm"
                                    :disabled="!contacts.prev_page_url"
                                    @click="goToPage(contacts.current_page - 1)"
                                    class="flex-1 sm:flex-initial"
                                >
                                    Precedente
                                </Button>
                                <Button 
                                    variant="outline" 
                                    size="sm"
                                    :disabled="!contacts.next_page_url"
                                    @click="goToPage(contacts.current_page + 1)"
                                    class="flex-1 sm:flex-initial"
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
import { ref, computed, reactive, watch } from 'vue'
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

// Watch for contacts data changes and reset selection
watch(() => props.contacts.data, () => {
    // Reset selection when contacts data changes
    selectedContacts.value = []
}, { deep: true })

// Watch for page changes and reset selection
watch(() => props.contacts.current_page, () => {
    selectedContacts.value = []
})

const allSelected = computed(() => {
    return props.contacts.data.length > 0 && 
           selectedContacts.value.length === props.contacts.data.length
})

// Computed property to check if some contacts are selected (for indeterminate state)
const someSelected = computed(() => {
    return selectedContacts.value.length > 0 && selectedContacts.value.length < props.contacts.data.length
})

// Debug computed to track selection state
const selectionInfo = computed(() => {
    return {
        total: props.contacts.data.length,
        selected: selectedContacts.value.length,
        selectedIds: selectedContacts.value,
        allSelected: allSelected.value,
        someSelected: someSelected.value
    }
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
    // Reset selection when applying filters
    selectedContacts.value = []
    
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
    // Reset selection when clearing filters
    selectedContacts.value = []
    
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
        // Create a new array with all contact IDs
        selectedContacts.value = props.contacts.data.map(contact => contact.id)
    }
}

function toggleContact(contactId: number) {
    // Create a new array to ensure reactivity
    const currentSelection = [...selectedContacts.value]
    const index = currentSelection.indexOf(contactId)
    
    if (index > -1) {
        // Remove contact from selection
        currentSelection.splice(index, 1)
    } else {
        // Add contact to selection
        currentSelection.push(contactId)
    }
    
    selectedContacts.value = currentSelection
}

function toggleReadStatus(contact: Contact) {
    router.patch(route('contacts.toggle-read', contact.id), {}, {
        preserveState: true,
        onSuccess: () => {
            // Remove contact from selection if it was selected
            const index = selectedContacts.value.indexOf(contact.id)
            if (index > -1) {
                selectedContacts.value.splice(index, 1)
            }
        }
    })
}

function deleteContact(contact: Contact) {
    if (confirm('Sei sicuro di voler eliminare questo contatto?')) {
        router.delete(route('contacts.destroy', contact.id), {
            onSuccess: () => {
                // Remove contact from selection if it was selected
                const index = selectedContacts.value.indexOf(contact.id)
                if (index > -1) {
                    selectedContacts.value.splice(index, 1)
                }
            }
        })
    }
}

function markSelectedAsRead() {
    if (selectedContacts.value.length === 0) return
    
    router.post(route('contacts.mark-multiple-read'), {
        contact_ids: selectedContacts.value
    }, {
        preserveState: true,
        onSuccess: () => {
            // Reset selection after successful operation
            selectedContacts.value = []
        }
    })
}

function deleteSelected() {
    if (selectedContacts.value.length === 0) return
    
    if (confirm(`Sei sicuro di voler eliminare ${selectedContacts.value.length} contatti?`)) {
        router.delete(route('contacts.destroy-multiple'), {
            data: { contact_ids: selectedContacts.value },
            preserveState: true,
            onSuccess: () => {
                // Reset selection after successful operation
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
    // Reset selection when changing page
    selectedContacts.value = []
    
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