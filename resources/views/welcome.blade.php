@extends('layouts.app')

@section('title', 'Welcome - ' . config('app.name'))

@section('content')
  <div id="home"></div>
@endsection

@push('scripts')
  @vite(['resources/js/home.tsx'])
@endpush
