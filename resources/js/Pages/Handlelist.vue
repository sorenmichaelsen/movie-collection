<script setup>
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

/* PrimeVue */
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import Image from 'primevue/image'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

/* --------------------------------------------------
 Props
-------------------------------------------------- */
const props = defineProps({
    movies: Object,
    count: Number
})

/* --------------------------------------------------
 UI State
-------------------------------------------------- */
const visible = ref(false)
const loading = ref(false)
const searchResults = ref([])
const rotation = ref(0)

/* --------------------------------------------------
 Prime helpers
-------------------------------------------------- */
const toast = useToast()
const confirm = useConfirm()

/* --------------------------------------------------
 Form
-------------------------------------------------- */
const movieForm = useForm({
    id: null,
    title: '',
    alternativetitle: '',
    year: '',
    plot: '',
    ean: '',
    imgpath: '',
    scanimg: '',
    director: '',
    actors: '',
    tmdb_id: null,

    /* MEDIA */
    selectedMedia: { name: 'Dvd' },
    movie_edition: { name: 'Standard' },
    ripped: false,
    storagebox: ''
})

/* --------------------------------------------------
 Select options
-------------------------------------------------- */
const medias = [
    { name: 'Dvd' },
    { name: 'Bluray' }
]

const editions = [
    { name: 'Standard' },
    { name: 'Special' },
    { name: 'DirectorsCut' },
    { name: 'Limited' },
    { name: 'twoMoviesInOne' }
]

/* --------------------------------------------------
 Helpers
-------------------------------------------------- */
const DEFAULT_MEDIA = { name: 'Dvd' }

const resetForm = () => {
    movieForm.reset({
        storagebox: movieForm.storagebox,
        ripped: movieForm.ripped,
        selectedMedia: movieForm.selectedMedia || DEFAULT_MEDIA
    })


    searchResults.value = []
    visible.value = false
    rotation.value = 0
}

const showSuccess = (summary, detail) => {
    toast.add({ severity: 'success', summary, detail, life: 3000 })
}

/* --------------------------------------------------
 Pagination
-------------------------------------------------- */
const onPage = (event) => {
    router.get(route('handlelist'), { page: event.page + 1 }, {
        preserveScroll: true,
        preserveState: false
    })
}

/* --------------------------------------------------
 Modal
-------------------------------------------------- */
const showModal = (data) => {
    movieForm.id = data.id
    movieForm.year = data.year
    movieForm.alternativetitle = data.title
    movieForm.ean = data.eannumber
    movieForm.scanimg = data.scanimg

   

    visible.value = true
}

/* --------------------------------------------------
 TMDB Search
-------------------------------------------------- */
const searchMovieDb = async () => {
    if (!movieForm.alternativetitle) return

    loading.value = true
    try {
        const { data } = await axios.get('/api/search/themoviedb', {
            params: { title: movieForm.alternativetitle }
        })
        searchResults.value = data.results || []
    } catch {
        toast.add({ severity: 'error', summary: 'Fejl', detail: 'TMDB søgning fejlede', life: 3000 })
    } finally {
        loading.value = false
    }
}

/* --------------------------------------------------
 Duplicate check
-------------------------------------------------- */
const movieExist = async (tmdbId) => {
    loading.value = true
    try {
        const { data } = await axios.get('/api/movies/exist', {
            params: { tmdb_id: tmdbId }
        })

        if (data > 0) {
            confirm.require({
                header: 'Film findes allerede',
                message: 'Der er allerede en kopi af denne film. Vil du fjerne filmen eller beholde den?',
                icon: 'pi pi-exclamation-triangle',
                acceptLabel: 'Fjern filmen',
                rejectLabel: 'Behold',
                accept: () => {
                    deleteMovie()

                },
                reject: () => {
                    saveMovie()
                    searchResults.value = []
                    toast.add({ severity: 'info', summary: 'Beholdt', life: 2500 })
                }
            })
        } 
        else {
             saveMovie()
                    searchResults.value = []
        }
    } finally {
        loading.value = false
    }
}

/* --------------------------------------------------
 Select TMDB result
-------------------------------------------------- */
const selectResult = (result) => {
    movieForm.title = result.title || result.name
    movieForm.tmdb_id = result.id
    movieExist(result.id)
}

