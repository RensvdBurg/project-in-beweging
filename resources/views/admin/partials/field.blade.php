{{--
    Invoerveld met label en foutmelding.
    Verwacht $name, $label; optioneel $type (text), $value, $hint, $extraAttributes (string met extra attributen),
    $textarea (bool), $rows en $list (id van een datalist).
--}}
@php
    $fieldId = 'field-'.$name;
    $fieldError = $errors->first($name);
    $fieldValue = old($name, $value ?? '');
    $fieldClass = 'account-input w-full rounded-xl border bg-white px-3 py-2.5 text-[13px] font-semibold outline-none transition focus:border-orange focus-visible:ring-2 focus-visible:ring-orange/30 '
        .($fieldError ? 'border-red-600' : 'border-[#e5e8ec]');
@endphp
<label class="block p-4" for="{{ $fieldId }}">
    <span class="settings-label mb-1.5 block text-[11px] font-semibold text-[#5f6672]">{{ $label }}</span>
    @if ($textarea ?? false)
        <textarea id="{{ $fieldId }}" name="{{ $name }}" rows="{{ $rows ?? 5 }}" {!! $extraAttributes ?? '' !!} @if ($fieldError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif class="{{ $fieldClass }} resize-y">{{ $fieldValue }}</textarea>
    @else
        <input id="{{ $fieldId }}" type="{{ $type ?? 'text' }}" name="{{ $name }}" value="{{ $fieldValue }}" @isset($list) list="{{ $list }}" @endisset {!! $extraAttributes ?? '' !!} @if ($fieldError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif class="{{ $fieldClass }}">
    @endif
    @if ($fieldError)
        <span id="{{ $fieldId }}-error" class="mt-1.5 block text-[11px] font-semibold text-red-600">{{ $fieldError }}</span>
    @elseif (isset($hint))
        <span class="settings-description mt-1.5 block text-[11px] text-[#656c77]">{{ $hint }}</span>
    @endif
</label>
