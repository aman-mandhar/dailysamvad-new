@extends('layouts.frontend')
@section('title', 'Page Not Found')
@section('robots', 'noindex, follow')
@section('content')
    <x-errors.page code="404" title="Page not found" message="The page may have moved, or the address may be incorrect.">
        <x-frontend.search-form class="mx-auto mt-8" />
        <x-error-suggestions />
    </x-errors.page>
@endsection
