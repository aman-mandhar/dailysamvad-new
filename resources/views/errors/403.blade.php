@extends('layouts.frontend')
@section('hide_breaking_news', '1')
@section('title', 'Access Forbidden')
@section('robots', 'noindex, nofollow')
@section('content')<x-errors.page code="403" title="Access forbidden" message="You do not have permission to view this page." />@endsection
