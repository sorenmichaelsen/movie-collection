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
    storagebox: '',
    manuallyEnter:1
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
            movieForm.alternativetitle = ''

        }
    })
}

const deleteMovie = () => {
            resetForm()
            movieForm.alternativetitle = ''

}
</script>

<template>
    <Head title="Manual Enter" />
    <Toast />
    <ConfirmDialog />

    <AuthenticatedLayout>
    <div class="p-4 sm:p-6">
        <div class="max-w-6xl mx-auto space-y-6">

            <Card>
                <template #title>Media & Storage</template>
                <template #content>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                    <div class="flex flex-col sm:flex-row gap-2">
                        <InputText
                            v-model="movieForm.alternativetitle"
                            class="flex-1"
                            @keyup.enter="searchMovieDb"

                        />
                        <Button
                            label="Search"
                            icon="pi pi-search"
                            @click="searchMovieDb"
                        />
                    </div>
                </template>
            </Card>

            <Card v-if="searchResults.length">
                <template #title>Search results</template>
                <template #content>
                    <div class="flex gap-4 overflow-x-auto pb-2">
                        <div
                            v-for="r in searchResults"
                            :key="r.id"
                            class="w-40 sm:w-48 shrink-0"
                        >
                            <img
                                v-if="r.poster_path"
                                :src="`https://image.tmdb.org/t/p/w300${r.poster_path}`"
                                class="rounded w-full"
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
    </div>
</AuthenticatedLayout>

</template>
