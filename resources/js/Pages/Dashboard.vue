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




// reactive search string (start from server-provided search)
const search = ref(props.search ?? '');




// small debounce helper
function debounce(fn, wait = 300) {
    let timeout = null;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), wait);
    };
}
const sortField = ref(props.sort ?? 'title');
const sortOrder = ref(props.direction === 'desc' ? -1 : 1);


// function to fetch results (page default 1)
function fetchMovies(page = 1) {
    router.get('/dashboard', {
        page,
        search: search.value,
        sort: sortField.value,
        direction: sortOrder.value === 1 ? 'asc' : 'desc'
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true
    });
}
const onSort = (event) => {
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;
    fetchMovies(1); // altid tilbage til side 1 ved ny sortering
};
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

                        <DataTable :value="props.movies.data" :paginator="true" :rows="props.movies.per_page"
                            :totalRecords="props.movies.total" :lazy="true" :sortField="sortField"
                            :sortOrder="sortOrder" @sort="onSort" @page="onPage">
                            <Column field="title" header="Title" sortable />
                            <Column field="releast_at" header="Year" sortable />
                            <Column field="quantity" header="Count" sortable />
                            <Column class="w-24 !text-end">
                                <template #body="{ data }">

                                    <span v-if="!data.imdb_id" @click="showModal(data)">{{ data.imdb_id }}</span> <a
                                        v-if="data.imdb_id" v-show="false"
                                        :href="`https://www.imdb.com/title/${data.imdb_id}`" target="_blank">Link</a>
                                    <div style="width:71px; height:115px; overflow:hidden;" v-if="data.localimg">
                                        <img :src="`/storage/poster${data.poster_path}`"
                                            class="object-cover w-full h-full" />
                                    </div>
                                    <div v-else style="width:71px; height:115px; overflow:hidden;">
                                        <img :src="`/storage/moviecovers/noimg.jpg`" class="object-cover w-full h-full"
                                            @click="showModal(data)" />
                                    </div>
                                </template>
                            </Column>

                        </DataTable>

                        <!-- Optional: show simple status -->
                        <div class="mt-2 text-sm text-gray-600">
                            Showing page {{ props.movies.current_page }} of {{ props.movies.last_page }} — total movie
                            count {{
                                props.movies.total }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
