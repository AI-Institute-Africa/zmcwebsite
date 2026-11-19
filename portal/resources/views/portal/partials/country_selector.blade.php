@php
$selectorId = $selectorId ?? 'country_selector_' . uniqid();
$fieldName = $fieldName ?? 'nationality';
$fieldLabel = $fieldLabel ?? 'Nationality';
$isRequired = $isRequired ?? true;
$currentValue = $currentValue ?? '';
@endphp

<div class="zmc-country-selector position-relative" id="{{ $selectorId }}_wrapper">
  <label class="form-label {{ $isRequired ? 'required' : '' }}">{{ $fieldLabel }}</label>
  <div class="position-relative">
    <input type="text"
           class="form-control zmc-country-search"
           id="{{ $selectorId }}_search"
           placeholder="Search country..."
           autocomplete="off"
           value="{{ $currentValue }}"
           {{ $isRequired ? 'required' : '' }}>
    <input type="hidden" name="{{ $fieldName }}" id="{{ $selectorId }}_value" value="{{ $currentValue }}">
    <div class="zmc-country-dropdown position-absolute w-100 border rounded-3 shadow-sm bg-white d-none"
         id="{{ $selectorId }}_dropdown"
         style="z-index:1050; max-height:240px; overflow-y:auto; top:100%; left:0;">
    </div>
  </div>
</div>

