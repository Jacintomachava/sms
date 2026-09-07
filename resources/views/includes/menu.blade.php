<div class="sidebar-wrapper" data-sidebar-layout="stroke-svg">
    <div>
        <div class="logo-wrapper">
            <a href="#">
                <img class="img-fluid" src="{{ URL('/logotipo/logo.png') }}" width="100" height="100" alt="">
            </a>
            <div class="back-btn"><i class="fa fa-angle-left"></i></div>
            <div class="toggle-sidebar">
                <i class="status_toggle middle sidebar-toggle" data-feather="grid"></i>
            </div>
        </div>

        <div class="logo-icon-wrapper">
            <a href="#">
                <img class="img-fluid" src="{{ URL('/logotipo/logo.png') }}" width="100" height="100" alt="">
            </a>
        </div>

        {{-- Se o usuário estiver autenticado, mostra o perfil --}}
        @auth
        <div class="profile-section sidebar-search">
            <div class="profile-wrapper">
                <div class="active-profile">
                    <img src="{{ URL('/avatar/avatar.png') }}" width="150" height="150" alt="profile">
                    <div class="status bg-success"></div>
                </div>
                <div>
                    <h4>Nível</h4>
                    <span>{{ Auth::user()->role }}</span>
                </div>
            </div>
            <div>
                <svg>
                    <use href="{{ URL('/assets/svg/icon-sprite.svg#profile-setting') }}"></use>
                </svg>
            </div>
        </div>
        @else
        {{-- Caso não esteja autenticado, mostra algo genérico --}}
        <div class="profile-section sidebar-search">
            <div class="profile-wrapper">
                <div class="active-profile">
                    <img src="{{ URL('/avatar/avatar.png') }}" width="150" height="150" alt="profile">
                    <div class="status bg-secondary"></div>
                </div>
                <div>
                    <h4>Nível</h4>
                    <span>Visitante</span>
                </div>
            </div>
        </div>
        @endauth

        <div class="sidebar-search">
            <div class="input-group">
                SMS
            </div>
        </div>

        <nav class="sidebar-main">
            <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
            <div id="sidebar-menu">
                <ul class="sidebar-links" id="simple-bar">
                    <li class="back-btn">
                        <a href="index.html"><img class="img-fluid" src="" alt=""></a>
                        <div class="mobile-back text-end">
                            <span>Back</span>
                            <i class="fa fa-angle-right ps-2" aria-hidden="true"></i>
                        </div>
                    </li>

                    <li class="sidebar-list">
                        <i class="fa fa-thumb-tack"></i>
                        <label class="badge badge-light-primary">1</label>
                        <a class="sidebar-link sidebar-title" href="{{ route('home.index') }}">
                            <svg class="stroke-icon">
                                <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-learning') }}"></use>
                            </svg>
                            <span class="lan-3">Home</span>
                        </a>
                    </li>

                    <li class="sidebar-list">
                        <i class="fa fa-thumb-tack"></i>
                        <label class="badge badge-light-primary">1</label>
                        <a class="sidebar-link sidebar-title" href="{{route('cliente.index')}}">
                            <svg class="stroke-icon">
                                <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-blog') }}"></use>
                            </svg>
                            <span class="lan-3">CLientes</span>
                        </a>
                    </li>

                    <li class="sidebar-list">
                        <i class="fa fa-thumb-tack"></i>
                        <label class="badge badge-light-primary">1</label>
                        <a class="sidebar-link sidebar-title" href="{{route('compras.index')}}">
                            <svg class="stroke-icon">
                                <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-blog') }}"></use>
                            </svg>
                            <span class="lan-3">Compras SMS</span>
                        </a>
                    </li>

                    <li class="sidebar-list">
                        <i class="fa fa-thumb-tack"></i>
                        <label class="badge badge-light-primary">1</label>
                        <a class="sidebar-link sidebar-title" href="#">
                            <svg class="stroke-icon">
                                <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-blog') }}"></use>
                            </svg>
                            <span class="lan-3">Credito & SMS</span>
                        </a>
                    </li>

                    <li class="sidebar-list">
                        <i class="fa fa-thumb-tack"></i>
                        <label class="badge badge-light-primary">1</label>
                        <a class="sidebar-link sidebar-title" href="{{route('recarregamento.index')}}">
                            <svg class="stroke-icon">
                                <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-blog') }}"></use>
                            </svg>
                            <span class="lan-3">Recargas</span>
                        </a>
                    </li>

                    <li class="sidebar-list">
                        <i class="fa fa-thumb-tack"></i>
                        <label class="badge badge-light-primary">1</label>
                        <a class="sidebar-link sidebar-title" href="{{route('user.index')}}">
                            <svg class="stroke-icon">
                                <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-learning') }}"></use>
                            </svg>
                            <span class="lan-3">Utilizadores</span>
                        </a>
                    </li>

                    
                </ul>
            </div>
            <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
        </nav>
    </div>
</div>
