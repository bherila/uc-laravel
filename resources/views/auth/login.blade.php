@extends('layouts.app')

@section('content')
<div id="login-root"></div>
@endsection

@push('scripts')
    @vite(['resources/js/login.tsx'])
@endpush
