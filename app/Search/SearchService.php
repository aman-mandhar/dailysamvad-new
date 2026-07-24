<?php
namespace App\Search;

use App\Models\Post;
use App\Services\CacheQueryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class SearchService
{
    public function query(SearchCriteria $criteria, bool $public = true): Builder
    {
        $q = Post::query()->when($public, fn (Builder $b) => $b->published())->with(['primaryCategory:id,name,slug','featuredMedia:id,disk,path,width,height,missing_at,metadata']);
        if ($criteria->query !== '') { $like = '%'.$this->escape($criteria->query).'%'; $prefix = $this->escape($criteria->query).'%'; $q->where(fn (Builder $b) => $b->whereRaw("title LIKE ? ESCAPE '\\'", [$prefix])->orWhereRaw("title LIKE ? ESCAPE '\\'", [$like])->orWhereRaw("excerpt LIKE ? ESCAPE '\\'", [$like])->orWhereRaw("content LIKE ? ESCAPE '\\'", [$like])->orWhereRaw("meta_title LIKE ? ESCAPE '\\'", [$like])->orWhereRaw("meta_description LIKE ? ESCAPE '\\'", [$like])); if ($criteria->sort === 'relevance') $q->orderByRaw("CASE WHEN title = ? THEN 0 WHEN title LIKE ? ESCAPE '\\' THEN 1 WHEN excerpt LIKE ? ESCAPE '\\' THEN 2 ELSE 3 END", [$criteria->query, $prefix, $like]); }
        if ($criteria->category) $q->whereHas('categories', fn (Builder $b) => $b->where('slug', $criteria->category));
        if ($criteria->tag) $q->whereHas('tags', fn (Builder $b) => $b->where('slug', $criteria->tag));
        if ($criteria->author) $q->where('author_id', $criteria->author);
        if ($criteria->language) $q->where('language', $criteria->language);
        if ($criteria->from) $q->whereDate('published_at', '>=', $criteria->from);
        if ($criteria->to) $q->whereDate('published_at', '<=', $criteria->to);
        return match ($criteria->sort) { 'oldest' => $q->orderBy('published_at')->orderBy('id'), default => $q->orderByDesc('published_at')->orderByDesc('id') };
    }
    public function paginate(SearchCriteria $criteria, bool $public = true): LengthAwarePaginator { return $this->query($criteria, $public)->paginate($criteria->perPage)->appends(array_filter((array)$criteria)); }
    public function highlight(?string $text, string $term): string { $safe = e(strip_tags((string)$text)); if ($term === '') return $safe; return preg_replace('/('.preg_quote(e($term), '/').')/iu', '<mark>$1</mark>', $safe) ?? $safe; }
    private function escape(string $value): string { return str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $value); }
}
