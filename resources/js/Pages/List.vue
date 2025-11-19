<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3'; // ⬅ add router here
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const props = defineProps({
    movies: {
        type: Object,
    },
});

const onPage = (event) => {
  // event.page is 0-based; Laravel expects 1-based
  router.get(route('list'), { page: event.page + 1 }, {
    preserveScroll: true,
    // you can drop preserveState or leave it; props will still update
    preserveState: true,
  })
}
</script>

<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        MovieList
                        <DataTable
                        :value="props.movies.data"
                        tableStyle="min-width: 50rem"
                        :paginator="true"
                        :rows="props.movies.per_page"
                        :totalRecords="props.movies.total"
                        :lazy="true"
                        :first="(props.movies.current_page - 1) * props.movies.per_page"
                        
                        @page="onPage"
                        >
                        <Column field="title" header="Title"></Column>
                        <Column field="year" header="Year"></Column>
                        <Column field="amount" header="Count"></Column>
                        <Column field="eannumber" header="eannumber"></Column>

                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
