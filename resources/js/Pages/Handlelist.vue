<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3'; // ⬅ add router here
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import { useForm } from '@inertiajs/vue3';
import { ref } from "vue";
import { watch } from 'vue';
import InputText from 'primevue/inputtext';
import Toast from 'primevue/toast';
import Image from 'primevue/image';
import { useToast } from "primevue/usetoast";
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Card from 'primevue/card'




import Dialog from 'primevue/dialog';
const props = defineProps({
    movies: {
        type: Object,
    },
    count: {
        type: Number
    }
});
const visible = ref(false);
const loading = ref(false);
const toast = useToast();

const movieForm = useForm({
    id: '',
    title: '',
    year: '',
    plot: '',
    ean: '',
    media: '',
    director: '',
    actors: '',
    imgpath: '',
    alternativetitle: '',
    tmdb_id: '',
    media: '',
    selectedMedia: ref({ name: "Dvd" }),
    ripped: ref(false),
    movie_edition: ref({ name: "Standard" }),
    storagebox: '',
    scanimg: '',
});

const medias = ref([
    { name: 'Dvd' },
    { name: 'Bluray' },
]);
const editions = ref([
    { name: 'Standard' },
    { name: 'Special' },
    { name: 'DirectorsCut' },
    { name: 'Limited' },
    { name: 'twoMoviesInOne' },

]);

const onPage = (event) => {
    // event.page is 0-based; Laravel expects 1-based
    router.get(route('handlelist'), { page: event.page + 1 }, {
        preserveScroll: true,
        // you can drop preserveState or leave it; props will still update
        preserveState: false,
    })
}

const showModal = (data) => {
    visible.value = true
    movieForm.id = data.id
    movieForm.title = ""
    movieForm.year = data.year
    movieForm.alternativetitle = data.title
    movieForm.ean = data.eannumber
    movieForm.scanimg = data.scanimg


}

const searchMovie = (id) => {
    console.log(id)
    loading.value = true
    axios
        .get('/api/fetchmovie?imdbid=' + id)
        .then(response => {
            console.log(response.data)
            movieForm.title = response.data.Title
            movieForm.year = response.data.Year
            movieForm.plot = response.data.Plot;
            movieForm.imgpath = response.data.Poster;
            movieForm.actors = response.data.Actors;
            movieForm.director = response.data.Director;

        })
        .catch(error => {
            console.log(error)
            this.errored = true
        })
        .finally(() => loading.value = false)

}

const saveMovie = () => {
    movieForm.post(route('updatehandlelistmovie'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Film gemt', detail: 'Filmen er opdateret og fjernet fra manuel behandling', life: 3000 });
            visible.value = false;
            movieForm.id = ""
            movieForm.title = ""
            movieForm.year = ""
            movieForm.alternativetitle = ""
            movieForm.plot = "";
            movieForm.ean = "";
            movieForm.imgpath = "";
            movieForm.actors = "";
            movieForm.director = "";
            movieForm.imdb = "";
        },
        onError: (errors) => {
            for (const key in errors) {
                if (Object.prototype.hasOwnProperty.call(errors, key)) {
                    const messages = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
                    messages.forEach(message => {
                        showToast('Valideringsfejl', message, 'error');
                    });
                }
            }
        }
    });
}
const searchResults = ref([]);


const searchMovieDb = () => {
    if (!movieForm.alternativetitle) {
        searchResults.value = [];
        return;
    }

    loading.value = true;
    axios
        .get('/api/search/themoviedb?title=' + encodeURIComponent(movieForm.alternativetitle))
        .then(response => {
            // TMDB usually returns { results: [...] }
            searchResults.value = response.data.results || [];
        })
        .catch(error => {
            console.error(error)
            // optional: show toast
            toast.add({ severity: 'error', summary: 'Search failed', detail: 'Could not search TMDB', life: 3000 });
            searchResults.value = [];
        })
        .finally(() => loading.value = false)
}

/* NEW: when user picks a TMDB result, populate the form */
const TMDB_IMAGE_BASE = 'https://image.tmdb.org/t/p/w500';

const selectResult = (result) => {
    // result shape typically has: title, overview (plot), release_date, poster_path
    movieForm.title = result.title || result.name || movieForm.title;

    movieForm.tmdb_id = result.id
    // If you want to store full URL instead: movieForm.imgpath = TMDB_IMAGE_BASE + result.poster_path
    // TMDB doesn't return director/actors in the search endpoint — you must call /movie/{id}/credits if you need them.
    // Close results or keep them visible — up to you.


    loading.value = true;

    setTimeout(() => {
        saveMovie();
    }, 500); // 1000 ms = 1 second

    searchResults.value = [];

}


const openSearchWindow = (ean) => {
    console.log(ean)
    if (!ean) return;

    const url = `https://www.google.com/search?q=${encodeURIComponent(ean)}`;

    window.open(
        url,
        'eanSearchWindow',
        'width=900,height=900,top=100,left=100,menubar=no,toolbar=no,location=no,status=no'
    );
};

</script>

