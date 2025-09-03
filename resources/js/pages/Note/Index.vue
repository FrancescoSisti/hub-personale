<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { Pin, PinOff, Plus, Trash2 } from 'lucide-vue-next'

interface Note {
  id: number
  title: string
  content?: string
  pinned: boolean
  color?: string
  created_at: string
  updated_at: string
}

interface Props {
  notes: {
    data: Note[]
    current_page: number
    last_page: number
    links: Array<{ url: string|null; label: string; active: boolean }>
  }
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Note', href: '/notes' },
]

const search = ref('')
const filteredNotes = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return props.notes.data
  return props.notes.data.filter(n =>
    n.title.toLowerCase().includes(q) || (n.content || '').toLowerCase().includes(q)
  )
})

const newNoteForm = useForm({
  title: '',
  content: '',
  color: '',
  pinned: false,
})

const createNote = () => {
  newNoteForm.post('/notes', {
    preserveScroll: true,
    onSuccess: () => {
      newNoteForm.reset('title', 'content', 'color')
      router.reload({ only: ['notes'] })
    },
  })
}

const togglePin = (note: Note) => {
  router.patch(`/notes/${note.id}/toggle-pin`, {}, { preserveScroll: true, onSuccess: () => router.reload({ only: ['notes'] }) })
}

const destroyNote = (note: Note) => {
  router.delete(`/notes/${note.id}`, { preserveScroll: true })
}
</script>

<template>
  <Head title="Note" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <div class="flex items-center justify-between gap-4">
        <h1 class="text-3xl font-bold tracking-tight">Le tue note</h1>
        <div class="flex gap-2 items-center">
          <Input v-model="search" placeholder="Cerca note..." class="w-64" />
        </div>
      </div>

      <Card>
        <CardContent class="p-4">
          <form @submit.prevent="createNote" class="grid gap-3 md:grid-cols-6">
            <Input v-model="newNoteForm.title" placeholder="Titolo" class="md:col-span-2" />
            <Input v-model="newNoteForm.color" placeholder="Colore (es. amber, blue)" class="md:col-span-1" />
            <Textarea v-model="newNoteForm.content" placeholder="Contenuto" rows="2" class="md:col-span-2" />
            <Button type="submit" class="md:col-span-1">
              <Plus class="mr-2 h-4 w-4" />
              Aggiungi
            </Button>
          </form>
        </CardContent>
      </Card>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <Card v-for="note in filteredNotes" :key="note.id" :class="note.color ? `border-${note.color}-500` : ''">
          <CardContent class="p-4 space-y-2">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <h3 class="font-semibold truncate">{{ note.title }}</h3>
                <p v-if="note.content" class="text-sm text-muted-foreground line-clamp-3">{{ note.content }}</p>
                <div class="mt-1">
                  <Badge v-if="note.pinned" variant="secondary">Pinned</Badge>
                </div>
              </div>
              <div class="flex gap-1 shrink-0">
                <Button size="icon" variant="ghost" @click="togglePin(note)">
                  <Pin v-if="!note.pinned" class="h-4 w-4" />
                  <PinOff v-else class="h-4 w-4" />
                </Button>
                <Link :href="`/notes/${note.id}`">
                  <Button size="sm" variant="outline">Apri</Button>
                </Link>
                <Button size="icon" variant="ghost" @click="destroyNote(note)"><Trash2 class="h-4 w-4" /></Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

    </div>
  </AppLayout>

</template>


