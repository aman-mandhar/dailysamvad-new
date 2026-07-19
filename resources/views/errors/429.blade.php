@extends('layouts.error')
@section('title', 'Too Many Requests')
@section('content')<x-errors.page code="429" title="Too many requests" message="Please wait a moment before trying again." />@endsection
