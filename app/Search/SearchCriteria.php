<?php
namespace App\Search;

use Illuminate\Support\Str;

final class SearchCriteria
{
    public function __construct(public readonly string $query, public readonly ?string $category = null, public readonly ?string $tag = null, public readonly ?int $author = null, public readonly ?string $language = null, public readonly ?string $from = null, public readonly ?string $to = null, public readonly string $sort = 'relevance', public readonly int $perPage = 12) {}
    public static function from(array $input): self { $q = Str::limit(Str::squish(strip_tags((string)($input['q'] ?? ''))), (int)config('archive.search_max_length', 200), ''); $requestedSort = (string)($input['sort'] ?? 'relevance'); $sort = in_array($requestedSort, ['relevance','latest','oldest'], true) ? $requestedSort : 'relevance'; return new self($q, filled($input['category'] ?? null) ? (string)$input['category'] : null, filled($input['tag'] ?? null) ? (string)$input['tag'] : null, filter_var($input['author'] ?? null, FILTER_VALIDATE_INT) ?: null, filled($input['language'] ?? null) ? (string)$input['language'] : null, filled($input['from'] ?? null) ? (string)$input['from'] : null, filled($input['to'] ?? null) ? (string)$input['to'] : null, $sort, min(max((int)($input['per_page'] ?? config('archive.per_page', 12)), 1), 50)); }
}
