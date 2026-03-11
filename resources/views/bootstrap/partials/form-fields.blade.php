{{--
    Partial: form-fields
    Wymagane zmienne:
      $fields   – array<{key, label, type}>
      $dataKey  – prefiks wire:model, np. "creatingData", "editingData", "massEditData"
      $placeholder – opcjonalny string dla pól tekstowych (np. przy mass edit)
--}}
@foreach ($fields as $field)
    <div class="mb-3">
        <label
            for="{{ $dataKey }}_{{ $field['key'] }}"
            class="form-label fw-medium small mb-1"
        >
            {{ $field['label'] }}
        </label>

        @if ($field['type'] === 'textarea')
            <textarea
                id="{{ $dataKey }}_{{ $field['key'] }}"
                wire:model="{{ $dataKey }}.{{ $field['key'] }}"
                class="form-control @error($dataKey . '.' . $field['key']) is-invalid @enderror"
                rows="3"
            ></textarea>
        @elseif ($field['type'] === 'checkbox')
            <div class="form-check">
                <input
                    type="checkbox"
                    id="{{ $dataKey }}_{{ $field['key'] }}"
                    wire:model="{{ $dataKey }}.{{ $field['key'] }}"
                    class="form-check-input @error($dataKey . '.' . $field['key']) is-invalid @enderror"
                >
            </div>
        @else
            <input
                type="{{ $field['type'] }}"
                id="{{ $dataKey }}_{{ $field['key'] }}"
                wire:model="{{ $dataKey }}.{{ $field['key'] }}"
                class="form-control @error($dataKey . '.' . $field['key']) is-invalid @enderror"
                @if (!empty($placeholder)) placeholder="{{ $placeholder }}" @endif
            >
        @endif

        @error($dataKey . '.' . $field['key'])
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
@endforeach