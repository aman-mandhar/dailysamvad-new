@extends('layouts.frontend')
@section('title', 'Page Expired')
@section('robots', 'noindex, nofollow')
@section('content')<x-errors.page code="419" title="Page expired" message="Please refresh the page and try again." />@endsection
