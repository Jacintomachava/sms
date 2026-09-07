<!DOCTYPE html>
<html lang="en">
  <head>
    @include('includes.css')

    {{-- Token CSRF obrigatório --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
      .error {
          color: red;
          margin: 0;
      }
    </style>
    @stack('css')
  </head>

  <body> 
    <!-- loader starts-->
    <div class="loader-wrapper">
      <div class="loader"></div>
    </div>
    <!-- loader ends-->
    <!-- tap on top starts-->
    <div class="tap-top"><i data-feather="chevrons-up"></i></div>
    <!-- tap on tap ends-->
    <!-- page-wrapper Start-->
    <div class="page-wrapper compact-wrapper" id="pageWrapper">
      <div class="page-header row">
        <!-- Page Header Start-->
        <div class="col-auto header-left-wrapper">
          <div class="header-logo-wrapper p-0 left-header">
            <div class="logo-wrapper">
              <a href="#">
                <img class="img-fluid" src="{{ URL('/logotipo/logo.png') }}" width="150" height="150" alt="Logo">
              </a>
            </div>
          </div>
          <div class="toggle-sidebar">
            <svg class="status_toggle sidebar-toggle">
              <use href="{{ URL('/assets/svg/icon-sprite.svg#collapse-sidebar') }}"></use>
            </svg>
          </div>
        </div>

        <div class="col-auto header-right-wrapper page-title">
          <div>
            <h2>SMS</h2>
            <nav>
              <ol class="breadcrumb justify-content-sm-start align-items-center mb-0">
                <li class="breadcrumb-item">
                  @if(Auth::check())
                    <a href="#">{{ Auth::user()->username ?? 'Usuário' }}</a>
                  @else
                    <a href="#">Visitante</a>
                  @endif
                </li>
              </ol>
            </nav>
          </div>
        </div>

        <div class="col header-wrapper m-0 header-right-wrapper">
          <div class="row m-0">
            <form class="form-inline search-full col" action="#" method="get">
              <div class="form-group w-100">
                <div class="Typeahead Typeahead--twitterUsers">
                  <div class="u-posRelative">
                    <input class="demo-input Typeahead-input form-control-plaintext w-100" type="text" placeholder="Search anything .." name="q" title="" autofocus>
                    <div class="spinner-border Typeahead-spinner" role="status"><span class="sr-only">Carregando...</span></div>
                    <i class="close-search" data-feather="x"></i>
                  </div>
                  <div class="Typeahead-menu"></div>
                </div>
              </div>
            </form>

            <div class="header-logo-wrapper col-auto p-0 left-header"></div>
            <div class="nav-right col-auto pull-right right-header p-0 ms-auto">
              <ul class="nav-menus">
                <li class="onhover-dropdown">
                  <div class="notification-box onhover-click">
                    <svg>
                      <use href="{{ URL('/assets/svg/icon-sprite.svg#notification')}}"></use>
                    </svg>
                    <span class="badge rounded-pill badge-success">35</span>
                  </div>
                  <div class="onhover-show-div notification-dropdown">
                    <h6 class="f-18 mb-0 dropdown-title">Novos Post</h6>
                    <ul></ul>
                  </div>
                </li>

                @if(Auth::check())
                <li class="profile-nav onhover-dropdown">
                  <div class="onhover-click">
                    <div class="sidebar-image"> 
                      <img src="{{ URL('/avatar/avatar.png') }}" width="150" height="150" alt="profile">
                      <span class="status status-success"></span>
                    </div>
                    <div class="sidebar-content"> 
                      <span class="f-12 f-w-600 f-light">{{ Auth::user()->nome ?? '' }} </span><br>
                      <span class="f-12 f-w-600 f-light">{{ Auth::user()->role ?? 'N/A' }}</span>
                    </div>
                  </div>
                  <ul class="profile-dropdown onhover-show-div">
                    <li>
                      <a href="{{ route('senha.index') }}">
                        <div class="profile-icon">
                          <svg>
                            <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-form') }}"></use>
                          </svg>
                        </div>
                        <span>Alterar Senha</span>
                      </a>
                    </li>

                    <li>
                      <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                          <div class="profile-icon">
                            <svg>
                              <use href="{{ URL('/assets/svg/icon-sprite.svg#login') }}"></use>
                            </svg>
                          </div>
                          <span>Sair</span>
                        </a>
                      </form>
                    </li>
                  </ul>
                </li>
                @endif
              </ul>
            </div>
          </div>
        </div>
        <!-- Page Header Ends-->
      </div>

      <!-- Page Body Start-->
      <div class="page-body-wrapper">
        <!-- Page Sidebar Start   Menu-->
        @include('includes.menu')
        <!-- Page Sidebar Ends   Menu-->

        <div class="page-body">
          <!-- Container-fluid starts-->
          <div class="container-fluid">
            @yield('conteudo')
          </div>
        </div>

        <!-- footer start-->
        @include('includes.footer')
      </div>
    </div>

    @include('includes.js')
    @stack('js')
  </body>
</html>
