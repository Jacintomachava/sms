@php
    $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');
    $registerUrl = \Illuminate\Support\Facades\Route::has('register') ? route('register') : url('/register');
@endphp
<header class="site-header" id="inicio">
    <div class="container navbar">
        <a class="brand" href="{{ route('home') }}" aria-label="Infordata - Início">
            <img src="{{ asset('assets/inforsms/logo.png') }}" alt="Logótipo Infordata">
            <span>Infordata<small>SMS</small></span>
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-nav" aria-label="Abrir menu">
            <span></span><span></span><span></span>
        </button>
        <nav id="main-nav" class="nav-links" aria-label="Menu principal">
            <a href="{{ route('home') }}">Início</a>
            <a href="{{ url('/') }}#planos">Planos</a>
            <a href="{{ url('/') }}#funcionalidades">Funcionalidades</a>
            <a class="docs-link" href="{{ url('/documentacao') }}">&lt;/&gt; Documentação de APIs</a>
            <a href="{{ url('/') }}#como-funciona">Como funciona</a>
            <a href="{{ url('/') }}#contactos">Contactos</a>
            <div class="nav-actions">
                <a class="btn btn-outline btn-sm" href="{{ route('formulario') }}">Entrar</a>
                <a class="btn btn-primary btn-sm" href="{{ route('registar') }}">Criar Conta</a>
            </div>
        </nav>
    </div>
</header>