<template>

    <Head title="ManualHandle" />
    <Toast />
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        Handle List: {{ props.count }}
                        <DataTable :value="props.movies.data" tableStyle="min-width: 50rem" :paginator="true"
                            :rows="props.movies.per_page" :totalRecords="props.movies.total" :lazy="true"
                            :first="(props.movies.current_page - 1) * props.movies.per_page" @page="onPage">
                            <Column field="title" header="Title"></Column>
                            <Column field="year" header="Year"></Column>
                            <Column field="amount" header="Count"></Column>
                            <Column field="eannumber" header="eannumber"></Column>
                            <Column class="w-24 !text-end">
                                <template #body="{ data }">
                                    <Button icon="pi pi-pencil" @click="showModal(data)" severity="secondary"
                                        rounded></Button>
                                </template>
                            </Column>

                        </DataTable>

                    </div>
                </div>
            </div>
        </div>

<Dialog
    v-model:visible="visible"
    modal
    header="Edit Movie"
    :style="{ width: '75rem' }"
    :breakpoints="{ '1200px': '90vw', '768px': '100vw' }"
>
    <p class="text-surface-500 dark:text-surface-400 mb-6">
        Update movie information and link it to IMDB/TMDB.
    </p>

    <div class="grid grid-cols-12 gap-6">
        <!-- LEFT: FORM -->
        <div class="col-span-8 space-y-6">

            <!-- MEDIA / STORAGE -->
            <Card>
                <template #title>Media & Storage</template>
                <template #content>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Media type</label>
                            <Select
                                v-model="movieForm.selectedMedia"
                                :options="medias"
                                optionLabel="name"
                                placeholder="Select media"
                                class="w-full"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Edition</label>
                            <Select
                                v-model="movieForm.movie_edition"
                                :options="editions"
                                optionLabel="name"
                                placeholder="Select edition"
                                class="w-full"
                            />
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <ToggleSwitch v-model="movieForm.ripped" />
                            <span class="text-sm">Ripped</span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Storage box</label>
                            <InputText v-model="movieForm.storagebox" class="w-full" />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- TITLE & SEARCH -->
            <Card>
                <template #title>Title & IMDB Search</template>
                <template #content>
                    <div class="space-y-4">
                        <div v-show="false">
                            <label class="block text-sm font-medium mb-1">Title</label>
                            <InputText v-model="movieForm.title" class="w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Title</label>
                            <div class="flex gap-2">
                                <InputText
                                    v-model="movieForm.alternativetitle"
                                    class="flex-1"
                                    placeholder="Search title on IMDB"
                                />
                                <Button
                                    icon="pi pi-search"
                                    label="Search"
                                    @click="searchMovieDb(movieForm.alternativetitle)"
                                />
                            </div>
                        </div>

                        <div class="w-32">
                            <label class="block text-sm font-medium mb-1">Year</label>
                            <InputText v-model="movieForm.year" />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- SEARCH RESULTS -->
            <Card v-if="searchResults.length">
                <template #title>Search results</template>
                <template #content>
                    <div class="flex gap-4 overflow-x-auto py-2">
                        <div
                            v-for="result in searchResults"
                            :key="result.id"
                            class="w-48 flex-shrink-0"
                        >
                            <div
                                class="rounded-lg overflow-hidden shadow hover:shadow-lg transition"
                            >
                                <img
                                    v-if="result.poster_path"
                                    :src="`https://image.tmdb.org/t/p/w300${result.poster_path}`"
                                    class="w-full h-64 object-cover"
                                />
                                <div
                                    v-else
                                    class="w-full h-64 bg-gray-100 flex items-center justify-center"
                                >
                                    <span class="text-sm text-gray-500">No poster</span>
                                </div>
                            </div>

                            <div class="mt-2 space-y-1">
                                <div class="font-semibold text-sm truncate">
                                    {{ result.title || result.name }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ (result.release_date || '').slice(0, 4) }}
                                </div>

                                <Button
                                    size="small"
                                    label="Use this"
                                    severity="success"
                                    class="w-full mt-2"
                                    @click="selectResult(result)"
                                />

                                <a
                                    :href="`https://www.themoviedb.org/movie/${result.id}`"
                                    target="_blank"
                                    class="block text-center text-xs text-blue-600 underline mt-1"
                                >
                                    Open on TMDB
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

        </div>

        <!-- RIGHT: COVER -->
        <div class="col-span-4">
            <Card>
                <template #title>Scanned Cover</template>
                <template #content>
                    <div class="space-y-3">
                        <div class="rounded overflow-hidden shadow">
                            <img
                                v-if="movieForm.scanimg"
                                :src="`/storage/images/${movieForm.scanimg}`"
                                :style="{ transform: `rotate(${rotation}deg)` }"
                                class="w-full h-auto object-cover transition-transform"
                            />
                            <div
                                v-else
                                class="w-full h-64 bg-gray-100 flex items-center justify-center"
                            >
                                <span class="text-sm text-gray-500">No cover scanned</span>
                            </div>
                        </div>

                        <div class="flex justify-center gap-2">
                            <Button
                                icon="pi pi-undo"
                                text
                                @click="rotation -= 90"
                                v-tooltip="'Rotate left'"
                            />
                            <Button
                                icon="pi pi-refresh"
                                text
                                @click="rotation += 90"
                                v-tooltip="'Rotate right'"
                            />
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="flex justify-end gap-2 mt-6 pt-4 border-t">
        <Button
            severity="secondary"
            label="Cancel"
            @click="visible = false"
        />
        <Button
            severity="primary"
            label="Save"
            icon="pi pi-check"
            @click="saveMovie()"
        />
    </div>
</Dialog>

    </AuthenticatedLayout>
</template>
