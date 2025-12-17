<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import Login from '@/Pages/Auth/Login.vue';

const props = defineProps({
  imgs: { type: Array, required: true },
});

const page = usePage();
 

// Search state
const searchQuery = ref('');
const searchResults = ref([]);     // array af {id, title, year, cover_path}
const searching = ref(false);
const searchError = ref(null);

// Debounce timer
let debounceTimer = null;

// watch searchQuery og lav debounced request
watch(searchQuery, (val) => {
  searchError.value = null;
  searchResults.value = [];

  // cancel tidligere timer
  if (debounceTimer) clearTimeout(debounceTimer);

  // hvis mindre end 2 tegn: gør intet (fjern resultater)
  if (!val || val.trim().length < 2) {
    searching.value = false;
    return;
  }

  // sæt ny timer
  debounceTimer = setTimeout(async () => {
    searching.value = true;
    try {
      const res = await axios.get('/api/movies/search', { params: { q: val } });
      // forvent: res.data = [{ id, title, year, cover_path }, ...]
        console.log(res.data)
      searchResults.value = res.data || [];
    } catch (err) {
      searchError.value = 'Fejl ved søgning';
      searchResults.value = [];
      // console.error(err);
    } finally {
      searching.value = false;
    }
  }, 300); // 300ms debounce
});


// skjul ødelagte covers i baggrunden
function handleImageError(e) { e.target.style.display = 'none'; }
</script>

<template>
  <Head title="Welcome" />

  <div class="relative w-screen h-screen bg-gray-900 overflow-hidden">

    <!-- BAGGRUND: covers -->
    <div class="flex flex-wrap gap-[2px] w-screen h-screen" style="padding:0; overflow:hidden;">
      <div v-for="img in props.imgs" :key="img.id" style="width:71px; height:115px; overflow:hidden;">
   <img :src="`/storage/poster/w300${img.poster_path}`" class="object-cover w-full h-full" @error="handleImageError" />
      </div>
    </div>

    <!-- CENTRAL BOX (SØG ELLER LOGIN) -->
    <div v-if="!$page.props.auth?.user" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white/90 dark:bg-black/80 rounded-xl shadow-xl z-50 p-6 w-[50rem] h-[20rem] flex flex-col items-center">
      <h1 class="text-3xl font-bold mb-6 text-gray-900 dark:text-white">Login</h1>
      <div class="w-full max-w-xl"><Login /></div>
    </div>

    <div v-else class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white/90 dark:bg-black/80 rounded-xl shadow-xl z-50 p-6 w-[50rem] h-auto flex flex-col items-center">
      <h1 class="text-2xl font-bold mb-3 text-gray-900 dark:text-white">Find your movie</h1>

      <!-- Search input -->
      <div class="w-full max-w-2xl relative">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Søg... (skriv mindst 2 bogstaver)"
          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#FF2D20]"
          autocomplete="off"
        />

        <!-- Resultat-boks -->
        <div
          v-if="(searchResults.length > 0) || searching || searchError"
          class="absolute left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-auto z-50"
        >
          <!-- loading -->
          <div v-if="searching" class="p-3 text-sm text-gray-500">Søger…</div>

          <!-- error -->
          <div v-else-if="searchError" class="p-3 text-sm text-red-600">{{ searchError }}</div>

          <!-- no results -->
          <div v-else-if="!searching && searchResults.length === 0" class="p-3 text-sm text-gray-500">
            Ingen film fundet
          </div>

          <!-- liste af resultater -->
          <ul v-else class="divide-y">
            <li v-for="movie in searchResults" :key="movie.id" class="p-3 hover:bg-gray-50">
              <Link :href="`/movies/${movie.id}`" class="flex items-center gap-3">
                <!-- lille cover thumbnail hvis cover_path er tilgængelig -->
                <img v-if="movie.cover_path" :src="movie.cover_path" alt="" class="w-12 h-16 object-cover rounded-sm" />
                <div class="flex-1">
                  <div class="font-medium text-gray-800">{{ movie.title }}</div>
                  <div class="text-sm text-gray-500">{{ movie.year }}</div>
                </div>
                <div class="text-sm text-gray-400">Se</div>
              </Link>
            </li>
          </ul>
        </div>
      </div>

    </div>

  </div>
</template>
