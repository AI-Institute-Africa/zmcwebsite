@props(['selected' => null])
@php
    $countries = config('countries', []);
    $selectedCode = old('phone_country_code', $selected ?: '+263');
    $selectedCountry = collect($countries)->firstWhere('dial', $selectedCode) ?? collect($countries)->firstWhere('dial', '+263') ?? ($countries[0] ?? null);
    $instanceId = 'country-code-' . \Illuminate\Support\Str::uuid();
    $flagForIso = static function ($iso) {
        return collect(str_split(strtoupper($iso)))->map(fn ($letter) => mb_chr(127397 + ord($letter), 'UTF-8'))->implode('');
    };
@endphp

@push('styles')
<style>
    @keyframes cc-pop { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
    .country-select { position: relative; }
    .country-select-trigger { position: relative; display: flex; width: 100%; height: 38px; align-items: center; gap: 8px; padding: 0 10px; border: 1px solid var(--auth-border); border-radius: 10px; background: #fff; color: #22252a; cursor: pointer; text-align: left; transition: border-color var(--dur-base) var(--ease-out), box-shadow var(--dur-base) var(--ease-out); }
    .country-select-trigger:hover, .country-select-trigger[aria-expanded="true"] { border-color: var(--auth-accent-dark); }
    .country-select-trigger:focus-visible { outline: none; box-shadow: var(--auth-focus); }
    .country-select-flag { font-size: 17px; line-height: 1; }
    .country-select-code { font-size: 13px; font-weight: 700; }
    .country-select-name { overflow: hidden; color: #757b85; font-size: 11px; text-overflow: ellipsis; white-space: nowrap; }
    .country-select-chevron { margin-left: auto; color: #858b95; transition: transform var(--dur-base) var(--ease-out); }
    .country-select-trigger[aria-expanded="true"] .country-select-chevron { transform: rotate(180deg); }
    .country-select-menu { position: absolute; z-index: 20; top: calc(100% + 6px); left: 0; width: min(340px, calc(100vw - 56px)); padding: 8px; border: 1px solid var(--auth-border); border-radius: 13px; background: #fff; box-shadow: 0 14px 38px rgba(20,24,31,.17); }
    .country-select-menu[hidden] { display: none; }
    .country-select-search { width: 100%; height: 38px; padding: 0 11px; border: 1px solid var(--auth-border); border-radius: 9px; outline: none; transition: border-color var(--dur-base) var(--ease-out), background-color var(--dur-base) var(--ease-out), box-shadow var(--dur-base) var(--ease-out); }
    .country-select-search:focus { border-color: var(--auth-accent-dark); box-shadow: var(--auth-focus); }
    .country-select-options { max-height: 220px; overflow: auto; margin-top: 6px; }
    .country-select-option { display: flex; width: 100%; align-items: center; gap: 9px; padding: 9px 8px; border: 0; border-radius: 8px; background: #fff; color: #25282e; cursor: pointer; text-align: left; transition: background-color var(--dur-fast) var(--ease-out); }
    .country-select-option:hover, .country-select-option:focus-visible, .country-select-option[aria-selected="true"] { background: #fff8dc; }
    .country-select-option-name { flex: 1; font-size: 12px; }
    .country-select-empty { padding: 12px 8px; color: var(--auth-muted); font-size: 12px; }
    @media (prefers-reduced-motion: no-preference) {
        .country-select-menu:not([hidden]) { animation: cc-pop var(--dur-fast) var(--ease-out) both; }
    }
</style>
@endpush

<div class="country-select" id="{{ $instanceId }}" data-country-select>
    <input type="hidden" name="phone_country_code" value="{{ $selectedCountry['dial'] ?? '+263' }}" required data-country-value>
    <button type="button" class="country-select-trigger" aria-label="Country calling code" aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $instanceId }}-menu" data-country-trigger>
        <span class="country-select-flag" aria-hidden="true" data-country-flag>{{ $selectedCountry ? $flagForIso($selectedCountry['iso']) : '🌍' }}</span>
        <span class="country-select-code" data-country-code>{{ $selectedCountry['dial'] ?? '+263' }}</span>
        <span class="country-select-name" data-country-name>{{ $selectedCountry['name'] ?? 'Zimbabwe' }}</span>
        <i class="ri-arrow-down-s-line country-select-chevron" aria-hidden="true"></i>
    </button>
    <div class="country-select-menu" id="{{ $instanceId }}-menu" hidden>
        <input type="search" class="country-select-search" aria-label="Search countries" placeholder="Search name, code or dialling prefix" autocomplete="off" data-country-search>
        <div class="country-select-options" role="listbox" aria-label="Country calling codes" tabindex="-1" data-country-options>
            @foreach($countries as $country)
                <button type="button" class="country-select-option" role="option" aria-selected="{{ ($selectedCountry['iso'] ?? '') === $country['iso'] ? 'true' : 'false' }}" data-dial="{{ $country['dial'] }}" data-iso="{{ $country['iso'] }}" data-name="{{ $country['name'] }}" data-search="{{ strtolower($country['name'].' '.$country['iso'].' '.$country['dial']) }}">
                    <span class="country-select-flag" aria-hidden="true">{{ $flagForIso($country['iso']) }}</span>
                    <span class="country-select-option-name">{{ $country['name'] }}</span>
                    <span class="country-select-code">{{ $country['dial'] }}</span>
                </button>
            @endforeach
            <div class="country-select-empty" hidden data-country-empty>No countries match your search.</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(() => {
    const root = document.getElementById(@json($instanceId));
    if (!root) return;
    const trigger = root.querySelector('[data-country-trigger]');
    const menu = root.querySelector('.country-select-menu');
    const search = root.querySelector('[data-country-search]');
    const options = [...root.querySelectorAll('.country-select-option')];
    const value = root.querySelector('[data-country-value]');
    const empty = root.querySelector('[data-country-empty]');
    const flagFor = iso => [...iso.toUpperCase()].map(char => String.fromCodePoint(127397 + char.charCodeAt(0))).join('');
    const close = () => { menu.hidden = true; trigger.setAttribute('aria-expanded', 'false'); };
    const visibleOptions = () => options.filter(option => !option.hidden);
    const open = () => { menu.hidden = false; trigger.setAttribute('aria-expanded', 'true'); search.value = ''; options.forEach(option => option.hidden = false); empty.hidden = true; search.focus(); };
    const filter = () => {
        const query = search.value.trim().toLocaleLowerCase();
        options.forEach(option => option.hidden = !option.dataset.search.includes(query));
        empty.hidden = visibleOptions().length > 0;
    };
    trigger.addEventListener('click', () => menu.hidden ? open() : close());
    search.addEventListener('input', filter);
    trigger.addEventListener('keydown', event => {
        if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') { event.preventDefault(); open(); }
    });
    search.addEventListener('keydown', event => {
        if (event.key === 'Escape') { close(); trigger.focus(); }
        if (event.key === 'ArrowDown') { event.preventDefault(); visibleOptions()[0]?.focus(); }
    });
    options.forEach((option, index) => {
        option.addEventListener('click', () => {
            value.value = option.dataset.dial;
            root.querySelector('[data-country-flag]').textContent = flagFor(option.dataset.iso);
            root.querySelector('[data-country-code]').textContent = option.dataset.dial;
            root.querySelector('[data-country-name]').textContent = option.dataset.name;
            options.forEach(item => item.setAttribute('aria-selected', item === option ? 'true' : 'false'));
            close(); trigger.focus();
        });
        option.addEventListener('keydown', event => {
            const visible = visibleOptions();
            const current = visible.indexOf(option);
            if (event.key === 'Escape') { close(); trigger.focus(); }
            if (event.key === 'ArrowDown') { event.preventDefault(); visible[Math.min(current + 1, visible.length - 1)]?.focus(); }
            if (event.key === 'ArrowUp') { event.preventDefault(); (visible[current - 1] || search).focus(); }
            if (event.key === 'Tab' && index === options.length - 1) close();
        });
    });
    document.addEventListener('click', event => { if (!root.contains(event.target)) close(); });
})();
</script>
@endpush
