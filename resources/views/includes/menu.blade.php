<div class="sidebar-wrapper" data-sidebar-layout="stroke-svg">
    <div>
    <div class="logo-wrapper"><a href="#">
        <img class="img-fluid" src="{{ URL('/logotipo/logotipo.png') }}" width="100" height="100" alt=""></a>
        <div class="back-btn"><i class="fa fa-angle-left"></i></div>
        <div class="toggle-sidebar"><i class="status_toggle middle sidebar-toggle" data-feather="grid"> </i></div>
    </div>
    <div class="logo-icon-wrapper"><a href="#">
        <img class="img-fluid" src="{{ URL('/logotipo/logotipo.png') }}"  width="100" height="100" alt=""></a></div>
    <div class="profile-section sidebar-search"> 
        <div class="profile-wrapper">
        <div class="active-profile"> <img class="img-fluid"  src="{{ URL('/avatar/avatar.png') }}" alt="user">
            <div class="status bg-success"> </div>
        </div>
        <div> 
            <h4>TIPO</h4>
            <span>{{session('tipo_cobranca')}}</span>
        </div>
        </div>
        <div>
        <svg>
            <use href="{{ URL('/assets/svg/icon-sprite.svg#profile-setting') }}"></use>
        </svg>
        </div>
    </div>
    <div class="sidebar-search"> 
        <div class="input-group">
          Servicos de SMS
        </div>
    </div>
    <nav class="sidebar-main">
        <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
        <div id="sidebar-menu">
        <ul class="sidebar-links" id="simple-bar">
            <li class="back-btn"><a href="index.html"><img class="img-fluid" src="#" alt=""></a>
            <div class="mobile-back text-end"><span>Back</span><i class="fa fa-angle-right ps-2" aria-hidden="true"></i></div>
            </li>
            <li class="pin-title sidebar-main-title">
            <div> 
                <h6>- Pinned</h6>
            </div>
            </li>

            {{-- ============================================================
                MENUS DO CLIENTE / ACCOUNT
            ============================================================ --}}

            @if(session('contexto') === 'ACCOUNT')


                <li class="sidebar-list">

                    <i class="fa fa-thumb-tack"></i>
                    <a class="sidebar-link sidebar-title"  href="javascript:void(0)">
                        <svg class="stroke-icon">
                            <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-contact') }}"></use>
                        </svg>
                        <span>Contactos</span>

                        <div class="according-menu">
                            <i class="fa fa-angle-right"></i>
                        </div>

                    </a>

                    <ul class="sidebar-submenu">

                        <li>
                            <a href="{{ route('contactos.index') }}">
                                Contactos
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('grupos-contactos.index') }}">
                                Grupos
                            </a>
                        </li>

                    </ul>

                </li>

                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <label class="badge badge-light-primary"></label>

                    <a
                        class="sidebar-link sidebar-title"
                        href="{{ route('sender-ids.index') }}"
                    >
                        <svg class="stroke-icon">
                            <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-blog') }}"></use>
                        </svg>

                        <span class="lan-3">Sender </span>
                    </a>
                </li>

                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <label class="badge badge-light-primary"></label>

                    <a
                        class="sidebar-link sidebar-title"
                        href="{{ route('financeiro.index') }}"
                    >
                        <svg class="stroke-icon">
                            <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-blog') }}"></use>
                        </svg>

                        <span class="lan-3">Financeiro </span>
                    </a>
                </li>

                @if(session('tipo_cobranca') === 'PRE_PAGO')

                    <li class="sidebar-list">
                        <i class="fa fa-thumb-tack"></i>

                        <a
                            class="sidebar-link sidebar-title {{ request()->routeIs('compras.sms.*') ? 'active' : '' }}"
                            href="{{ route('compras.sms.index') }}"
                        >
                            <svg class="stroke-icon">
                                <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-ecommerce') }}"></use>
                            </svg>

                            <span>Recarregar </span>
                        </a>
                    </li>

                @endif

                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <a
                        class="sidebar-link sidebar-title {{ request()->routeIs('sms.*') ? 'active' : '' }}"
                        href="{{ route('sms.hitorico') }}"
                    >
                        <svg class="stroke-icon">
                            <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-email') }}"></use>
                        </svg>

                        <span>SMS</span>
                    </a>
                </li>

                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <a
                        class="sidebar-link sidebar-title
                        {{ request()->routeIs('api-keys.*') ? 'active' : '' }}"
                        href="{{ route('api-keys.index') }}"
                    >
                        <svg class="stroke-icon">
                            <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-blog') }}"></use>
                        </svg>

                        <span>Integração API</span>
                    </a>
                </li>

            @endif

            {{-- ============================================================
                ADMINISTRAÇÃO INFORDATA / PLATFORM
            ============================================================ --}}

            @if(session('contexto') === 'PLATFORM')

                {{-- 1. DASHBOARD --}}
                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <a class="sidebar-link sidebar-title"
                    href="{{ route('admin.dashboard.home') }}">

                        <span class="lan-3">
                            <i class="fa fa-home"
                            style="font-size: 15pt; padding-right: 5pt; color: white"></i>
                            Dashboard
                        </span>
                    </a>
                </li>


                {{-- 2. CONTAS --}}
                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <a class="sidebar-link sidebar-title"
                    href="{{ route('admin.contas.index') }}">

                        <span class="lan-3">
                            <i class="fa fa-building"
                            style="font-size: 15pt; padding-right: 5pt; color: white"></i>
                            Contas
                        </span>
                    </a>
                </li>


                {{-- 3. SENDERS ADMIN --}}
                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <a class="sidebar-link sidebar-title"
                    href="{{ route('admin.sender-ids.index') }}">

                        <span class="lan-3">
                            <i class="fa fa-paper-plane"
                            style="font-size: 15pt; padding-right: 5pt; color: white"></i>
                            Senders Admin
                        </span>
                    </a>
                </li>


                {{-- 4. MONITORIZAÇÃO SMS --}}
                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <a class="sidebar-link sidebar-title"
                    href="{{ route('admin.sms.index') }}">

                        <span class="lan-3">
                            <i class="fa fa-envelope"
                            style="font-size: 15pt; padding-right: 5pt; color: white"></i>
                            Monitorização SMS
                        </span>
                    </a>
                </li>


                {{-- 5. STOCK SMS --}}
                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <a class="sidebar-link sidebar-title"
                    href="{{ route('admin.stock.index') }}">

                        <span class="lan-3">
                            <i class="fa fa-cubes"
                            style="font-size: 15pt; padding-right: 5pt; color: white"></i>
                            Stock SMS
                        </span>
                    </a>
                </li>


                {{-- 6. TARIFAS SMS --}}
                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <a class="sidebar-link sidebar-title"
                    href="{{ route('admin.tarifas-sms.index') }}">

                        <span class="lan-3">
                            <i class="fa fa-tags"
                            style="font-size: 15pt; padding-right: 5pt; color: white"></i>
                            Tarifas SMS
                        </span>
                    </a>
                </li>


                {{-- 7. GESTÃO FINANCEIRA --}}
                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <a class="sidebar-link sidebar-title"
                    href="{{ route('admin.financeiro.dashboard') }}">

                        <span class="lan-3">
                            <i class="fa fa-line-chart"
                            style="font-size: 15pt; padding-right: 5pt; color: white"></i>
                            Gestão Financeira
                        </span>
                    </a>
                </li>


                {{-- 8. FACTURAS --}}
                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <a class="sidebar-link sidebar-title"
                    href="{{ route('admin.facturas.index') }}">

                        <span class="lan-3">
                            <i class="fa fa-file-text-o"
                            style="font-size: 15pt; padding-right: 5pt; color: white"></i>
                            Facturas
                        </span>
                    </a>
                </li>


                {{-- 9. UTILIZADORES, ROLES E PERMISSÕES --}}
                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <a class="sidebar-link sidebar-title"
                    href="{{ Route::has('admin.users.index') ? route('admin.users.index') : 'javascript:void(0)' }}">

                        <span class="lan-3">
                            <i class="fa fa-users"
                            style="font-size: 15pt; padding-right: 5pt; color: white"></i>
                            Utilizadores
                        </span>
                    </a>
                </li>

            @endif

        </ul>
        </div>
        <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
    </nav>
    </div>
</div>