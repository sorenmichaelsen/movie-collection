<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3'; // ⬅ add router here
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import { useForm } from '@inertiajs/vue3';
import { ref, reactive, nextTick, watch, onMounted } from 'vue';
import InputText from 'primevue/inputtext';
import Toast from 'primevue/toast';
import Image from 'primevue/image';
import { useToast } from "primevue/usetoast";
import Dialog from 'primevue/dialog';

const visible = ref(false);
const loading = ref(false);
const toast = useToast();
const searchResults = ref([]);


    const props = defineProps({
        movies: {
            type: Object,
            required: true
        },
        search: {
            type: String,
            default: ''
        }
    });

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
    alternative_title: '',
    imdb_id: ''
});

const showModal = (data) => {
    visible.value = true
    movieForm.id = data.id
    movieForm.title = data.title
    movieForm.year = data.year
    movieForm.alternative_title = data.alternative_title
    movieForm.plot = data.plot
    movieForm.ean = data.eannumber;
    movieForm.imgpath = data.imgpath;
    movieForm.actors = data.actors;
    movieForm.director = data.director;
    movieForm.imdb_id = data.imdb_id
    movieForm.imgpath = data.imgpath
    movieForm.media = data.media
    if (!movieForm.alternative_title) {
        movieForm.alternative_title = data.title
    }
    if(!movieForm.imdb_id) {
        searchMovieDb(data.alternative_title)
    }

}