@once
@push('scripts')
<script>
window.ZMC_COUNTRIES = [
  {code:'ZW',name:'Zimbabwe',dial:'+263',flag:'🇿🇼'},
  {code:'ZA',name:'South Africa',dial:'+27',flag:'🇿🇦'},
  {code:'ZM',name:'Zambia',dial:'+260',flag:'🇿🇲'},
  {code:'MZ',name:'Mozambique',dial:'+258',flag:'🇲🇿'},
  {code:'BW',name:'Botswana',dial:'+267',flag:'🇧🇼'},
  {code:'MW',name:'Malawi',dial:'+265',flag:'🇲🇼'},
  {code:'NA',name:'Namibia',dial:'+264',flag:'🇳🇦'},
  {code:'TZ',name:'Tanzania',dial:'+255',flag:'🇹🇿'},
  {code:'KE',name:'Kenya',dial:'+254',flag:'🇰🇪'},
  {code:'UG',name:'Uganda',dial:'+256',flag:'🇺🇬'},
  {code:'RW',name:'Rwanda',dial:'+250',flag:'🇷🇼'},
  {code:'NG',name:'Nigeria',dial:'+234',flag:'🇳🇬'},
  {code:'GH',name:'Ghana',dial:'+233',flag:'🇬🇭'},
  {code:'ET',name:'Ethiopia',dial:'+251',flag:'🇪🇹'},
  {code:'EG',name:'Egypt',dial:'+20',flag:'🇪🇬'},
  {code:'CD',name:'DR Congo',dial:'+243',flag:'🇨🇩'},
  {code:'SN',name:'Senegal',dial:'+221',flag:'🇸🇳'},
  {code:'CI',name:'Côte d\'Ivoire',dial:'+225',flag:'🇨🇮'},
  {code:'CM',name:'Cameroon',dial:'+237',flag:'🇨🇲'},
  {code:'AO',name:'Angola',dial:'+244',flag:'🇦🇴'},
  {code:'DZ',name:'Algeria',dial:'+213',flag:'🇩🇿'},
  {code:'SD',name:'Sudan',dial:'+249',flag:'🇸🇩'},
  {code:'MA',name:'Morocco',dial:'+212',flag:'🇲🇦'},
  {code:'TN',name:'Tunisia',dial:'+216',flag:'🇹🇳'},
  {code:'LY',name:'Libya',dial:'+218',flag:'🇱🇾'},
  {code:'GN',name:'Guinea',dial:'+224',flag:'🇬🇳'},
  {code:'ML',name:'Mali',dial:'+223',flag:'🇲🇱'},
  {code:'BF',name:'Burkina Faso',dial:'+226',flag:'🇧🇫'},
  {code:'NE',name:'Niger',dial:'+227',flag:'🇳🇪'},
  {code:'TD',name:'Chad',dial:'+235',flag:'🇹🇩'},
  {code:'MG',name:'Madagascar',dial:'+261',flag:'🇲🇬'},
  {code:'SO',name:'Somalia',dial:'+252',flag:'🇸🇴'},
  {code:'SS',name:'South Sudan',dial:'+211',flag:'🇸🇸'},
  {code:'ER',name:'Eritrea',dial:'+291',flag:'🇪🇷'},
  {code:'DJ',name:'Djibouti',dial:'+253',flag:'🇩🇯'},
  {code:'BI',name:'Burundi',dial:'+257',flag:'🇧🇮'},
  {code:'BJ',name:'Benin',dial:'+229',flag:'🇧🇯'},
  {code:'TG',name:'Togo',dial:'+228',flag:'🇹🇬'},
  {code:'SL',name:'Sierra Leone',dial:'+232',flag:'🇸🇱'},
  {code:'LR',name:'Liberia',dial:'+231',flag:'🇱🇷'},
  {code:'CF',name:'Central African Republic',dial:'+236',flag:'🇨🇫'},
  {code:'CG',name:'Republic of Congo',dial:'+242',flag:'🇨🇬'},
  {code:'GA',name:'Gabon',dial:'+241',flag:'🇬🇦'},
  {code:'GQ',name:'Equatorial Guinea',dial:'+240',flag:'🇬🇶'},
  {code:'LS',name:'Lesotho',dial:'+266',flag:'🇱🇸'},
  {code:'SZ',name:'Eswatini',dial:'+268',flag:'🇸🇿'},
  {code:'MU',name:'Mauritius',dial:'+230',flag:'🇲🇺'},
  {code:'SC',name:'Seychelles',dial:'+248',flag:'🇸🇨'},
  {code:'CV',name:'Cape Verde',dial:'+238',flag:'🇨🇻'},
  {code:'GM',name:'Gambia',dial:'+220',flag:'🇬🇲'},
  {code:'GW',name:'Guinea-Bissau',dial:'+245',flag:'🇬🇼'},
  {code:'MR',name:'Mauritania',dial:'+222',flag:'🇲🇷'},
  {code:'KM',name:'Comoros',dial:'+269',flag:'🇰🇲'},
  {code:'ST',name:'São Tomé and Príncipe',dial:'+239',flag:'🇸🇹'},
  {code:'GB',name:'United Kingdom',dial:'+44',flag:'🇬🇧'},
  {code:'US',name:'United States',dial:'+1',flag:'🇺🇸'},
  {code:'CA',name:'Canada',dial:'+1',flag:'🇨🇦'},
  {code:'AU',name:'Australia',dial:'+61',flag:'🇦🇺'},
  {code:'NZ',name:'New Zealand',dial:'+64',flag:'🇳🇿'},
  {code:'DE',name:'Germany',dial:'+49',flag:'🇩🇪'},
  {code:'FR',name:'France',dial:'+33',flag:'🇫🇷'},
  {code:'IT',name:'Italy',dial:'+39',flag:'🇮🇹'},
  {code:'ES',name:'Spain',dial:'+34',flag:'🇪🇸'},
  {code:'PT',name:'Portugal',dial:'+351',flag:'🇵🇹'},
  {code:'NL',name:'Netherlands',dial:'+31',flag:'🇳🇱'},
  {code:'BE',name:'Belgium',dial:'+32',flag:'🇧🇪'},
  {code:'SE',name:'Sweden',dial:'+46',flag:'🇸🇪'},
  {code:'NO',name:'Norway',dial:'+47',flag:'🇳🇴'},
  {code:'DK',name:'Denmark',dial:'+45',flag:'🇩🇰'},
  {code:'FI',name:'Finland',dial:'+358',flag:'🇫🇮'},
  {code:'CH',name:'Switzerland',dial:'+41',flag:'🇨🇭'},
  {code:'AT',name:'Austria',dial:'+43',flag:'🇦🇹'},
  {code:'IE',name:'Ireland',dial:'+353',flag:'🇮🇪'},
  {code:'PL',name:'Poland',dial:'+48',flag:'🇵🇱'},
  {code:'RU',name:'Russia',dial:'+7',flag:'🇷🇺'},
  {code:'UA',name:'Ukraine',dial:'+380',flag:'🇺🇦'},
  {code:'CZ',name:'Czech Republic',dial:'+420',flag:'🇨🇿'},
  {code:'RO',name:'Romania',dial:'+40',flag:'🇷🇴'},
  {code:'HU',name:'Hungary',dial:'+36',flag:'🇭🇺'},
  {code:'GR',name:'Greece',dial:'+30',flag:'🇬🇷'},
  {code:'BG',name:'Bulgaria',dial:'+359',flag:'🇧🇬'},
  {code:'RS',name:'Serbia',dial:'+381',flag:'🇷🇸'},
  {code:'HR',name:'Croatia',dial:'+385',flag:'🇭🇷'},
  {code:'SK',name:'Slovakia',dial:'+421',flag:'🇸🇰'},
  {code:'SI',name:'Slovenia',dial:'+386',flag:'🇸🇮'},
  {code:'LT',name:'Lithuania',dial:'+370',flag:'🇱🇹'},
  {code:'LV',name:'Latvia',dial:'+371',flag:'🇱🇻'},
  {code:'EE',name:'Estonia',dial:'+372',flag:'🇪🇪'},
  {code:'IS',name:'Iceland',dial:'+354',flag:'🇮🇸'},
  {code:'LU',name:'Luxembourg',dial:'+352',flag:'🇱🇺'},
  {code:'MT',name:'Malta',dial:'+356',flag:'🇲🇹'},
  {code:'CY',name:'Cyprus',dial:'+357',flag:'🇨🇾'},
  {code:'AL',name:'Albania',dial:'+355',flag:'🇦🇱'},
  {code:'MK',name:'North Macedonia',dial:'+389',flag:'🇲🇰'},
  {code:'BA',name:'Bosnia and Herzegovina',dial:'+387',flag:'🇧🇦'},
  {code:'ME',name:'Montenegro',dial:'+382',flag:'🇲🇪'},
  {code:'MD',name:'Moldova',dial:'+373',flag:'🇲🇩'},
  {code:'BY',name:'Belarus',dial:'+375',flag:'🇧🇾'},
  {code:'GE',name:'Georgia',dial:'+995',flag:'🇬🇪'},
  {code:'AM',name:'Armenia',dial:'+374',flag:'🇦🇲'},
  {code:'AZ',name:'Azerbaijan',dial:'+994',flag:'🇦🇿'},
  {code:'KZ',name:'Kazakhstan',dial:'+7',flag:'🇰🇿'},
  {code:'UZ',name:'Uzbekistan',dial:'+998',flag:'🇺🇿'},
  {code:'CN',name:'China',dial:'+86',flag:'🇨🇳'},
  {code:'JP',name:'Japan',dial:'+81',flag:'🇯🇵'},
  {code:'KR',name:'South Korea',dial:'+82',flag:'🇰🇷'},
  {code:'IN',name:'India',dial:'+91',flag:'🇮🇳'},
  {code:'PK',name:'Pakistan',dial:'+92',flag:'🇵🇰'},
  {code:'BD',name:'Bangladesh',dial:'+880',flag:'🇧🇩'},
  {code:'LK',name:'Sri Lanka',dial:'+94',flag:'🇱🇰'},
  {code:'NP',name:'Nepal',dial:'+977',flag:'🇳🇵'},
  {code:'MM',name:'Myanmar',dial:'+95',flag:'🇲🇲'},
  {code:'KH',name:'Cambodia',dial:'+855',flag:'🇰🇭'},
  {code:'LA',name:'Laos',dial:'+856',flag:'🇱🇦'},
  {code:'ID',name:'Indonesia',dial:'+62',flag:'🇮🇩'},
  {code:'MY',name:'Malaysia',dial:'+60',flag:'🇲🇾'},
  {code:'SG',name:'Singapore',dial:'+65',flag:'🇸🇬'},
  {code:'TH',name:'Thailand',dial:'+66',flag:'🇹🇭'},
  {code:'PH',name:'Philippines',dial:'+63',flag:'🇵🇭'},
  {code:'VN',name:'Vietnam',dial:'+84',flag:'🇻🇳'},
  {code:'TW',name:'Taiwan',dial:'+886',flag:'🇹🇼'},
  {code:'HK',name:'Hong Kong',dial:'+852',flag:'🇭🇰'},
  {code:'MN',name:'Mongolia',dial:'+976',flag:'🇲🇳'},
  {code:'AE',name:'United Arab Emirates',dial:'+971',flag:'🇦🇪'},
  {code:'SA',name:'Saudi Arabia',dial:'+966',flag:'🇸🇦'},
  {code:'QA',name:'Qatar',dial:'+974',flag:'🇶🇦'},
  {code:'KW',name:'Kuwait',dial:'+965',flag:'🇰🇼'},
  {code:'BH',name:'Bahrain',dial:'+973',flag:'🇧🇭'},
  {code:'OM',name:'Oman',dial:'+968',flag:'🇴🇲'},
  {code:'JO',name:'Jordan',dial:'+962',flag:'🇯🇴'},
  {code:'LB',name:'Lebanon',dial:'+961',flag:'🇱🇧'},
  {code:'IQ',name:'Iraq',dial:'+964',flag:'🇮🇶'},
  {code:'IR',name:'Iran',dial:'+98',flag:'🇮🇷'},
  {code:'AF',name:'Afghanistan',dial:'+93',flag:'🇦🇫'},
  {code:'IL',name:'Israel',dial:'+972',flag:'🇮🇱'},
  {code:'TR',name:'Turkey',dial:'+90',flag:'🇹🇷'},
  {code:'BR',name:'Brazil',dial:'+55',flag:'🇧🇷'},
  {code:'MX',name:'Mexico',dial:'+52',flag:'🇲🇽'},
  {code:'AR',name:'Argentina',dial:'+54',flag:'🇦🇷'},
  {code:'CL',name:'Chile',dial:'+56',flag:'🇨🇱'},
  {code:'CO',name:'Colombia',dial:'+57',flag:'🇨🇴'},
  {code:'PE',name:'Peru',dial:'+51',flag:'🇵🇪'},
  {code:'VE',name:'Venezuela',dial:'+58',flag:'🇻🇪'},
  {code:'EC',name:'Ecuador',dial:'+593',flag:'🇪🇨'},
  {code:'BO',name:'Bolivia',dial:'+591',flag:'🇧🇴'},
  {code:'PY',name:'Paraguay',dial:'+595',flag:'🇵🇾'},
  {code:'UY',name:'Uruguay',dial:'+598',flag:'🇺🇾'},
  {code:'CU',name:'Cuba',dial:'+53',flag:'🇨🇺'},
  {code:'DO',name:'Dominican Republic',dial:'+1',flag:'🇩🇴'},
  {code:'HT',name:'Haiti',dial:'+509',flag:'🇭🇹'},
  {code:'JM',name:'Jamaica',dial:'+1',flag:'🇯🇲'},
  {code:'TT',name:'Trinidad and Tobago',dial:'+1',flag:'🇹🇹'},
  {code:'PA',name:'Panama',dial:'+507',flag:'🇵🇦'},
  {code:'CR',name:'Costa Rica',dial:'+506',flag:'🇨🇷'},
  {code:'GT',name:'Guatemala',dial:'+502',flag:'🇬🇹'},
  {code:'HN',name:'Honduras',dial:'+504',flag:'🇭🇳'},
  {code:'NI',name:'Nicaragua',dial:'+505',flag:'🇳🇮'},
  {code:'SV',name:'El Salvador',dial:'+503',flag:'🇸🇻'},
  {code:'FJ',name:'Fiji',dial:'+679',flag:'🇫🇯'},
  {code:'PG',name:'Papua New Guinea',dial:'+675',flag:'🇵🇬'},
  {code:'WS',name:'Samoa',dial:'+685',flag:'🇼🇸'},
  {code:'TO',name:'Tonga',dial:'+676',flag:'🇹🇴'},
];

