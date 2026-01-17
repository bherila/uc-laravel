@extends('layouts.app')

@section('content')
<div id="admin-users-root" data-api-base="{{ url('/api') }}"></div>
@endsection

@push('head')
@vite('resources/js/admin-users.tsx')
@endpush
