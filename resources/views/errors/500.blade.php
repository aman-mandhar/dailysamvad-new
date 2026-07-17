@extends('layouts.frontend')
@section('hide_breaking_news', '1')
@section('title', 'Server Error')
@section('robots', 'noindex, nofollow')
@section('content')<x-errors.page code="500" title="Something went wrong" message="We could not complete your request. Please try again later." />@endsection
