<!DOCTYPE html>
<html lang="pt">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Área restrita do Colégio Lhaysso Kindergarten and School.">
    <meta name="keywords" content="Colégio Lhaysso, escola, login, portal escolar">
    <meta name="author" content="Colégio Lhaysso">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="{{ URL('/leading/images/favicon.png') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ URL('/leading/images/favicon.png') }}" type="image/x-icon">
    <title>Área Restrita | Colégio Lhaysso</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ URL('/assets/css/vendors/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL('/assets/css/font-awesome.css') }}">

    <style>
        :root {
            --lh-green: #17a64a;
            --lh-deep: #073b32;
            --lh-pink: #d93a8c;
            --lh-yellow: #f7b719;
            --lh-blue: #087da3;
            --lh-ink: #172026;
            --lh-muted: #637178;
            --lh-line: #dce7df;
            --lh-soft: #f5f8f6;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            color: var(--lh-ink);
            background: var(--lh-soft);
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(420px, .95fr);
        }

        .login-visual {
            position: relative;
            display: flex;
            align-items: flex-end;
            min-height: 100vh;
            padding: 56px;
            color: #ffffff;
            background:
                linear-gradient(180deg, rgba(7, 59, 50, .2), rgba(7, 59, 50, .88)),
                url("{{ asset('/assets/lhaysso-landing/hero-estudantes.jpeg') }}") center / cover no-repeat;
            overflow: hidden;
        }

        .login-visual::after {
            content: "";
            position: absolute;
            inset: auto 0 0;
            height: 9px;
            background: linear-gradient(90deg, var(--lh-green), var(--lh-yellow), var(--lh-pink), var(--lh-blue));
        }

        .visual-content {
            position: relative;
            max-width: 620px;
            z-index: 1;
        }

        .visual-pill {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .28);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .visual-pill i {
            color: var(--lh-yellow);
        }

        .login-visual h1 {
            margin: 0;
            color: #ffffff;
            font-size: clamp(42px, 6vw, 78px);
            line-height: .96;
            font-weight: 900;
            letter-spacing: 0;
        }

        .login-visual p {
            margin: 18px 0 0;
            max-width: 560px;
            color: rgba(255, 255, 255, .88);
            font-size: 17px;
            line-height: 1.75;
        }

        .login-panel {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 42px 28px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .92), rgba(255, 255, 255, .98)),
                radial-gradient(circle at top right, rgba(217, 58, 140, .16), transparent 34%);
        }

        .login-card-school {
            width: min(100%, 470px);
            border: 1px solid var(--lh-line);
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 28px 70px rgba(7, 59, 50, .14);
            padding: 34px;
        }

        .login-brand {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 26px;
        }

        .login-brand img {
            width: 76px;
            height: 76px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid var(--lh-line);
            padding: 6px;
            background: #ffffff;
        }

        .login-brand strong {
            display: block;
            color: var(--lh-deep);
            font-size: 19px;
            line-height: 1.2;
            font-weight: 900;
        }

        .login-brand span {
            display: block;
            margin-top: 4px;
            color: var(--lh-muted);
            font-size: 13px;
        }

        .login-card-school h2 {
            margin: 0 0 8px;
            color: var(--lh-deep);
            font-size: 28px;
            line-height: 1.12;
            font-weight: 900;
        }

        .login-card-school .lead {
            margin: 0 0 25px;
            color: var(--lh-muted);
            font-size: 15px;
            line-height: 1.65;
        }

        .form-group-school {
            margin-bottom: 18px;
        }

        .form-label {
            color: var(--lh-deep);
            font-weight: 800;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            color: var(--lh-green);
            font-size: 16px;
        }

        .school-input {
            width: 100%;
            min-height: 52px;
            border: 1px solid var(--lh-line);
            border-radius: 8px;
            padding: 0 14px 0 42px;
            color: var(--lh-ink);
            background: #fbfdfc;
            font-size: 15px;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .school-input:focus {
            border-color: var(--lh-green);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(23, 166, 74, .12);
        }

        .login-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin: 6px 0 18px;
            font-size: 13px;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--lh-muted);
            margin: 0;
        }

        .remember input {
            width: 16px;
            height: 16px;
            accent-color: var(--lh-green);
        }

        .forgot-link {
            color: var(--lh-pink);
            font-weight: 800;
        }

        .forgot-link:hover {
            color: var(--lh-deep);
            text-decoration: none;
        }

        .login-error {
            min-height: 22px;
            color: #b42318;
            background: #fff4f2;
            border: 1px solid #ffd7d2;
            border-radius: 8px;
            padding: 9px 11px;
            margin-bottom: 16px;
            display: none;
            font-size: 13px;
            line-height: 1.45;
        }

        .login-error:not(:empty) {
            display: block;
        }

        .school-submit {
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 8px;
            color: var(--lh-deep);
            background: var(--lh-yellow);
            font-weight: 900;
            font-size: 15px;
            box-shadow: 0 16px 32px rgba(247, 183, 25, .26);
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .school-submit:hover {
            transform: translateY(-1px);
            background: #ffd257;
            box-shadow: 0 20px 38px rgba(247, 183, 25, .32);
        }

        .school-submit:disabled {
            opacity: .72;
            cursor: wait;
            transform: none;
        }

        .login-help {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid var(--lh-line);
            color: var(--lh-muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .login-help a {
            color: var(--lh-green);
            font-weight: 900;
        }

        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 22px;
            color: var(--lh-deep);
            font-weight: 900;
            font-size: 13px;
        }

        .back-home:hover {
            color: var(--lh-green);
            text-decoration: none;
        }

        label.error {
            color: #b42318;
            font-size: 12px;
            margin: 7px 0 0;
        }

        @media (max-width: 991px) {
            .login-page {
                grid-template-columns: 1fr;
            }

            .login-visual {
                min-height: 360px;
                padding: 34px 24px;
            }

            .login-panel {
                min-height: auto;
                padding: 28px 18px 42px;
            }
        }

        @media (max-width: 520px) {
            .login-card-school {
                padding: 24px;
            }

            .login-row {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <main class="login-page">
        <section class="login-visual">
            <div class="visual-content">
                <div class="visual-pill"><i class="fa fa-star"></i> Área restrita escolar</div>
                <h1>Colégio Lhaysso</h1>
                <p>Acesso reservado para alunos, professores e equipa administrativa acompanharem a vida escolar com segurança.</p>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-card-school">
                <div class="login-brand">
                    <img src="{{ URL('/logotipo/lhaysso.png') }}" alt="Colégio Lhaysso">
                    <div>
                        <strong>Kindergarten and School</strong>
                        <span>Meu filho, meu tesouro</span>
                    </div>
                </div>

                <h2>Entrar no portal</h2>
                <p class="lead">Use o seu telefone, código ou utilizador fornecido pela secretaria.</p>

                <form id="formulario" novalidate>
                    @csrf

                    <div class="form-group-school">
                        <label class="form-label" for="user">Utilizador</label>
                        <div class="input-wrap">
                            <i class="fa fa-user-o"></i>
                            <input id="user" class="school-input" name="user" type="text" placeholder="Telefone, código ou utilizador" autocomplete="username" autofocus>
                        </div>
                    </div>

                    <div class="form-group-school">
                        <label class="form-label" for="senha">Senha</label>
                        <div class="input-wrap">
                            <i class="fa fa-lock"></i>
                            <input id="senha" class="school-input" type="password" name="senha" placeholder="Digite a sua senha" autocomplete="current-password">
                        </div>
                    </div>

                    <div class="login-row">
                        <label class="remember" for="checkbox1">
                            <input id="checkbox1" type="checkbox">
                            <span>Lembrar acesso</span>
                        </label>
                        <a class="forgot-link" href="#">Recuperar senha</a>
                    </div>

                    <div class="login-error" id="error"></div>

                    <button id="botao_salvar" type="submit" class="school-submit">
                        <span id="botao_texto">{{ __('Entrar com segurança') }}</span>
                        <i id="icon_enviar" class="fa fa-arrow-right ms-2"></i>
                    </button>

                    <p class="login-help">
                        Precisa de ajuda? Contacte a secretaria pelo
                        <a href="https://wa.me/258878403247" target="_blank" rel="noopener noreferrer">WhatsApp 878403247</a>.
                    </p>

                    <a class="back-home" href="#">
                        <i class="fa fa-angle-left"></i> Voltar ao site do colégio
                    </a>
                </form>
            </div>
        </section>
    </main>

    <script src="{{ URL('/assets/js/jquery.min.js') }}"></script>
    <script src="{{ URL('/assets/js/jquery.validate.js') }}"></script>
    <script src="{{ URL('/assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $("#formulario").validate({
                rules: {
                    user: {
                        required: true,
                        minlength: 2
                    },
                    senha: {
                        required: true,
                        minlength: 2
                    }
                },
                messages: {
                    user: {
                        required: "Informe o telefone, código ou utilizador.",
                        minlength: "O utilizador deve ter pelo menos 2 caracteres."
                    },
                    senha: {
                        required: "Informe a sua senha.",
                        minlength: "A senha deve ter pelo menos 2 caracteres."
                    }
                },
                submitHandler: function(form) {
                    $.ajax({
                        type: "POST",
                        url: "#",
                        data: $(form).serialize(),

                        beforeSend: function () {
                            $('#error').text('');
                            $('#botao_salvar').attr('disabled', true);
                            $('#icon_enviar').removeClass('fa-arrow-right').addClass('fa-spinner fa-spin');
                            $('#botao_texto').text('A autenticar...');
                        },

                        success: function(response) {
                            $('#botao_salvar').attr('disabled', false);
                            $('#icon_enviar').removeClass('fa-spinner fa-spin').addClass('fa-arrow-right');
                            $('#botao_texto').text('Entrar com segurança');

                            if(response.status == 1) {
                                if(response.codigo == 'A') {
                                    window.location.href = '/home/aluno';
                                } else if(response.codigo == 'P') {
                                    window.location.href = '/home/professor';
                                } else if(response.codigo == 'F') {
                                    window.location.href = '/home';
                                }
                            } else if(response.status == 0) {
                                $('#error').text(response.message);
                            }
                        },
                        error: function(errors) {
                            $('#botao_salvar').attr('disabled', false);
                            $('#icon_enviar').removeClass('fa-spinner fa-spin').addClass('fa-arrow-right');
                            $('#botao_texto').text('Entrar com segurança');

                            var message = 'Não foi possível autenticar. Verifique os dados e tente novamente.';
                            if (errors.responseJSON && errors.responseJSON.message) {
                                message = errors.responseJSON.message;
                            }
                            $('#error').text(message);
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
