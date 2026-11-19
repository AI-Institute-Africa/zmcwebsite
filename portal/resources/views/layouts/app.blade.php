<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <style>
        .zmc-pilot-notice{position:fixed;top:12px;left:50%;transform:translateX(-50%);z-index:3000;width:min(760px,calc(100% - 24px));padding:10px 16px;border:1px solid rgba(250,204,21,.55);border-radius:12px;background:rgba(22,30,35,.96);box-shadow:0 8px 24px rgba(0,0,0,.22);color:#fff;font:600 12px/1.4 Inter,sans-serif}.zmc-pilot-notice strong{color:#facc15}@media(max-width:600px){.zmc-pilot-notice{top:8px;padding:8px 11px;font-size:11px}}
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  @include('layouts.favicon')
</head>
<body>
    <div class="zmc-pilot-notice" role="status" id="zmcPilotNotice"><span>This portal is currently in a testing phase and is not yet accepting applications. In the meantime, please continue to submit applications at the Commission's head office or Bulawayo regional office. For further information or guidance, kindly contact the Commission.</span><button type="button" id="zmcNoticeClose" aria-label="Close" style="margin-left:auto;background:transparent;border:none;color:#facc15;cursor:pointer;font-size:22px;line-height:1;">&times;</button></div>
    <script>try{var n=document.getElementById("zmcPilotNotice");if(localStorage.getItem("zmc_pilot_notice_closed")=="1"&&n){n.style.display="none";}var b=document.getElementById("zmcNoticeClose");if(b){b.onclick=function(){try{localStorage.setItem("zmc_pilot_notice_closed","1");}catch(e){}if(n){n.style.display="none";}};}}catch(e){}</script>

    
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>
</body>
</html>
