<div class="sidebar-wrapper" data-sidebar-layout="stroke-svg">
    <div>
    <div class="logo-wrapper"><a href="#">
        <img class="img-fluid" src="{{ URL('/logotipo/'.session('logotipo')) }}" width="100" height="100" alt=""></a>
        <div class="back-btn"><i class="fa fa-angle-left"></i></div>
        <div class="toggle-sidebar"><i class="status_toggle middle sidebar-toggle" data-feather="grid"> </i></div>
    </div>
    <div class="logo-icon-wrapper"><a href="#">
        <img class="img-fluid" src="{{ URL('/logotipo/'.session('logotipo')) }}"  width="100" height="100" alt=""></a></div>
    <div class="profile-section sidebar-search"> 
        <div class="profile-wrapper">
        <div class="active-profile"> <img class="img-fluid"  src="#" alt="user">
            <div class="status bg-success"> </div>
        </div>
        <div> 
            <h4>Codigo</h4>
            <span>codigo utilizador</span>
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
          ....Escola
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

            @role('Secretaria')

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-learning') }}"></use>
                        </svg><span class="lan-3">Home</span></a>
                </li>
                
                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-blog') }}"></use>
                        </svg><span class="lan-3">Matriculas</span></a>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-icons') }}"></use>
                        </svg><span class="lan-3">Alunos</span></a>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-form') }}"></use>
                        </svg><span class="lan-3">Professores</span></a>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-form') }}"></use>
                        </svg><span class="lan-3">Prof. Turma</span></a>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-task') }}"></use>
                        </svg><span class="lan-3">Propina</span></a>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-task') }}"></use>
                        </svg><span class="lan-3">Pagamentos</span></a>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-task') }}"></use>
                        </svg><span class="lan-3">Referencias</span></a>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-chat') }}"></use>
                        </svg><span class="lan-3">Credito & SMS</span></a>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-chat') }}"></use>
                        </svg><span class="lan-3">Transferencia</span></a>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                    <span class="lan-3"><i style="font-size: 15pt; padding-right: 5pt; color: white" class="icofont icofont-chart-histogram"></i>Finanças</span></a>
                </li>
                    
                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                    <span class="lan-3"><i style="font-size: 15pt; padding-right: 5pt; color: white" class="icofont icofont-graduate-alt"></i>Pauta</span></a>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-faq') }}"></use>
                        </svg><span class="lan-3">Curriculo</span></a>
                </li>

            

               
                
            @endrole

            @role('Secretaria')    
                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">7</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-learning') }}"></use>
                        </svg><span class="lan-3">Parametrização</span></a>
                    <ul class="sidebar-submenu">
                        <li><a class="lan-1" href="#">Ano Academico</a></li>
                        <li><a class="lan-2" href="#">Turmas</a></li>
                        <li><a class="lan-2" href="#">Taxas</a></li>
                        <li><a class="lan-2" href="#">Disciplinas</a></li>
                        <li><a class="lan-2" href="#">Classes</a></li>
                        <li><a class="lan-2" href="#">Meses</a></li>
                        <li><a class="lan-2" href="#">Metodo Pagamento</a></li>
                        <li><a class="lan-2" href="#">Escola</a></li>
                    </ul>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">2</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-learning') }}"></use>
                        </svg><span class="lan-3">Administração</span></a>
                    <ul class="sidebar-submenu">
                        <li><a class="lan-1" href="#">Funcionarios</a></li>
                        <li><a class="lan-2" href="#">Usuarios</a></li>
                    </ul>
                </li>

            @endrole

            @role('Professor')

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-task') }}"></use>
                        </svg><span class="lan-3">Home</span></a>
                </li>

                 <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-task') }}"></use>
                        </svg><span class="lan-3">Turma</span></a>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-charts') }}"></use>
                        </svg><span class="lan-3">Notas</span></a>
                </li>

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-icons') }}"></use>
                        </svg><span class="lan-3">Meu Dados</span></a>
                </li>
            @endrole
           

            @role('Aluno')
                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-blog') }}"></use>
                        </svg><span class="lan-3">Matriculas</span></a>
                </li>
                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-charts') }}"></use>
                        </svg><span class="lan-3">Pauta</span></a>
                </li>
                

                <li class="sidebar-list"><i class="fa fa-thumb-tack"></i>
                    <label class="badge badge-light-primary">1</label><a class="sidebar-link sidebar-title" href="#">
                        <svg class="stroke-icon">
                        <use href="{{ URL('/assets/svg/icon-sprite.svg#stroke-icons') }}"></use>
                        </svg><span class="lan-3">Meu Dados</span></a>
                </li>
            @endrole


        </ul>
        </div>
        <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
    </nav>
    </div>
</div>