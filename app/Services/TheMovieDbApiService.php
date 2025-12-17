<?php
namespace App\Services;

use App\Jobs\imageDownloader;
use App\Models\Actor;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TheMovieDbApiService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeoutSeconds = 10;

    public function __construct()
    {
        $this->baseUrl = 'https://api.themoviedb.org/3';
        $this->apiKey  = config('services.tmdb.key') ?? env('TheMovieDb_token');
    }

    /**
     * Search for movies by title (optionally with year).
     *
     * @param string $title
     * @param int|null $year
     * @param string $language
     * @return array|null  // returns decoded JSON or null on error
     */
    public function searchMovieByTitle(string $title, ?int $year = null, string $language = 'en-US'): ?array
    {
        $endpoint = $this->baseUrl . '/search/movie';
        $query    = [
            'query'    => $title,
            'language' => $language,
            // TMDb supports 'year' parameter to restrict to a particular release year
        ];
        if ($year != 0) {
            $query['year'] = $year;
        }

        try {
            $response = Http::timeout($this->timeoutSeconds)->withToken($this->apiKey)
                ->get($endpoint, $query);
            if ($response->successful()) {
                return $response->json(); // array med keys som 'page', 'results', ...
            }

            Log::warning('TMDb searchMovieByTitle failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('TMDb searchMovieByTitle exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get movie details by TMDb id.
     *
     * @param int $id
     * @param string $language
     * @return array|null
     */
    public function getMovieDetails(int $id, string $language = 'en-US'): ?array
    {
        $endpoint = "{$this->baseUrl}/movie/{$id}";
        $query    = [
            'language' => $language,
        ];

        try {
            $cacheKey = "tmdb.movie.details.{$id}.{$language}";
            return Cache::remember($cacheKey, now()->addHours(6), function () use ($endpoint, $query) {
                $response = Http::timeout($this->timeoutSeconds)->withToken($this->apiKey)->get($endpoint, $query);
                if ($response->successful()) {
                    return $response->json();
                }
                Log::warning('TMDb getMovieDetails failed', ['id' => $endpoint, 'status' => $response->status()]);
                return null;
            });
        } catch (\Throwable $e) {
            Log::error('TMDb getMovieDetails exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get movie credits by id.
     *
     * @param int $id
     * @param string $language
     * @return array|null
     */
    public function getMovieCredits(int $id, string $language = 'en-US'): ?array
    {
        $endpoint = "{$this->baseUrl}/movie/{$id}/credits";
        $query    = [
            'language' => $language,
        ];

        try {
            $cacheKey = "tmdb.movie.credits.{$id}.{$language}";
            return Cache::remember($cacheKey, now()->addHours(6), function () use ($endpoint, $query) {
                $response = Http::timeout($this->timeoutSeconds)->withToken($this->apiKey)->get($endpoint, $query);
                if ($response->successful()) {
                    return $response->json();
                }
                Log::warning('TMDb getMovieCredits failed', ['id' => $id, 'status' => $response->status()]);
                return null;
            });
        } catch (\Throwable $e) {
            Log::error('TMDb getMovieCredits exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get external ids for a movie by id (imdb_id, facebook_id, etc).
     *
     * @param int $id
     * @param string $language
     * @return array|null
     */
    public function getExternalIds(int $id, string $language = 'en-US'): ?array
    {
        $endpoint = "{$this->baseUrl}/movie/{$id}/external_ids";
        $query    = [
            'language' => $language,
        ];

        try {
            $cacheKey = "tmdb.movie.external_ids.{$id}.{$language}";
            return Cache::remember($cacheKey, now()->addHours(24), function () use ($endpoint, $query) {
                $response = Http::timeout($this->timeoutSeconds)->withToken($this->apiKey)->get($endpoint, $query);
                if ($response->successful()) {
                    return $response->json();
                }
                Log::warning('TMDb getExternalIds failed', ['id' => $id, 'status' => $response->status()]);
                return null;
            });
        } catch (\Throwable $e) {
            Log::error('TMDb getExternalIds exception: ' . $e->getMessage());
            return null;
        }
    }

    public function importMovieWithCredits(
        int $tmdbId,
        $ean = null,
        $physicalMedia = null,
        $alternativeTitle = null
    ): ?\App\Models\Movie {
        $details = $this->getMovieDetails($tmdbId, 'en-US');
        $credits = $this->getMovieCredits($tmdbId, 'en-US');

        if (! $details) {
            Log::warning("TMDb: movie details not found for id {$tmdbId}");
            return null;
        }

        return \DB::transaction(function () use ($details, $credits, $tmdbId, $ean, $physicalMedia, $alternativeTitle) {

            // Opret ny film hvis ikke eksisterer, med quantity = 1
            $movie = Movie::firstOrCreate(
                ['tmdb_id' => $tmdbId],
                [
                    'title'             => $details['title'] ?? ($details['original_title'] ?? 'Unknown'),
                    'releast_at'        => $details['release_date'] ?? null,
                    'eannumber'         => $ean,
                    'mediatype'         => $physicalMedia,
                    'plot'              => $details['overview'] ?? null,
                    'duration'          => $details['runtime'] ?? null,
                    'rating'            => $details['vote_average'] ?? null,
                    'poster_path'       => $details['poster_path'] ?? null,
                    'backdrop_path'     => $details['backdrop_path'] ?? null,
                    'imdb_id'           => $details['imdb_id'] ?? null,
                    'alternative_title' => $alternativeTitle,
                    'quantity'          => 1,
                ]
            );

            // Hvis filmen allerede eksisterer → increment quantity
            if (! $movie->wasRecentlyCreated) {
                $movie->increment('quantity');

                // opdater felter hvis de findes i API’en (ikke null)
                $movie->update(array_filter([
                    'title'             => $details['title'] ?? null,
                    'releast_at'        => $details['release_date'] ?? null,
                    'plot'              => $details['overview'] ?? null,
                    'duration'          => $details['runtime'] ?? null,
                    'rating'            => $details['vote_average'] ?? null,
                    'poster_path'       => $details['poster_path'] ?? null,
                    'backdrop_path'     => $details['backdrop_path'] ?? null,
                    'imdb_id'           => $details['imdb_id'] ?? null,
                    'alternative_title' => $alternativeTitle ?? null,
                ]));
            }

            // Sync actors
            if (! empty($credits['cast'])) {
                foreach ($credits['cast'] as $castMember) {
                    if (empty($castMember['id'])) {
                        continue;
                    }

                    $actor = Actor::firstOrCreate(
                        ['tmdb_id' => $castMember['id']],
                        [
                            'name'         => $castMember['name'] ?? 'Unknown',
                            'profile_path' => $castMember['profile_path'] ?? null,
                        ]
                    );

                    $movie->actors()->syncWithoutDetaching([
                        $actor->id => [
                            'character_name' => $castMember['character'] ?? null,
                            'cast_order'     => $castMember['order'] ?? null,
                        ],
                    ]);
                }
            }
            imageDownloader::dispatch("poster", $details['poster_path'] ?? null, $movie);
            imageDownloader::dispatch("backdrop", $details['backdrop_path'] ?? null, $movie);

            // Sync genres
            if (! empty($details['genres']) && is_array($details['genres'])) {
                $genreIds = [];
                foreach ($details['genres'] as $g) {
                    if (empty($g['id']) || empty($g['name'])) {
                        continue;
                    }

                    $genre = Genre::firstOrCreate(
                        ['tmdb_id' => $g['id']],
                        ['name' => $g['name']]
                    );

                    $genreIds[] = $genre->id;
                }

                if (! empty($genreIds)) {
                    $movie->genres()->syncWithoutDetaching($genreIds);
                }
            }

            return $movie;
        });
    }

}
