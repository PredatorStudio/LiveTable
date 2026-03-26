{{--
    Partial: form-fields
    Wymagane zmienne:
      $fields      – FieldDefinition[]
      $dataKey     – prefiks wire:model, np. "creatingData", "editingData", "massEditData"
      $placeholder – opcjonalny string dla pól tekstowych
--}}
@foreach ($fields as $field)
    <div class="mb-4">
        <label
            for="{{ $dataKey }}_{{ $field->key }}"
            class="block text-xs font-medium text-gray-700 mb-1"
        >
            {{ $field->label }}
        </label>

        @if ($field->type === 'textarea')
            <textarea
                id="{{ $dataKey }}_{{ $field->key }}"
                wire:model="{{ $dataKey }}.{{ $field->key }}"
                class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500 @error($dataKey . '.' . $field->key) ring-red-500 @enderror"
                rows="3"
            ></textarea>
        @elseif ($field->type === 'checkbox')
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="{{ $dataKey }}_{{ $field->key }}"
                    wire:model="{{ $dataKey }}.{{ $field->key }}"
                    class="h-4 w-4 rounded border-gray-300 text-indigo-500 focus:ring-indigo-500 @error($dataKey . '.' . $field->key) border-red-500 @enderror"
                >
            </div>
        @elseif ($field->type === 'select')
            <select
                id="{{ $dataKey }}_{{ $field->key }}"
                wire:model="{{ $dataKey }}.{{ $field->key }}"
                class="block w-full rounded-md border-0 py-1 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-500 @error($dataKey . '.' . $field->key) ring-red-500 @enderror"
            >
                <option value="">— wybierz —</option>
                @foreach ($field->options ?? [] as $optVal => $optLabel)
                    <option value="{{ $optVal }}">{{ $optLabel }}</option>
                @endforeach
            </select>
        @else
            <input
                type="{{ $field->type }}"
                id="{{ $dataKey }}_{{ $field->key }}"
                wire:model="{{ $dataKey }}.{{ $field->key }}"
                class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500 @error($dataKey . '.' . $field->key) ring-red-500 @enderror"
                @if (!empty($placeholder)) placeholder="{{ $placeholder }}" @endif
            >
        @endif

        @error($dataKey . '.' . $field->key)
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
@endforeach