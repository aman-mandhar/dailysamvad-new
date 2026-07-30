@props(['blocks'])

<div class="ds-article-content">
    @foreach ($blocks as $block)
        @if ($block->type === 'html')
            {!! $block->html !!}
        @elseif ($block->type === 'advertisement')
            <x-news.advertisement-slot :advertisement="$block->advertisement" />
        @elseif ($block->type === 'advertisement_bottom_stack' && $block->advertisements->isNotEmpty())
            <div class="article-ad-bottom-stack">
                @foreach($block->advertisements as $advertisement)<x-advertisement.slot :advertisement="$advertisement" />@endforeach
            </div>
        @endif
    @endforeach
</div>