function zmcInitCountrySelector(wrapperId) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  const search = wrapper.querySelector('.zmc-country-search');
  const hidden = wrapper.querySelector('input[type="hidden"]');
  const dropdown = wrapper.querySelector('.zmc-country-dropdown');
  if (!search || !hidden || !dropdown) return;

  function render(filter) {
    const q = (filter || '').toLowerCase();
    const matches = window.ZMC_COUNTRIES.filter(c =>
      c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q)
    );

    dropdown.innerHTML = '';
    if (matches.length === 0) {
      dropdown.innerHTML = '<div class="p-2 text-muted text-center" style="font-size:12px;">No matches</div>';
      return;
    }

    matches.forEach(c => {
      const item = document.createElement('div');
      item.className = 'px-3 py-2 d-flex align-items-center gap-2 zmc-country-item';
      item.style.cssText = 'cursor:pointer; font-size:13px; transition:background .15s;';
      item.onmouseover = () => item.style.background = '#f1f5f9';
      item.onmouseout = () => item.style.background = '';

      const flag = document.createElement('span');
      flag.textContent = c.flag;
      flag.style.fontSize = '18px';

      const name = document.createElement('span');
      name.textContent = c.name;
      name.className = 'fw-medium';

      const code = document.createElement('span');
      code.textContent = c.code;
      code.className = 'text-muted ms-auto';
      code.style.fontSize = '11px';

      item.appendChild(flag);
      item.appendChild(name);
      item.appendChild(code);

      item.addEventListener('click', () => {
        search.value = c.flag + ' ' + c.name;
        hidden.value = c.name;
        dropdown.classList.add('d-none');
      });

      dropdown.appendChild(item);
    });
  }

  search.addEventListener('focus', () => {
    render(search.value.replace(/^[^\w]*/, ''));
    dropdown.classList.remove('d-none');
  });

  search.addEventListener('input', () => {
    const v = search.value.replace(/^[^\w]*/, '');
    render(v);
    dropdown.classList.remove('d-none');
    hidden.value = v;
  });

  document.addEventListener('click', e => {
    if (!wrapper.contains(e.target)) dropdown.classList.add('d-none');
  });

  if (hidden.value) {
    const match = window.ZMC_COUNTRIES.find(c => c.name === hidden.value);
    if (match) search.value = match.flag + ' ' + match.name;
  }
}