const searchMovieDb = () => {
    if (!movieForm.alternative_title) {
        searchResults.value = [];
        return;
    }

    loading.value = true;
    axios
        .get('/api/search/themoviedb?title=' + encodeURIComponent(movieForm.alternative_title) + '&year=' + encodeURIComponent(movieForm.year))
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
// reactive search string (start from server-provided search)
const search = ref(props.search ?? '');

const searchMovie = (id) => {

    loading.value = true
    axios
        .get('/api/fetchmovie?imdbid=' + id)
        .then(response => {
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

const selectResult = (result) => {
    // result shape typically has: title, overview (plot), release_date, poster_path
    movieForm.title = result.title || result.name || movieForm.title;
    movieForm.year = (result.release_date || '').split('-')[0] || movieForm.year;
    movieForm.plot = result.overview || movieForm.plot;
    movieForm.imgpath = result.poster_path ? result.poster_path.replace(/^\//, '') : movieForm.imgpath;
    // If you want to store full URL instead: movieForm.imgpath = TMDB_IMAGE_BASE + result.poster_path
    // TMDB doesn't return director/actors in the search endpoint — you must call /movie/{id}/credits if you need them.
    // Close results or keep them visible — up to you.


    loading.value = true;
    axios
        .get('/api/search/themoviedbdetails?id=' + encodeURIComponent(result.id))
        .then(response => {
            // TMDB usually returns { results: [...] }
            movieForm.imdb_id = response.data.imdb_id

            searchMovie(response.data.imdb_id)

        })
        .catch(error => {
            console.error(error)
            // optional: show toast
            toast.add({ severity: 'error', summary: 'Search failed', detail: 'Could not search TMDB', life: 3000 });

        })
        .finally(() => {
            loading.value = false;

            setTimeout(() => {
                saveMovie();
            }, 500); // 1000 ms = 1 second

            searchResults.value = [];
        });

}
const saveMovie = () => {
    movieForm.post(route('updatemovie'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Film gemt', detail: 'Filmen er opdateret', life: 3000 });
            visible.value = false;
            movieForm.id = ""
            movieForm.title = ""
            movieForm.year = ""
            movieForm.alternative_title = ""
            movieForm.plot = "";
            movieForm.ean = "";
            movieForm.imgpath = "";
            movieForm.actors = "";
            movieForm.director = "";
            movieForm.imdb_imdb = "";

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

// small debounce helper
function debounce(fn, wait = 300) {
    let timeout = null;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), wait);
    };
}

// function to fetch results (page default 1)
function fetchMovies(page = 1) {
    router.get('/dashboard', { page, search: search.value }, {
        preserveScroll: true,
        preserveState: true, // allow server to return new props
        replace: true         // optional: replace history entry
    });
}

// debounce the search fetch so we don't spam the server while typing
const debouncedFetch = debounce(() => fetchMovies(1), 350);

// when search changes, call debouncedFetch (resets to page 1)
watch(search, (val, old) => {
    // only fetch if changed (watch already ensures that)
    debouncedFetch();
});

// when user uses the DataTable paginator
const onPage = (event) => {
    const page = event.page + 1; // PrimeVue's event.page is 0-based
    fetchMovies(page);
};

// If you want to make sure the UI input is in sync with server-provided initial search on mount
onMounted(() => {
    // nothing else needed — search is already initialized from props
//    for (const movie of props.movies.data) {
    
//     if(!movie.imdb_id) {
//         showModal(movie)
//     }
     
//    }
});

const resultRefs = reactive({}); // key: result.id -> element

// id på det resultat vi vil scrolle til / som er valgt
const highlightedId = ref(null);

// find første result der matcher år (eller null)
function findFirstYearMatch() {
    if (!movieForm.year) return null;
    const yearStr = String(movieForm.year);
    const found = searchResults.value.find(r => ((r.release_date || '').slice(0, 4)) === yearStr);

    return found ? found : null;
}

const autoSelectTimer = ref(null);


// scroll til et element med smooth behaviour
function scrollToResult(selectedMovie) {
    if (!selectedMovie) return;

    const el = resultRefs[selectedMovie.id];
    if (!el) return;

    // Scroll
    el.scrollIntoView({
        behavior: 'smooth',
        inline: 'center',
        block: 'nearest'
    });

    highlightedId.value = selectedMovie.id;

    // Stop evt. gammel timer
    if (autoSelectTimer.value) {
        clearTimeout(autoSelectTimer.value);
        autoSelectTimer.value = null;
    }

    // Start ny timer (3 sek)
    autoSelectTimer.value = setTimeout(() => {
        autoSelectTimer.value = null;
        selectResult(selectedMovie);
    }, 1000);
}

function cancelAutoSelect() {
    if (autoSelectTimer.value) {
        clearTimeout(autoSelectTimer.value);
        autoSelectTimer.value = null;
        highlightedId.value = null;
    }
}

// når resultater opdateres, scroll til første år-match (hvis nogen)
watch(searchResults, async (newVal) => {
    await nextTick();
    const id = findFirstYearMatch();
    if (id) {
        // små delay så layout kan stabilisere
        setTimeout(() => scrollToResult(id), 120);
    }
});

</script>

<template>

    <Head title="Dashboard" />
    <Toast />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">

                    <div class="p-6">
                        <div class="mb-4">
                            <input v-model="search" type="search" placeholder="Search movies..."
                                class="border px-3 py-2 rounded w-full max-w-md" />
                        </div>

                        <DataTable :value="props.movies.data" tableStyle="min-width: 50rem" :paginator="true"
                            :rows="props.movies.per_page" :totalRecords="props.movies.total" :lazy="true"
                            :first="(props.movies.current_page - 1) * props.movies.per_page" @page="onPage">
                            <Column field="title" header="Title" />
                            <Column field="year" header="Year" />
                            <Column field="amount" header="Count" />
                            <Column class="w-24 !text-end">
                                <template #body="{ data }">
                                    <Button icon="pi pi-pencil" @click="showModal(data)" severity="secondary"
                                        v-if="!data.imdb_id" rounded></Button>
                                        <span v-if="data.imdb_id" @click="showModal(data)">{{ data.imdb_id }}</span> <a v-if="data.imdb_id" :href="`https://www.imdb.com/title/${data.imdb_id}`" target="_blank">Link</a>

                                </template>
                            </Column>

                        </DataTable>

                        <!-- Optional: show simple status -->
                        <div class="mt-2 text-sm text-gray-600">
                            Showing page {{ props.movies.current_page }} of {{ props.movies.last_page }} — total {{
                                props.movies.total }}
                        </div>
                    </div>
                </div>
            </div>
        </div>










        <Dialog v-model:visible="visible" header="Edit Profile" :style="{ width: '70rem' }">
            <span class="text-surface-500 dark:text-surface-400 block mb-8">Update movie information.<span v-if="autoSelectTimer">
                <Button label="Cancel auto-select" severity="secondary" size="small" @click="cancelAutoSelect" />
            </span> </span>
            <!-- TMDB search results (horizontal scroll) -->
            
            <div v-if="searchResults.length" class="mb-6">
                <div class="flex gap-4 overflow-x-auto py-5 px-5">
                    <div v-for="result in searchResults" :key="result.id" :ref="el => resultRefs[result.id] = el"
                        @click="selectResult(result); scrollToResult(result.id)" role="button"
                        class="w-48 flex-shrink-0 rounded transition-all transform hover:scale-105 hover:shadow-lg cursor-pointer"
                        :class="{
                            // grøn ring når år præcis matcher filmens år
                            'ring-4 ring-green-500 ring-offset-0': (result.release_date || '').slice(0, 4) === String(movieForm.year),
                            // ekstra fremhævning hvis vi aktivt scroller/valgte result
                            'ring-4 ring-blue-400 ring-offset-2': highlightedId === result.id
                        }">
                        <div class="rounded overflow-hidden bg-white">
                            <img v-if="result.poster_path" :src="`https://image.tmdb.org/t/p/w300${result.poster_path}`"
                                :alt="result.title || result.name" class="w-full h-64 object-cover block" />
                            <div v-else class="w-full h-64 bg-gray-100 flex items-center justify-center">
                                <span class="text-sm text-gray-500">No poster</span>
                            </div>
                        </div>

                        <div class="mt-2 px-1">
                            <div class="font-semibold text-sm truncate">{{ result.title || result.name }}</div>
                            <div class="text-xs text-gray-500">{{ (result.release_date || '').slice(0, 4) }}</div>
                            <div class="mt-2 flex gap-2">
                                <Button size="small" label="Use"
                                    @click.stop="selectResult(result); scrollToResult(result.id)" />
                                <a :href="`https://www.themoviedb.org/movie/${result.id}`" target="_blank"
                                    class="text-xs self-center text-blue-600 underline">Open</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 mb-2">
                <label for="email" class="font-semibold w-24">Imdb id</label>
                <InputText id="imdb" v-model="movieForm.imdb_id" class="flex-auto" autocomplete="off" /> <Button
                    label="Search" icon="pi pi-search" @click="searchMovie(movieForm.imdb_id)" />
            </div>
            <div class="flex items-center gap-4 mb-2">
                <label for="username" class="font-semibold w-24 ">Title</label>
                <InputText id="title" v-model="movieForm.title" class="flex-auto" autocomplete="off" />
            </div>
            <div class="flex items-center gap-4 mb-2">
                <label for="username" class="font-semibold w-24 ">Alternative Title</label>
                <InputText id="title" v-model="movieForm.alternative_title" class="flex-auto" autocomplete="off" />
                <Button label="Search" icon="pi pi-search" @click="searchMovieDb(movieForm.alternative_title)" />
            </div>
            <div class="flex items-center gap-4 mb-2">
                <label for="email" class="font-semibold w-24">Year</label>
                <InputText id="year" v-model="movieForm.year" class="flex-auto" autocomplete="off" />
            </div>
            <div class="flex items-center gap-4 mb-2">
                <label for="email" class="font-semibold w-24">director</label>
                <InputText id="year" v-model="movieForm.director" class="flex-auto" autocomplete="off" />
            </div>
            <div class="flex items-center gap-4 mb-2">
                <label for="email" class="font-semibold w-24">Actors</label>
                <InputText id="year" v-model="movieForm.actors" class="flex-auto" autocomplete="off" />
            </div>

            <div class="flex items-center gap-4 mb-2">
                <label for="email" class="font-semibold w-24">Plot</label>
                <InputText id="imdb" v-model="movieForm.plot" class="flex-auto" autocomplete="off" />
            </div>
            <div class="flex items-center gap-4 mb-2">
                <label for="email" class="font-semibold w-24">EAN</label>
                <InputText id="imdb" v-model="movieForm.ean" class="flex-auto" autocomplete="off" />
            </div>
            <div class="flex items-center gap-4 mb-2">
                <label for="email" class="font-semibold w-24">Media</label>
                <InputText id="imdb" v-model="movieForm.media" class="flex-auto" autocomplete="off" />
            </div>
            <div class="flex items-center gap-4 mb-2">
                <label for="email" class="font-semibold w-24">poster</label>
                <InputText id="imdb" v-model="movieForm.imgpath" class="flex-auto" autocomplete="off" />
            </div>




            <div class="flex justify-end gap-2">
                <Button type="button" label="Cancel" severity="secondary" @click="visible = false"></Button>
                <Button type="button" label="Save" @click="saveMovie()"></Button>
            </div>
        </Dialog>
    </AuthenticatedLayout>
</template>
