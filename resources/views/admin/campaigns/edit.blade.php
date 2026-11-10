@extends('layouts.admin')

@section('title', 'Modifier la campagne')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('admin.campaigns.index') }}" class="text-sm text-green hover:text-green-deep">
            <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>Retour aux campagnes
        </a>
        <h1 class="text-3xl font-bold mt-2">Modifier la campagne</h1>
        <p class="text-gray-600">Seuls les brouillons sont modifiables.</p>
    </div>

    @include('admin.campaigns._form', ['campaign' => $campaign])
</div>
@endsection
