@extends('layouts.app')

@section('title', 'Login - ' . config('app.name'))

@section('content')
<div id="login-root"></div>
@endsection

@push('scripts')
    @vite(['resources/js/login.tsx'])
@endpush
