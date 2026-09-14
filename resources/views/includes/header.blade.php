    <!-- Page Header Start-->
    <div class="col-auto header-left-wrapper">
        <div class="header-logo-wrapper p-0 left-header">
        <div class="logo-wrapper"><a href="#"><img class="img-fluid" src="{{ URL('/assets/images/logo/logo_dark.png') }}" alt=""></a></div>
        </div>
        <div class="toggle-sidebar">
        <svg class="status_toggle sidebar-toggle">
            <use href="{{ URL('/assets/svg/icon-sprite.svg#collapse-sidebar') }}"></use>
        </svg>
        </div>
    </div>
    <div class="col-auto header-right-wrapper page-title">
        <div>
            <h2>Associacao</h2>
            <nav>
            <ol class="breadcrumb justify-content-sm-start align-items-center mb-0">
                <li class="breadcrumb-item f-w-500">Dashboard</li>
            </ol>
            </nav>
        </div>
    </div>
    <div class="col header-wrapper m-0 header-right-wrapper">
        <div class="row m-0">

        <div class="header-logo-wrapper col-auto p-0 left-header"></div>
        <div class="nav-right col-auto pull-right right-header p-0 ms-auto">
            <ul class="nav-menus">

            <li class="profile-nav onhover-dropdown">
                <div class="onhover-click">
                <div class="sidebar-image"> <img src="{{ URL('/assets/images/user.png') }}" alt="profile"><span class="status status-success"></span></div>
                <div class="sidebar-content"> 
                    <h4>Wade Warren</h4><span class="f-12 f-w-600 f-light">UI Designer</span>
                </div>
                </div>
                <ul class="profile-dropdown onhover-show-div">
                    <li><a href="sign-up.html">
                        <div class="profile-icon">
                            <svg>
                            <use href="{{ URL('/assets/svg/icon-sprite.svg#user') }}"></use>
                            </svg>
                        </div><span>Account </span></a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                                <div class="profile-icon">
                                    <svg>
                                    <use href="{{ URL('/assets/svg/icon-sprite.svg#login') }}"></use>
                                    </svg>
                                </div><span>Log out1</span>
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