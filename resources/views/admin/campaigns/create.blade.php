@extends('layouts.admin')

@section('title', 'Nouvelle campagne')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('admin.campaigns.index') }}" class="text-sm text-green hover:text-green-deep">
            <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>Retour aux campagnes
        </a>
        <h1 class="text-3xl font-bold mt-2">Nouvelle campagne</h1>
        <p class="text-gray-600">Elle est enregistrée en brouillon : rien ne part avant votre validation.</p>
    </div>

    @include('admin.campaigns._form')
</div>
@endsection