function zmcPopulatePhoneCodeSelects() {
  document.querySelectorAll('.zmc-phone-code-select').forEach(sel => {
    if (sel.dataset.zmcPopulated) {
      const defaultValue = (sel.dataset.defaultValue || '').trim();
      if (defaultValue) {
        sel.value = defaultValue;
      }
      return;
    }
    sel.dataset.zmcPopulated = '1';
    const seen = new Set();
    window.ZMC_COUNTRIES.forEach(c => {
      if (seen.has(c.dial)) return;
      seen.add(c.dial);
      const opt = document.createElement('option');
      opt.value = c.dial;
      opt.textContent = `${c.flag} ${c.dial} (${c.code})`;
      if ((sel.dataset.defaultValue || '').trim() === c.dial) {
        opt.selected = true;
      }
      sel.appendChild(opt);
    });
    const defaultValue = (sel.dataset.defaultValue || '').trim();
    if (defaultValue) {
      sel.value = defaultValue;
    }
  });
}
document.addEventListener('DOMContentLoaded', zmcPopulatePhoneCodeSelects);

function zmcSyncCountrySelector(wrapperId) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  const search = wrapper.querySelector('.zmc-country-search');
  const hidden = wrapper.querySelector('input[type="hidden"]');
  if (!search || !hidden || !hidden.value) return;
  const match = window.ZMC_COUNTRIES.find(c => c.name === hidden.value);
  if (match) search.value = match.flag + ' ' + match.name;
  else search.value = hidden.value;
}
</script>
<style>
.zmc-country-dropdown::-webkit-scrollbar { width: 6px; }
.zmc-country-dropdown::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
</style>
@endpush
@endonce
