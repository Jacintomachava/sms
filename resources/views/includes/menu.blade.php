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
            <h4>Codigo</h4>
            <span>codigo utilizador44</span>
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

                    <a
                        class="sidebar-link sidebar-title {{ request()->routeIs('compras.sms.*') ? 'active' : '' }}"
                        href="{{ route('compras.sms.index') }}"
                    >
                        <svg class="stroke-icon">
                            <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-ecommerce') }}"></use>
                        </svg>

                        <span>Créditos SMS</span>
                    </a>
                </li>

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

                    <label class="badge badge-light-primary">1</label>

                    <a
                        class="sidebar-link sidebar-title"
                        href="{{ route('sender-ids.index') }}"
                    >
                        <svg class="stroke-icon">
                            <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-blog') }}"></use>
                        </svg>

                        <span class="lan-3">Sender</span>
                    </a>
                </li>

                <li class="sidebar-list">

                    <i class="fa fa-thumb-tack"></i>

                    <a
                        class="sidebar-link sidebar-title"
                        href="{{ route('api-keys.index') }}"
                    >
                        <svg class="stroke-icon">
                            <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-blog') }}"></use>
                        </svg>

                        <span>API</span>

                    </a>

                </li>

            @endif

            {{-- ============================================================
                MENUS DA ADMINISTRAÇÃO / PLATFORM
            ============================================================ --}}

            @if(session('contexto') === 'PLATFORM')

                <li class="sidebar-list">

                    <i class="fa fa-thumb-tack"></i>

                    <a
                        class="sidebar-link sidebar-title"
                        href="{{ route('admin.tarifas-sms.index') }}"
                    >

                        <svg class="stroke-icon">
                            <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-ecommerce') }}">
                            </use>
                        </svg>

                        <span>Tarifas SMS</span>

                    </a>

                </li>

                <li class="sidebar-list">
                    <i class="fa fa-thumb-tack"></i>

                    <label class="badge badge-light-primary">1</label>

                    <a
                        class="sidebar-link sidebar-title"
                        href="{{ route('admin.sender-ids.index') }}"
                    >
                        <svg class="stroke-icon">
                            <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-icons') }}"></use>
                        </svg>

                        <span class="lan-3">Senders Admin</span>
                    </a>
                </li>

            @endif

        </ul>
        </div>
        <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
    </nav>
    </div>
</div>