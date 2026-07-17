@props(['category'])

<section {{ $attributes->class('rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900') }} aria-labelledby="category-{{ $category->getKey() }}-heading">
    <h3 id="category-{{ $category->getKey() }}-heading" class="border-b border-slate-200 pb-3 text-lg font-black text-slate-950 dark:border-slate-800 dark:text-white">
        {{ $category->name }}
    </h3>
    <div class="mt-5 space-y-5 divide-y divide-slate-100 dark:divide-slate-800">
        @foreach ($category->posts as $post)
            <x-news.small-card :post="$post" :show-category="false" @class(['pt-5' => ! $loop->first]) />
        @endforeach
    </div>
</section>
