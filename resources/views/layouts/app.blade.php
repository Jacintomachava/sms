<!DOCTYPE html>
<html lang="en">
  <head>

    @include('includes.css')

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
                        <img class="img-fluid" src="#" width="150" height="150"  alt="">
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
            <h2>{{ session('escola') }}</h2>
            <nav>
              <ol class="breadcrumb justify-content-sm-start align-items-center mb-0">
                <li class="breadcrumb-item"><a href="#">{{ session('slogan') }}</a></li>
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
                            <div class="spinner-border Typeahead-spinner" role="status"><span class="sr-only">Carregando...</span></div><i class="close-search" data-feather="x"></i>
                          </div>
                          <div class="Typeahead-menu"></div>
                        </div>
                      </div>
                    </form>
                    <div class="header-logo-wrapper col-auto p-0 left-header"></div>
                    <div class="nav-right col-auto pull-right right-header p-0 ms-auto">
                      <ul class="nav-menus">
                        <li class="onhover-dropdown">

                        @role('Secretaria') 
                          <div class="notification-box onhover-click">
                            <svg>
                              <use href="{{ URL('/assets/svg/icon-sprite.svg#notification')}}"></use>
                            </svg><span class="badge rounded-pill badge-success"> {{ session('totalReclamacao') }} </span>
                          </div>
                          <div class="onhover-show-div notification-dropdown">
                            <h6 class="f-18 mb-0 dropdown-title">Notificações WhatsApp</h6>
                            <ul>
                                @if(session('reclamacoes'))

                                 @foreach(session('reclamacoes') as $reclamacao)
                                  <li class="d-flex">
                                    <div class="notification-image"> <img class="img-fluid" src="{{ URL('/images/whatsapp.webp')}}" alt="user">
                                      <div class="notification-icon bg-success"><i class="fa fa-wechat"></i></div>
                                    </div>
                                    <div data-container="body" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="{{$reclamacao->mensagem}}">
                                      <p >
                                        0
                                      </p>
                                      <span class="f-light">
                                          //
                                      </span>
                                    </div>
                                  </li>
                                 @endforeach

                                 <li><a class="f-w-700" href="{{route('listar.reclamacoes')}}">Ver Todas</a></li>
                                @endif
                            </ul>
                          </div>
                        @endrole  
                        </li>
                        <li class="profile-nav onhover-dropdown">
                          <div class="onhover-click">
                            <div class="sidebar-image"> 
                              <img src="#" alt="profile">
                              <span class="status status-success"></span>
                            </div>
                            <div class="sidebar-content"> 
                              <span class="f-12 f-w-600 f-light">Nome</span><br>
                              <span class="f-12 f-w-600 f-light">Codigo</span>
                            </div>
                          </div>
                          <ul class="profile-dropdown onhover-show-div">
                              <li><a href="#">
                                <div class="profile-icon">
                                  <svg>
                                    <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-form') }}"></use>
                                  </svg>
                                </div><span>Alterar Senha</span></a>
                              </li>

                              <li>
                                  <form method="POST" action="{{ route('logout') }}">
                                      @csrf

                                      <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                                          <div class="profile-icon">
                                              <svg>
                                              <use href="{{ URL('/assets/svg/icon-sprite.svg#login') }}"></use>
                                              </svg>
                                          </div><span>Sair </span>
                                      </a>
                                  </form>
                                </li>
                          </ul>
                        </li>
                      </ul>
                    </div>
                    <script class="result-template" type="text/x-handlebars-template">
                      <div class="ProfileCard u-cf">                        
                      <div class="ProfileCard-avatar"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-airplay m-0"><path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path><polygon points="12 15 17 21 7 21 12 15"></polygon></svg></div>
                      <div class="ProfileCard-details">
                      <div class="ProfileCard-realName"></div>
                      </div>
                      </div>
                    </script>
                    <script class="empty-template" type="text/x-handlebars-template"><div class="EmptyMessage">Your search turned up 0 results. This most likely means the backend is down, yikes!</div></script>
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