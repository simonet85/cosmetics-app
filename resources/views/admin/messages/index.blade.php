@extends('layouts.admin')

@section('title', 'Messages')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Messages</h1>
            <p class="text-gray-600">Messages envoyés depuis le formulaire de contact. {{ $unread }} non {{ $unread > 1 ? 'lus' : 'lu' }}.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-6">{{ session('success') }}</div>
    @endif

    @if ($messages->isEmpty())
        <div class="bg-white rounded-lg shadow-md p-12 text-center text-gray-600">Aucun message pour le moment.</div>
    @else
        <div class="bg-white rounded-lg shadow-md divide-y divide-gray-200">
            @foreach ($messages as $message)
                <details class="group" @if (! $message->is_read) data-unread @endif>
                    <summary class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 cursor-pointer list-none hover:bg-gray-50">
                        <span class="flex items-center gap-3 min-w-0">
                            <span class="inline-block w-2 h-2 rounded-full {{ $message->is_read ? 'bg-gray-300' : 'bg-green' }}" aria-hidden="true"></span>
                            <span class="{{ $message->is_read ? 'text-gray-700' : 'font-bold text-gray-900' }} truncate">
                                {{ $message->subject ?: '(sans sujet)' }}
                                <span class="font-normal text-gray-500">— {{ $message->name }}</span>
                                @unless ($message->is_read)<span class="sr-only"> (non lu)</span>@endunless
                            </span>
                        </span>
                        <span class="text-sm text-gray-500">{{ $message->created_at->translatedFormat('j F Y à H:i') }}</span>
                    </summary>

                    <div class="px-6 pb-6">
                        <p class="text-sm text-gray-600 mb-3">De <a href="mailto:{{ $message->email }}" class="underline">{{ $message->email }}</a></p>
                        <p class="whitespace-pre-line text-gray-900 mb-4">{{ $message->message }}</p>
                        <div class="flex flex-wrap gap-3">
                            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject) }}" class="bg-green text-white px-4 py-2 rounded hover:opacity-90">Répondre</a>
                            <form method="POST" action="{{ route('admin.messages.read', $message) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="border border-gray-300 px-4 py-2 rounded hover:bg-gray-50">{{ $message->is_read ? 'Marquer comme non lu' : 'Marquer comme lu' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" onsubmit="return confirm('Supprimer ce message ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="border border-red-300 text-red-700 px-4 py-2 rounded hover:bg-red-50">Supprimer</button>
                            </form>
                        </div>
                    </div>
                </details>
            @endforeach
        </div>

        <div class="mt-6">{{ $messages->links() }}</div>
    @endif
</div>
@endsection
