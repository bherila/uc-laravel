@extends('layouts.app')

@section('title', 'Admin - Shop: ' . ($storeName ?? $storeId))

@section('content')
<div class="p-8">
     <h1 class="text-xl font-semibold">Store editing moved</h1>
     <p class="mt-4">The standalone Edit Store page has been replaced by an inline modal on the <a href="/admin/stores" class="underline">Stores</a> page.</p>
     <p class="mt-4">You will be redirected back in a moment.</p>
</div>
<script>setTimeout(() => { window.location.href = '/admin/stores'; }, 1000);</script>
@endsection
