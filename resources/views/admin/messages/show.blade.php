@extends('layouts.admin')

@section('title', 'Message de '.$contactMessage->name)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <a href="{{ route('admin.messages.index') }}" class="text-sm text-green hover:text-green-deep">
                <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>Retour aux messages
            </a>
            <h1 class="text-3xl font-bold mt-2">{{ $contactMessage->subject ?: '(sans sujet)' }}</h1>
            <p class="text-gray-600">Reçu le {{ $contactMessage->created_at->translatedFormat('j F Y à H:i') }}</p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('admin.messages.read', $contactMessage) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                    {{ $contactMessage->is_read ? 'Marquer comme non lu' : 'Marquer comme lu' }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.messages.destroy', $contactMessage) }}" onsubmit="return confirm('Supprimer ce message ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="border border-red-300 text-red-700 px-4 py-2 rounded-lg hover:bg-red-50 transition">Supprimer</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            <div>
                <dt class="text-sm text-gray-500">Expéditeur</dt>
                <dd class="font-semibold text-gray-900">{{ $contactMessage->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">E-mail</dt>
                <dd><a href="mailto:{{ $contactMessage->email }}" class="text-green hover:text-green-deep underline">{{ $contactMessage->email }}</a></dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Adresse IP</dt>
                <dd class="text-gray-900">{{ $contactMessage->ip_address ?: '—' }}</dd>
            </div>
        </dl>

        <p class="whitespace-pre-line text-gray-900 border-t border-gray-200 pt-4">{{ $contactMessage->message }}</p>
    </div>

    @if ($contactMessage->admin_reply)
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
            <p class="text-sm font-semibold text-green-800 mb-2">
                Votre réponse {{ $contactMessage->replied_at ? 'du '.$contactMessage->replied_at->format('d/m/Y à H:i') : '' }}
            </p>
            <p class="whitespace-pre-line text-gray-900">{{ $contactMessage->admin_reply }}</p>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Répondre par e-mail</h2>
        <p class="text-sm text-gray-600 mb-4">La réponse est envoyée à {{ $contactMessage->email }} et enregistrée sur cette fiche.</p>

        <form method="POST" action="{{ route('admin.messages.reply', $contactMessage) }}" class="grid gap-4">
            @csrf

            <div>
                <label for="reply-subject" class="block text-sm font-medium text-gray-700 mb-2">Sujet</label>
                <input id="reply-subject" name="subject" type="text" maxlength="255"
                       value="{{ old('subject', 'Re: '.($contactMessage->subject ?: 'votre message')) }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
                @if ($errors->has('subject'))<p class="mt-1 text-sm text-red-600">{{ $errors->first('subject') }}</p>@endif
            </div>

            <div>
                <label for="reply-body" class="block text-sm font-medium text-gray-700 mb-2">Message <span class="text-red-600" aria-hidden="true">*</span></label>
                <textarea id="reply-body" name="reply" rows="8" required maxlength="5000"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent @if ($errors->has('reply')) border-red-500 @endif">{{ old('reply') }}</textarea>
                @if ($errors->has('reply'))<p class="mt-1 text-sm text-red-600">{{ $errors->first('reply') }}</p>@endif
            </div>

            <div>
                <button type="submit" class="bg-green text-white px-6 py-2 rounded-lg hover:bg-green-deep transition">
                    <i class="fas fa-paper-plane mr-2" aria-hidden="true"></i>Envoyer la réponse
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
