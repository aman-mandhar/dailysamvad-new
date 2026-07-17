@props(['post', 'class' => '', 'loading' => 'lazy'])

@if ($post->featured_image_url)
    <img src="{{ $post->featured_image_url }}" alt="{{ $post->meta_title ?: $post->title }}" loading="{{ $loading }}" {{ $attributes->class($class) }}>
@else
    <div {{ $attributes->class(['flex items-center justify-center bg-slate-200 text-sm font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400', $class]) }} role="img" aria-label="No image available">
        Daily Samvad
    </div>
@endif
