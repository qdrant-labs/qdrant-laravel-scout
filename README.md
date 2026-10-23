# Qdrant for Laravel Scout

A Qdrant driver for [Laravel Scout](https://laravel.com/docs/scout) with full-text, semantic, and hybrid search.

## Requirements

- PHP 8.2 or later
- Laravel 12 or 13
- Laravel Scout 11.6 or a later 11.x release
- Qdrant 1.17 or later
- For the default embedding driver: the Laravel AI SDK 1.0 or later, which requires PHP 8.3

## Installation

Install the package with Composer:

```bash
composer require qdrant-labs/qdrant-laravel-scout
```

Set the Scout driver and the Qdrant connection in your `.env` file:

```ini
SCOUT_DRIVER=qdrant
QDRANT_HOST=http://localhost:6333
QDRANT_API_KEY=
```

Publish the configuration file to `config/scout-qdrant.php`:

```bash
php artisan vendor:publish --tag=scout-qdrant-config
```

## Configuration

Add each searchable model to `model-settings` in `config/scout-qdrant.php`:

```php
'model-settings' => [
    App\Models\Post::class => [
        'searchable-attributes' => ['title', 'body', 'tags'],
        'embedding' => [
            'attribute' => 'embedding',
            'dimensions' => 1536,
        ],
        'payload-indexes' => [
            'status' => 'keyword',
            'published_at' => 'integer',
        ],
    ],
],
```

Scout creates the collection, its vectors, and its payload indexes the first time it indexes a model. You don't need to run `scout:index`.

| Setting | Use |
| --- | --- |
| `searchable-attributes` | The attributes for full-text search. Qdrant generates one [BM25](https://qdrant.tech/documentation/inference/inference-bm25/) sparse vector from their values. Array attributes are included. |
| `full-text` | Optional. The sparse vector name (`vector`, default `bm25`), the model (`model`, default `qdrant/bm25`), and model `options`. |
| `embedding` | The dense vector for semantic and hybrid search. Set the vector name (`attribute`) and the `dimensions`. Optional keys are `distance` (default `Cosine`), `driver`, `provider`, `model`, and `options`. The `options` are provider options for the Laravel AI SDK or Qdrant inference. |
| `payload-indexes` | The [payload indexes](https://qdrant.tech/documentation/concepts/indexing/#payload-index) to create, as field name and schema type. Use them for the fields that you filter or order on. |
| `collection` | Optional. Extra [collection parameters](https://qdrant.tech/documentation/concepts/collections/#create-a-collection), such as HNSW, quantization, or strict mode settings. |

Scout reads the vector settings only when it creates the collection. If you change `searchable-attributes` or `embedding` later, run `scout:flush` and then `scout:import`. Scout creates payload indexes that you add later on the next import.

The engine also indexes `__scout_key` and, when you use soft deletes, `__soft_deleted`.

### Embeddings

By default, Scout generates embeddings with the [Laravel AI SDK](https://github.com/laravel/ai). Install it with `composer require laravel/ai`. The SDK requires PHP 8.3 or later. On PHP 8.2, use the `qdrant` driver below.

Add a `toSearchableEmbedding` method to the model. It returns the text to embed, or an embedding array that you have computed already:

```php
public function toSearchableEmbedding(): string|array
{
    return $this->title."\n\n".$this->body;
}
```

To let Qdrant generate the embeddings with [inference](https://qdrant.tech/documentation/inference/), set the `qdrant` driver and a model:

```php
'embedding' => [
    'attribute' => 'embedding',
    'dimensions' => 384,
    'driver' => 'qdrant',
    'model' => 'sentence-transformers/all-minilm-l6-v2',
],
```

Dense model inference requires Qdrant Cloud Inference.

## Usage

### Full-text search

```php
$posts = Post::search('deploy laravel')
    ->where('status', 'published')
    ->paginate(15);
```

### Semantic search

```php
$posts = Post::search('how do I ship my app to production')
    ->semantic(minSimilarity: 0.7)
    ->get();
```

`minSimilarity` requires the `Cosine` or `Dot` distance.

### Hybrid search

Hybrid search combines the full-text and semantic results with weighted [Reciprocal Rank Fusion](https://qdrant.tech/documentation/concepts/hybrid-queries/):

```php
$posts = Post::search('deploy laravel')
    ->hybrid(textWeight: 1, semanticWeight: 2)
    ->get();
```

### Filters and ordering

The engine supports `where` with the `=`, `!=`, `<`, `<=`, `>`, and `>=` operators, `whereIn`, and `whereNotIn`.

Filter values use the type of the field's payload index. On a field without an index, a number also matches its string form, and the reverse.

`orderBy` works on searches without a query string, such as `Post::search('')`. The field needs an `integer`, `float`, or `datetime` payload index.

### Qdrant options

Pass [Query API](https://qdrant.tech/documentation/concepts/search/) parameters with `options`:

```php
$posts = Post::search('')
    ->options([
        'filter' => ['must' => [['key' => 'tags', 'match' => ['value' => 'php']]]],
        'params' => ['hnsw_ef' => 128],
    ])
    ->get();
```

Each model in the results has its Qdrant score in `$post->scoutMetadata()['_qdrant_score']`.

### Pagination

Full-text, semantic, and hybrid searches return at most `max_results` results across all pages (default 1,000). Call `take` to change the maximum for one search. Searches without a query string have no maximum.

## Limitations

- `searchable-attributes` takes a list of attribute names without weights.
- A search can use only one `orderBy` clause. Ordering by an array field repeats points across pages, because Qdrant returns a point once for each value.
- `scout:index` does nothing, because Scout creates collections on the first import.

## Running Qdrant locally

You can run Qdrant with

```bash
docker run -p 6333:6333 qdrant/qdrant
```

The dashboard is accessible at http://localhost:6333/dashboard.

The engine uses the Qdrant REST API through Laravel's HTTP client, so it doesn't need an extra Composer package ([Ref](https://qdrant.tech/documentation/interfaces/)).

## Testing

```bash
composer install
vendor/bin/phpunit
```

On PHP 8.3 or later, run `composer require --dev laravel/ai` first to run the tests against the Laravel AI SDK.

## License

[Apache License 2.0](LICENSE.md).
