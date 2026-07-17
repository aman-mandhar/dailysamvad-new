@props(['archive'])

@if ($archive->posts->isEmpty())
    <x-news.archive.empty-state :message="$archive->emptyState" />
@else
    <div class="ds-archive-results">
        @foreach ($archive->posts as $post)
            <x-news.archive.post-card :post="$post" />
        @endforeach
    </div>
@endif
