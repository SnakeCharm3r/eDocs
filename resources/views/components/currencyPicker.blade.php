@props([
    'name' => 'currency',
    'id' => 'currency',
    'label' => 'Preferred Currency',
    'required' => true,
    'selected' => old('currency'),
    'class' => '',
    'defaultOption' => 'Select Currency'
])

<div class="currency-picker-container {{ $class }}">
    @if($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }} @if($required)<span class="text-danger">*</span>@endif
        </label>
    @endif
    
    <select class="form-select currency-select" id="{{ $id }}" name="{{ $name }}" {{ $required ? 'required' : '' }}>
        <option value="">{{ $defaultOption }}</option>
        @foreach($this->getCurrencies() as $currencyCode => $currencyData)
            <option value="{{ $currencyCode }}" 
                    data-symbol="{{ $currencyData['symbol'] }}"
                    data-flag="{{ $currencyData['flag'] }}"
                    {{ $selected == $currencyCode ? 'selected' : '' }}>
                {{ $currencyData['flag'] }} {{ $currencyCode }} - {{ $currencyData['name'] }}
            </option>
        @endforeach
    </select>
    
    @if($slot->isNotEmpty())
        <div class="form-text">{{ $slot }}</div>
    @else
        <div class="form-text">Select the preferred currency for transactions</div>
    @endif
</div>

@push('styles')
<style>
.currency-select option {
    font-size: 14px;
    padding: 8px 12px;
}

.currency-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize currency pickers with search functionality
    document.querySelectorAll('.currency-select').forEach(select => {
        const container = select.closest('.currency-picker-container');
        const searchInput = document.createElement('input');
        
        searchInput.type = 'text';
        searchInput.placeholder = 'Search currencies...';
        searchInput.className = 'form-control mb-2 currency-search';
        searchInput.style.marginTop = '5px';
        
        container.insertBefore(searchInput, select);

        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const options = select.options;
            
            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                const text = option.text.toLowerCase();
                if (text.includes(searchTerm)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        });

        // Clear search when selection changes
        select.addEventListener('change', function() {
            searchInput.value = '';
            // Show all options again
            Array.from(select.options).forEach(option => {
                option.style.display = '';
            });
        });
    });
});
</script>
@endpush