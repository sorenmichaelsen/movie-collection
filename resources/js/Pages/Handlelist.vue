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
    selectedMedia: ref({name:"Dvd"}),
    ripped: ref(false)
});

const medias = ref([
    { name: 'Dvd' },
    { name: 'Bluray' },
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

        <Dialog v-model:visible="visible" header="Edit Profile" :style="{ width: '70rem' }">
            <span class="text-surface-500 dark:text-surface-400 block mb-8">Update movie information. </span>
            <!-- TMDB search results (horizontal scroll) -->
            <div v-if="searchResults.length" class="mb-6">
                <div class="flex gap-4 overflow-x-auto py-2">
                    <div v-for="result in searchResults" :key="result.id" class="w-48 flex-shrink-0">
                        <div class="rounded shadow-sm overflow-hidden">
                            <img v-if="result.poster_path" :src="`https://image.tmdb.org/t/p/w300${result.poster_path}`"
                                :alt="result.title || result.name" class="w-full h-64 object-cover" />
                            <div v-else class="w-full h-64 bg-gray-100 flex items-center justify-center">
                                <span class="text-sm text-gray-500">No poster</span>
                            </div>
                        </div>

                        <div class="mt-2">
                            <div class="font-semibold text-sm truncate">{{ result.title || result.name }}</div>
                            <div class="text-xs text-gray-500">{{ (result.release_date || '').slice(0, 4) }}</div>
                            <div class="mt-2 flex gap-2">
                                <Button size="small" label="Use" @click="selectResult(result)" />
                                <a :href="`https://www.themoviedb.org/movie/${result.id}`" target="_blank"
                                    class="text-xs self-center text-blue-600 underline">Open</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                      <div class="flex items-center gap-4 mb-2">
                <label for="email" class="font-semibold w-24">Media type</label>
        <Select v-model="movieForm.selectedMedia" :options="medias" optionLabel="name" placeholder="Select media" />
            </div>
                     <div class="flex items-center gap-4 mb-2">
                <label for="email" class="font-semibold w-24">Ripped</label>
        <ToggleSwitch v-model="movieForm.ripped" />
            </div>

            <div class="flex items-center gap-4 mb-2">
                <label for="username" class="font-semibold w-24 ">Title</label>
                <InputText id="title" v-model="movieForm.title" class="flex-auto" autocomplete="off" />
            </div>
           <div class="flex items-center gap-4 mb-2">
                <label for="username" class="font-semibold w-24 ">Alternative Title</label>
                <InputText id="title" v-model="movieForm.alternativetitle" class="flex-auto" autocomplete="off" />
                <Button label="Search" icon="pi pi-search" @click="searchMovieDb(movieForm.alternativetitle)" />
            </div>
            <div class="flex items-center gap-4 mb-2">
                <label for="email" class="font-semibold w-24">Year</label>
                <InputText id="year" v-model="movieForm.year" class="flex-auto" autocomplete="off" />
            </div>
 


            <div class="flex justify-end gap-2">
                <Button type="button" label="Cancel" severity="secondary" @click="visible = false"></Button>
                <Button type="button" label="Save" @click="saveMovie()"></Button>
            </div>
        </Dialog>
    </AuthenticatedLayout>
</template>