/* --------------------------------------------------
 CRUD
-------------------------------------------------- */
const saveMovie = () => {
    movieForm.post(route('updatehandlelistmovie'), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess('Gemt', 'Filmen er opdateret')
            resetForm()
        }
    })
}

const deleteMovie = () => {
    movieForm.post(route('deletehandlelistmovie'), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess('Slettet', 'Filmen er fjernet')
            resetForm()
        }
    })
}
</script>

<template>
    <Head title="Manual Handle" />
    <Toast />
    <ConfirmDialog />

    <AuthenticatedLayout>
        <div class="p-6">
            <DataTable
                :value="props.movies.data"
                lazy paginator
                :rows="props.movies.per_page"
                :totalRecords="props.movies.total"
                :first="(props.movies.current_page - 1) * props.movies.per_page"
                @page="onPage"
            >
                <Column field="title" header="Title" />
                <Column field="year" header="Year" />
                <Column field="amount" header="Count" />
                <Column field="eannumber" header="EAN" />
                <Column>
                    <template #body="{ data }">
                        <Button icon="pi pi-pencil" rounded @click="showModal(data)" />
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="visible" modal header="Edit movie" style="width:75rem">
            <div class="grid grid-cols-12 gap-6">

                <!-- LEFT -->
                <div class="col-span-8 space-y-6">

                    <Card>
                        <template #title>Media & Storage</template>
                        <template #content>
                            <div class="grid grid-cols-2 gap-4">
                                <Select v-model="movieForm.selectedMedia" :options="medias" optionLabel="name" />
                                <Select v-model="movieForm.movie_edition" :options="editions" optionLabel="name" />
                                <div class="flex items-center gap-2">
                                    <ToggleSwitch v-model="movieForm.ripped" />
                                    <span>Ripped</span>
                                </div>
                                <InputText v-model="movieForm.storagebox" placeholder="Storage box" />
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #title>TMDB Search</template>
                        <template #content>
                            <div class="flex gap-2">
                                <InputText v-model="movieForm.alternativetitle" class="flex-1" />
                                <Button label="Search" icon="pi pi-search" @click="searchMovieDb" />
                            </div>
                        </template>
                    </Card>

                    <Card v-if="searchResults.length">
                        <template #title>Search results</template>
                        <template #content>
                            <div class="flex gap-4 overflow-x-auto">
                                <div v-for="r in searchResults" :key="r.id" class="w-48">
                                    <img
                                        v-if="r.poster_path"
                                        :src="`https://image.tmdb.org/t/p/w300${r.poster_path}`"
                                        class="rounded"
                                    />
                                    <Button
                                        label="Use this"
                                        size="small"
                                        class="mt-2 w-full"
                                        @click="selectResult(r)"
                                    />
                                </div>
                            </div>
                        </template>
                    </Card>

                </div>

                <!-- RIGHT -->
                <div class="col-span-4">
                    <Card>
                        <template #title>Scanned cover</template>
                        <template #content>
                            <Image
                                v-if="movieForm.scanimg"
                                :src="`/storage/images/${movieForm.scanimg}`"
                                preview
                                :style="{ transform: `rotate(${rotation}deg)` }"
                                class="w-full"
                            />
                            <div v-else class="text-sm text-gray-500">No scanned image</div>

                            <div class="flex justify-center gap-2 mt-3">
                                <Button icon="pi pi-undo" text @click="rotation -= 90" />
                                <Button icon="pi pi-refresh" text @click="rotation += 90" />
                            </div>
                        </template>
                    </Card>
                </div>

            </div>

<div class="flex items-center justify-between mt-6 pt-4 border-t">
    <!-- VENSTRE: Delete -->
    <Button
        severity="danger"
        label="Delete"
        icon="pi pi-trash"
        @click="deleteMovie"
    />

    <!-- HØJRE: Cancel + Save -->
    <div class="flex gap-2">
        <Button
            severity="secondary"
            label="Cancel"
            @click="resetForm"
        />
        <Button
            severity="primary"
            label="Save"
            icon="pi pi-check"
            @click="saveMovie"
        />
    </div>
</div>
        </Dialog>
    </AuthenticatedLayout>
</template>
