@extends('layouts.error')
@section('title', 'Service Unavailable')
@section('content')<x-errors.page code="503" title="Temporarily unavailable" message="Daily Samvad is undergoing brief maintenance. Please try again shortly." />@endsection
