@props(['archive'])

@if ($archive->posts->isEmpty())
    <x-news.archive.empty-state :message="$archive->emptyState" :search-query="$archive->contextType === 'search' ? $archive->searchQuery : null" />
@else
    <div class="ds-archive-results">
        @foreach ($archive->posts as $post)
            <x-news.archive.post-card :post="$post" />
        @endforeach
    </div>
@endif
