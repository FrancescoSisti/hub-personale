<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { ArrowLeft, Save, Pin, PinOff, Trash2 } from 'lucide-vue-next'

interface Note {
  id: number
  title: string
  content?: string
  pinned: boolean
  color?: string
  created_at: string
  updated_at: string
}

const props = defineProps<{ note: Note }>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Note', href: '/notes' },
  { title: props.note.title, href: `/notes/${props.note.id}` },
]

const form = useForm({
  title: props.note.title,
  content: props.note.content || '',
  color: props.note.color || '',
})

const save = () => {
  form.patch(`/notes/${props.note.id}`, { onSuccess: () => router.reload({ only: ['note'] }) })
}

const togglePin = () => {
  router.patch(`/notes/${props.note.id}/toggle-pin`, {}, { onSuccess: () => router.reload({ only: ['note'] }) })
}

const destroyNote = () => {
  router.delete(`/notes/${props.note.id}`)
}
</script>

<template>
  <Head :title="props.note.title" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <Button variant="ghost" as-child>
            <a href="/notes"><ArrowLeft class="h-4 w-4" /></a>
          </Button>
          <h1 class="text-2xl font-semibold">{{ props.note.title }}</h1>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline" size="icon" @click="togglePin">
            <Pin v-if="!props.note.pinned" class="h-4 w-4" />
            <PinOff v-else class="h-4 w-4" />
          </Button>
          <Button variant="destructive" size="icon" @click="destroyNote"><Trash2 class="h-4 w-4" /></Button>
        </div>
      </div>

      <Card :style="props.note.color ? { borderColor: props.note.color } : undefined" class="border-2">
        <CardContent class="p-6 grid gap-4">
          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <label for="title" class="text-sm font-medium">Titolo</label>
              <Input id="title" v-model="form.title" />
            </div>
            <div class="space-y-2">
              <label for="color" class="text-sm font-medium">Colore</label>
              <Input id="color" v-model="form.color" placeholder="es. amber, blue" />
            </div>
          </div>
          <div class="space-y-2">
            <label for="content" class="text-sm font-medium">Contenuto</label>
            <Textarea id="content" v-model="form.content" :rows="10" />
          </div>
          <div>
            <Button @click="save"><Save class="mr-2 h-4 w-4" /> Salva</Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>

</template>


