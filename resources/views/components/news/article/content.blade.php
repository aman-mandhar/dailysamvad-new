@props(['blocks'])

<div class="ds-article-content">
    @foreach ($blocks as $block)
        @if ($block->type === 'html')
            {!! $block->html !!}
        @elseif ($block->type === 'advertisement')
            <x-news.advertisement-slot :advertisement="$block->advertisement" />
        @endif
    @endforeach
</div>
