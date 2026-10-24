@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'error' => null,
    'help' => null,
    'required' => false,
    'autocomplete' => null,
    'inputmode' => null,
    'wrapper' => null,   // classes for the outer block (grid placement)
])

{{--
    Form field: visible label above the input (never placeholder-only), 48px input, error under the field and
    linked with aria-describedby, persistent help text.
--}}
@php
    $id = $attributes->get('id', 'field-'.$name);
    $describedBy = trim(($help ? "{$id}-help " : '').($error ? "{$id}-error" : ''));
@endphp

<div class="grid gap-1.5 {{ $wrapper }}">
    <label for="{{ $id }}" class="text-sm font-semibold text-ink">
        {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span><span class="sr-only"> {{ __('(obligatoire)') }}</span>@endif
    </label>

    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}"
           @if ($required) required @endif
           @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           @if ($inputmode) inputmode="{{ $inputmode }}" @endif
           @if ($error) aria-invalid="true" @endif
           @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
           {{ $attributes->except('id')->merge(['class' => 'min-h-12 w-full rounded-sm border bg-white px-3 text-base text-ink placeholder:text-ink-muted '.($error ? 'border-danger' : 'border-field')]) }}>

    @if ($help)
        <p id="{{ $id }}-help" class="text-sm text-ink-muted">{{ $help }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="flex items-start gap-1.5 text-sm font-medium text-danger">
            <x-ui.icon name="alert" class="mt-0.5 size-4" />
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
