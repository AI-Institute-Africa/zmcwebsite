{{-- Reusable in-page tab bar. Usage: @include('staff.partials.tabs', ['tabs' => [[route_name, label, icon], ...], 'label' => 'Section navigation']) --}}
<nav class="zmc-tabs" role="tablist" aria-label="{{ $label ?? 'Section navigation' }}">
  @foreach($tabs as [$tabRoute, $tabLabel, $tabIcon])
    @php $isCurrent = request()->routeIs($tabRoute); @endphp
    <a href="{{ route($tabRoute) }}" role="tab" aria-selected="{{ $isCurrent ? 'true' : 'false' }}" @if($isCurrent) aria-current="page" @endif><i class="{{ $tabIcon }}" aria-hidden="true"></i> {{ $tabLabel }}</a>
  @endforeach
</nav>
