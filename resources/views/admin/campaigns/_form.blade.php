@php $campaign = $campaign ?? null; @endphp

<form method="POST"
      action="{{ $campaign ? route('admin.campaigns.update', $campaign) : route('admin.campaigns.store') }}"
      class="grid gap-6 max-w-3xl">
    @csrf
    @if ($campaign)
        @method('PUT')
    @endif

    <div class="bg-white rounded-lg shadow-md p-6 grid gap-5">
        <div class="grid gap-1.5">
            <label for="campaign-subject" class="text-sm font-semibold">Sujet <span class="text-red-600" aria-hidden="true">*</span></label>
            <input id="campaign-subject" name="subject" type="text" required maxlength="255"
                   value="{{ old('subject', $campaign?->subject) }}"
                   @if ($errors->has('subject')) aria-invalid="true" @endif
                   class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-green focus:border-transparent">
            @error('subject')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-1.5">
            <label for="campaign-body" class="text-sm font-semibold">Message <span class="text-red-600" aria-hidden="true">*</span></label>
            <textarea id="campaign-body" name="body" rows="14" required maxlength="20000"
                      @if ($errors->has('body')) aria-invalid="true" @endif
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 font-mono text-sm focus:ring-2 focus:ring-green focus:border-transparent">{{ old('body', $campaign?->body) }}</textarea>
            @error('body')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <p class="text-sm text-gray-500">Texte brut : les retours à la ligne sont conservés, le HTML n'est pas interprété. L'aperçu reprend l'habillage des e-mails de la boutique.</p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="bg-green text-white px-6 py-2 rounded-lg hover:bg-green-deep transition">
            <i class="fas fa-save mr-2" aria-hidden="true"></i>{{ $campaign ? 'Enregistrer' : 'Enregistrer le brouillon' }}
        </button>
        <a href="{{ route('admin.campaigns.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Annuler</a>
    </div>
</form>
