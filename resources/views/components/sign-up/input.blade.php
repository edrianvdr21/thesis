<div>
    <label for="{{ $name }}">{{ $label }}</label>
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" aria-label="{{ $label }}"
        @if ($required) aria-required="true" required @endif
        class="rounded w-full py-2 px-3"
        wire:model="{{ $name }}">
    @if ($required)
        <div class="text-red-600 text-base" id="{{ $name }}-error" role="alert" aria-live="polite">
            @error($name) {{ $message }} @enderror
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const input = document.getElementById('{{ $name }}');
                const error = document.getElementById('{{ $name }}-error');

                input.addEventListener('blur', function () {
                    if (!input.validity.valid) {
                        error.textContent = '{{ $label }} is required';
                    } else {
                        error.textContent = '';
                    }
                });
            });
        </script>
    @endif
</div>

