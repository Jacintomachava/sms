<!DOCTYPE html>
<html lang="pt">

<head>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible"  content="IE=edge">
    <meta name="viewport"  content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token"  content="{{ csrf_token() }}">
    <meta name="description"  content="INFORDATA SMS - Plataforma de envio de SMS">
    <meta name="author" content="INFORDATA">
    <title>Criar Conta | INFORDATA SMS</title>
    <!-- Favicon -->
    <link rel="icon"  href="{{ URL('/assets/images/favicon.png') }}" type="image/x-icon">
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect"  href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet"  href="{{ URL('/assets/css/font-awesome.css') }}">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="{{ URL('/assets/css/vendors/bootstrap.css') }}">
    <!-- ============================================================= -->
    <!-- CSS -->
    <!-- ============================================================= -->
    <style>
        :root {
            --primary: #6557ff;
            --primary-light: #f4f2ff;
            --primary-border: #7669ff;
            --text: #171a2b;
            --muted: #7d8597;
            --border: #dce2ea;
            --background: #effcff;
            --danger: #dc3545;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            font-family: "Inter", sans-serif;
            color: var(--text);
        }

        body {

            min-height: 100vh;
            background:
                radial-gradient(
                    circle at 10% 35%,
                    rgba(56, 189, 248, .12) 0,
                    rgba(56, 189, 248, .05) 18%,
                    transparent 35%
                ),
                radial-gradient(
                    circle at 85% 20%,
                    rgba(14, 165, 233, .10),
                    transparent 30%
                ),
                linear-gradient(
                    135deg,
                    #f7fdff 0%,
                    #eafaff 45%,
                    #ffffff 100%
                );
        }
        /* ============================================================
           PAGE
        ============================================================ */
        .register-page {
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 30px 20px 50px;
            position: relative;
            overflow: hidden;
        }
        /*
        |--------------------------------------------------------------------------
        | ELEMENTOS DECORATIVOS
        |--------------------------------------------------------------------------
        */
        .register-page::before {
            content: "";
            position: fixed;
            width: 400px;
            height: 400px;
            left: -180px;
            top: 230px;
            border-radius: 50%;
            border: 55px solid rgba(14, 165, 233, .06);
            pointer-events: none;
        }

        .register-page::after {
            content: "";
            position: fixed;
            width: 350px;
            height: 350px;
            right: -160px;
            bottom: -100px;
            border-radius: 50%;
            border: 40px solid rgba(101, 87, 255, .035);
            pointer-events: none;
        }
        /* ============================================================
           WRAPPER
        ============================================================ */
        .register-wrapper {
            width: 100%;
            max-width: 760px;
            position: relative;
            z-index: 2;
        }
        /* ============================================================
           LOGO
        ============================================================ */
        .register-logo {
            text-align: center;
            margin-bottom: 14px;
        }
        .register-logo img {
            width: 115px;
            max-height: 65px;
            object-fit: contain;
        }
        /* ============================================================
           CARD
        ============================================================ */
        .register-card {
            width: 100%;
            background: rgba(255, 255, 255, .97);
            border: 1px solid rgba(210, 220, 230, .8);
            border-radius: 14px;
            padding: 30px 34px;
            box-shadow:
                0 25px 65px rgba(28, 66, 94, .09),
                0 4px 12px rgba(28, 66, 94, .04);
        }
        .register-card h1 {
            margin: 0 0 6px;
            font-size: 28px;
            font-weight: 800;
            color: #101426;
        }
        .register-subtitle {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
            margin-bottom: 22px;
        }
        /* ============================================================
           LABEL
        ============================================================ */
        .form-label-custom {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
            color: #25293b;
        }
        /* ============================================================
           TIPO CONTA
        ============================================================ */
        .account-type-title {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .account-types {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 22px;
        }
        .account-option {
            position: relative;
        }
        .account-option input {
            position: absolute;
            opacity: 0;
            width: 1px;
            height: 1px;
        }
        .account-option label {
            width: 100%;
            min-height: 78px;
            border: 1px solid var(--border);
            border-radius: 9px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            margin: 0;
            background: #ffffff;
            transition: all .2s ease;
            position: relative;
        }
        .account-option label:hover {
            border-color: var(--primary-border);
            background: #faf9ff;
        }
        .account-option label i {
            font-size: 19px;
            margin-bottom: 6px;
        }
        .account-option label strong {
            font-size: 14px;
            font-weight: 600;
        }
        .account-option label small {
            margin-top: 3px;
            font-size: 11px;
            color: var(--muted);
        }
        .account-option input:checked + label {

            border-color: var(--primary-border);
            background: var(--primary-light);
            color: var(--primary);
            box-shadow:
                0 0 0 1px rgba(101, 87, 255, .05);

        }
        .account-option input:checked + label::after {

            content: "";

            position: absolute;

            right: 12px;

            top: 12px;

            width: 11px;

            height: 11px;

            border-radius: 50%;

            background: var(--primary);

            box-shadow:
                0 0 0 3px #ffffff,
                0 0 0 4px var(--primary);

        }


        /* ============================================================
           SECTION TITLE
        ============================================================ */

        .section-title {

            font-size: 16px;

            font-weight: 700;

            margin: 0 0 14px;

        }


        /* ============================================================
           GRID
        ============================================================ */

        .form-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            column-gap: 16px;

            row-gap: 15px;

        }


        .form-full {

            grid-column: 1 / -1;

        }


        .form-group-custom {

            min-width: 0;

        }


        /* ============================================================
           INPUT
        ============================================================ */

        .input-wrapper {

            position: relative;

        }


        .input-wrapper > i.input-icon {

            position: absolute;

            left: 14px;

            top: 50%;

            transform: translateY(-50%);

            color: #667085;

            font-size: 14px;

            pointer-events: none;

        }


        .form-control-custom {

            width: 100%;

            height: 46px;

            border: 1px solid var(--border);

            border-radius: 7px;

            padding:

                0
                14px
                0
                42px;

            font-size: 13px;

            color: #1f2937;

            outline: none;

            background: #ffffff;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;

        }


        .form-control-custom::placeholder {

            color: #a0a8b8;

        }


        .form-control-custom:focus {

            border-color: var(--primary-border);

            box-shadow:
                0 0 0 3px rgba(101, 87, 255, .09);

        }


        /* ============================================================
           PASSWORD
        ============================================================ */

        .password-input {

            padding-right: 45px;

        }


        .toggle-password {

            position: absolute;

            right: 14px;

            top: 50%;

            transform: translateY(-50%);

            border: 0;

            padding: 0;

            background: transparent;

            color: #778195;

            cursor: pointer;

            font-size: 15px;

        }


        /* ============================================================
           EMPRESA
        ============================================================ */

        #campos_empresa {

            display: none;

            margin-top: 20px;

            padding: 20px;

            background:
                linear-gradient(
                    135deg,
                    #f8fbfd,
                    #f2f8fb
                );

            border: 1px solid #e4ebf0;

            border-radius: 10px;

        }


        .company-header {

            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 17px;

        }


        .company-icon {

            width: 38px;

            height: 38px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 7px;

            background: #ffffff;

            border: 1px solid #e2e8f0;

            color: #3f485b;

            font-size: 18px;

        }


        .company-header strong {

            display: block;

            font-size: 14px;

        }


        .company-header small {

            display: block;

            color: var(--muted);

            font-size: 11px;

            margin-top: 2px;

        }


        /* ============================================================
           TERMOS
        ============================================================ */

        .terms-wrapper {

            margin-top: 20px;

            padding-top: 17px;

            border-top: 1px solid #e5e9ef;

        }


        .terms-label {

            display: inline-flex;

            align-items: center;

            gap: 9px;

            cursor: pointer;

            font-size: 13px;

            color: #3f4657;

        }


        .terms-label input {

            width: 17px;

            height: 17px;

            accent-color: var(--primary);

        }


        .terms-label a {

            color: #0767df;

            font-weight: 600;

            text-decoration: none;

        }


        /* ============================================================
           BUTTON
        ============================================================ */

        .submit-button {

            width: 100%;

            height: 48px;

            margin-top: 16px;

            border: none;

            border-radius: 7px;

            background:
                linear-gradient(
                    90deg,
                    #6557ff,
                    #765cff
                );

            color: #ffffff;

            font-size: 14px;

            font-weight: 700;

            cursor: pointer;

            box-shadow:
                0 10px 22px rgba(101, 87, 255, .20);

            transition:
                transform .2s ease,
                box-shadow .2s ease;

        }


        .submit-button:hover {

            transform: translateY(-1px);

            box-shadow:
                0 13px 28px rgba(101, 87, 255, .26);

        }


        .submit-button:disabled {

            opacity: .7;

            cursor: wait;

            transform: none;

        }


        /* ============================================================
           LOGIN
        ============================================================ */

        .login-link {

            text-align: center;

            margin-top: 19px;

            padding-top: 17px;

            border-top: 1px solid #e7ebef;

            font-size: 13px;

            color: var(--muted);

        }


        .login-link a {

            color: #0767df;

            font-weight: 700;

            text-decoration: none;

            margin-left: 4px;

        }


        /* ============================================================
           ERROR
        ============================================================ */

        label.error {

            display: block;

            color: var(--danger);

            font-size: 11px;

            margin-top: 5px;

            font-weight: 400;

        }


        .form-control-custom.error {

            border-color: var(--danger);

        }


        #error {

            display: none;

            margin-top: 15px;

            padding: 10px 12px;

            background: #fff4f4;

            border: 1px solid #ffd4d4;

            border-radius: 7px;

            color: #c62828;

            font-size: 12px;

            line-height: 1.5;

        }


        /* ============================================================
           RESPONSIVO
        ============================================================ */

        @media (max-width: 700px) {

            .register-page {

                padding: 20px 14px 35px;

            }


            .register-card {

                padding: 24px 20px;

            }


            .form-grid {

                grid-template-columns: 1fr;

            }


            .form-full {

                grid-column: auto;

            }

        }


        @media (max-width: 480px) {

            .account-types {

                grid-template-columns: 1fr;

            }


            .register-card h1 {

                font-size: 24px;

            }

        }

    </style>

</head>


<body>


<main class="register-page">


    <div class="register-wrapper">


        <!-- =========================================================
             LOGO
        ========================================================== -->

        <div class="register-logo">

            <img
                src="{{ URL('/logotipo/logotipo.png') }}"
                alt="INFORDATA">

        </div>



        <!-- =========================================================
             CARD
        ========================================================== -->

        <div class="register-card">


            <h1>Criar sua conta</h1>


            <div class="register-subtitle">

                Crie a sua conta para começar a utilizar
                a plataforma INFORDATA SMS.

            </div>



            <!-- =====================================================
                 FORM
            ====================================================== -->

            <form
                id="formulario"
                novalidate>


                @csrf



                <!-- =================================================
                     TIPO CONTA
                ================================================== -->

                <div class="account-type-title">

                    Tipo de conta

                </div>


                <div class="account-types">


                    <!-- INDIVIDUAL -->

                    <div class="account-option">

                        <input
                            type="radio"
                            name="tipo_conta"
                            id="tipo_individual"
                            value="INDIVIDUAL"
                            checked>


                        <label for="tipo_individual">

                            <i class="fa fa-user"></i>

                            <strong>
                                Individual
                            </strong>

                            <small>
                                Para uso pessoal
                            </small>

                        </label>

                    </div>



                    <!-- EMPRESA -->

                    <div class="account-option">

                        <input
                            type="radio"
                            name="tipo_conta"
                            id="tipo_empresa"
                            value="EMPRESA">


                        <label for="tipo_empresa">

                            <i class="fa fa-building"></i>

                            <strong>
                                Empresa
                            </strong>

                            <small>
                                Para o seu negócio
                            </small>

                        </label>

                    </div>


                </div>



                <!-- =================================================
                     DADOS PESSOAIS
                ================================================== -->

                <h3 class="section-title">
                    Dados pessoais
                </h3>


                <div class="form-grid">


                    <!-- =================================================
                         NOME - SOZINHO
                    ================================================== -->

                    <div class="form-group-custom form-full">


                        <label
                            class="form-label-custom"
                            for="name">

                            Nome completo

                        </label>


                        <div class="input-wrapper">


                            <i class="fa fa-user-o input-icon"></i>


                            <input
                                type="text"
                                class="form-control-custom"
                                id="name"
                                name="name"
                                placeholder="Ex.: Jacinto Machava"
                                autocomplete="name">


                        </div>


                    </div>



                    <!-- =================================================
                         EMAIL
                    ================================================== -->

                    <div class="form-group-custom">


                        <label
                            class="form-label-custom"
                            for="email">

                            Email

                        </label>


                        <div class="input-wrapper">


                            <i class="fa fa-envelope-o input-icon"></i>


                            <input
                                type="email"
                                class="form-control-custom"
                                id="email"
                                name="email"
                                placeholder="exemplo@email.com"
                                autocomplete="email">


                        </div>


                    </div>



                    <!-- =================================================
                         TELEFONE
                    ================================================== -->

                    <div class="form-group-custom">


                        <label
                            class="form-label-custom"
                            for="telefone">

                            Telefone

                        </label>


                        <div class="input-wrapper">


                            <i class="fa fa-phone input-icon"></i>


                            <input
                                type="text"
                                class="form-control-custom"
                                id="telefone"
                                name="telefone"
                                placeholder="84xxxxxxx"
                                autocomplete="tel">


                        </div>


                    </div>



                    <!-- =================================================
                         PASSWORD
                    ================================================== -->

                    <div class="form-group-custom">


                        <label
                            class="form-label-custom"
                            for="password">

                            Palavra-passe

                        </label>


                        <div class="input-wrapper">


                            <i class="fa fa-lock input-icon"></i>


                            <input
                                type="password"
                                class="form-control-custom password-input"
                                id="password"
                                name="password"
                                placeholder="Mínimo 8 caracteres"
                                autocomplete="new-password">


                            <button
                                type="button"
                                class="toggle-password"
                                data-target="password">

                                <i class="fa fa-eye"></i>

                            </button>


                        </div>


                    </div>



                    <!-- =================================================
                         CONFIRMAR PASSWORD
                    ================================================== -->

                    <div class="form-group-custom">


                        <label
                            class="form-label-custom"
                            for="password_confirmation">

                            Confirmar palavra-passe

                        </label>


                        <div class="input-wrapper">


                            <i class="fa fa-lock input-icon"></i>


                            <input
                                type="password"
                                class="form-control-custom password-input"
                                id="password_confirmation"
                                name="password_confirmation"
                                placeholder="Confirme a palavra-passe"
                                autocomplete="new-password">


                            <button
                                type="button"
                                class="toggle-password"
                                data-target="password_confirmation">

                                <i class="fa fa-eye"></i>

                            </button>


                        </div>


                    </div>


                </div>



                <!-- =================================================
                     EMPRESA
                ================================================== -->

                <div id="campos_empresa">


                    <div class="company-header">


                        <div class="company-icon">

                            <i class="fa fa-building"></i>

                        </div>


                        <div>

                            <strong>
                                Dados da empresa
                            </strong>

                            <small>
                                Preencha as informações da sua empresa.
                            </small>

                        </div>


                    </div>



                    <div class="form-grid">


                        <!-- NOME EMPRESA -->

                        <div class="form-group-custom">


                            <label
                                class="form-label-custom"
                                for="nome_empresa">

                                Nome da empresa

                            </label>


                            <div class="input-wrapper">


                                <i class="fa fa-building-o input-icon"></i>


                                <input
                                    type="text"
                                    class="form-control-custom"
                                    id="nome_empresa"
                                    name="nome_empresa"
                                    placeholder="Ex.: INFORDATA">


                            </div>


                        </div>



                        <!-- NOME LEGAL -->

                        <div class="form-group-custom">


                            <label
                                class="form-label-custom"
                                for="nome_legal">

                                Nome legal

                            </label>


                            <div class="input-wrapper">


                                <i class="fa fa-file-text-o input-icon"></i>


                                <input
                                    type="text"
                                    class="form-control-custom"
                                    id="nome_legal"
                                    name="nome_legal"
                                    placeholder="Ex.: INFORDATA, LDA">


                            </div>


                        </div>



                        <!-- NUIT -->

                        <div class="form-group-custom form-full">


                            <label
                                class="form-label-custom"
                                for="nuit">

                                NUIT

                            </label>


                            <div class="input-wrapper">


                                <i class="fa fa-hashtag input-icon"></i>


                                <input
                                    type="text"
                                    class="form-control-custom"
                                    id="nuit"
                                    name="nuit"
                                    placeholder="NUIT da empresa">


                            </div>


                        </div>


                    </div>


                </div>



                <!-- =================================================
                     TERMOS
                ================================================== -->

                <div class="terms-wrapper">


                    <label
                        class="terms-label"
                        for="termos">


                        <input
                            type="checkbox"
                            id="termos"
                            name="termos"
                            value="1">


                        <span>

                            Aceito os

                            <a href="#">
                                Termos e Condições
                            </a>

                        </span>


                    </label>


                </div>



                <!-- =================================================
                     ERRO AJAX
                ================================================== -->

                <div id="error"></div>



                <!-- =================================================
                     SUBMIT
                ================================================== -->

                <button
                    type="submit"
                    id="botao_salvar"
                    class="submit-button">


                    <span id="botao_texto">

                        Criar Conta

                    </span>


                    <i
                        id="icon_enviar"
                        class="fa fa-arrow-right ms-2">

                    </i>


                </button>



                <!-- =================================================
                     LOGIN
                ================================================== -->

                <div class="login-link">


                    Já tenho uma conta?


                    <a href="{{ route('login') }}">

                        Entrar

                    </a>


                </div>


            </form>


        </div>


    </div>


</main>



<!-- ============================================================= -->
<!-- JAVASCRIPT -->
<!-- ============================================================= -->


<script src="{{ URL('/assets/js/jquery.min.js') }}"></script>


<script src="{{ URL('/assets/js/jquery.validate.js') }}"></script>


<script src="{{ URL('/assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



<script>

$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */
    $.ajaxSetup({

        headers: {

            'X-CSRF-TOKEN':
                $('meta[name="csrf-token"]').attr('content')

        }

    });



    /*
    |--------------------------------------------------------------------------
    | MOSTRAR / ESCONDER EMPRESA
    |--------------------------------------------------------------------------
    */


    function atualizarTipoConta() {


        var tipo =
            $('input[name="tipo_conta"]:checked').val();


        if (tipo === 'EMPRESA') {


            $('#campos_empresa')
                .stop(true, true)
                .slideDown(180);


        } else {


            $('#campos_empresa')
                .stop(true, true)
                .slideUp(180);


            /*
             * Não chamamos .valid().
             * Portanto clicar Individual/Empresa
             * NÃO dispara validação.
             */


            $('#nome_empresa')
                .removeClass('error');


            $('#nome_legal')
                .removeClass('error');


            $('#nuit')
                .removeClass('error');


            $('#campos_empresa label.error')
                .remove();

        }

    }



    /*
    |--------------------------------------------------------------------------
    | MUDANÇA TIPO
    |--------------------------------------------------------------------------
    */


    $('input[name="tipo_conta"]').on(
        'change',
        function () {

            atualizarTipoConta();

        }
    );


    atualizarTipoConta();



    /*
    |--------------------------------------------------------------------------
    | MOSTRAR PASSWORD
    |--------------------------------------------------------------------------
    */


    $('.toggle-password').on(
        'click',
        function () {


            var target =
                $(this).data('target');


            var input =
                $('#' + target);


            var icon =
                $(this).find('i');


            if (
                input.attr('type') ===
                'password'
            ) {


                input.attr(
                    'type',
                    'text'
                );


                icon
                    .removeClass('fa-eye')
                    .addClass('fa-eye-slash');


            } else {


                input.attr(
                    'type',
                    'password'
                );


                icon
                    .removeClass('fa-eye-slash')
                    .addClass('fa-eye');

            }


        }
    );



    /*
    |--------------------------------------------------------------------------
    | JQUERY VALIDATE
    |--------------------------------------------------------------------------
    |
    | Não adicionamos "messages".
    |
    | O teu jquery.validate.js já contém
    | as mensagens traduzidas.
    |
    */


    $('#formulario').validate({


        /*
        |--------------------------------------------------------------------------
        | NÃO VALIDAR AO CLICAR
        |--------------------------------------------------------------------------
        */


        onfocusout: false,

        onclick: false,

        onkeyup: false,



        /*
        |--------------------------------------------------------------------------
        | CAMPOS ESCONDIDOS
        |--------------------------------------------------------------------------
        */


        ignore:
            ':hidden:not(input[name="tipo_conta"])',



        /*
        |--------------------------------------------------------------------------
        | REGRAS
        |--------------------------------------------------------------------------
        */


        rules: {


            name: {

                required: true,

                minlength: 3,

                maxlength: 255

            },


            email: {

                required: true,

                email: true,

                maxlength: 255

            },


            telefone: {

                required: true,

                minlength: 9,

                maxlength: 20

            },


            password: {

                required: true,

                minlength: 8

            },


            password_confirmation: {

                required: true,

                equalTo: '#password'

            },


            nome_empresa: {


                required: function () {


                    return $(
                        'input[name="tipo_conta"]:checked'
                    ).val() === 'EMPRESA';


                },


                minlength: 2,

                maxlength: 255

            },


            nome_legal: {


                required: function () {


                    return $(
                        'input[name="tipo_conta"]:checked'
                    ).val() === 'EMPRESA';


                },


                minlength: 2,

                maxlength: 255

            },


            nuit: {


                required: function () {


                    return $(
                        'input[name="tipo_conta"]:checked'
                    ).val() === 'EMPRESA';


                },


                minlength: 9,

                maxlength: 20

            },


            termos: {

                required: true

            }


        },



        /*
        |--------------------------------------------------------------------------
        | POSIÇÃO DOS ERROS
        |--------------------------------------------------------------------------
        */


        errorPlacement: function (
            error,
            element
        ) {


            /*
            |--------------------------------------------------------------------------
            | TERMOS
            |--------------------------------------------------------------------------
            */


            if (
                element.attr('name') ===
                'termos'
            ) {


                error.insertAfter(
                    element.closest(
                        '.terms-label'
                    )
                );


                return;

            }



            /*
            |--------------------------------------------------------------------------
            | INPUT WRAPPER
            |--------------------------------------------------------------------------
            */


            if (
                element.closest(
                    '.input-wrapper'
                ).length
            ) {


                error.insertAfter(
                    element.closest(
                        '.input-wrapper'
                    )
                );


                return;

            }


            error.insertAfter(element);

        },



        /*
        |--------------------------------------------------------------------------
        | SUBMIT
        |--------------------------------------------------------------------------
        */


        submitHandler: function (form) {


            $.ajax({


                type: 'POST',


                url:
                    "{{ route('register.store') }}",


                data:
                    $(form).serialize(),



                /*
                |--------------------------------------------------------------------------
                | BEFORE
                |--------------------------------------------------------------------------
                */


                beforeSend: function () {


                    $('#error')
                        .hide()
                        .html('');


                    $('#botao_salvar')
                        .prop(
                            'disabled',
                            true
                        );


                    $('#icon_enviar')
                        .removeClass(
                            'fa-arrow-right'
                        )
                        .addClass(
                            'fa-spinner fa-spin'
                        );


                    $('#botao_texto')
                        .text(
                            'A criar conta...'
                        );

                },



                /*
                |--------------------------------------------------------------------------
                | SUCCESS
                |--------------------------------------------------------------------------
                */


                success: function (
                    response
                ) {


                    if (response.status == 1) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Conta criada com sucesso!',
                            text: response.message ?? 'A sua conta foi criada com sucesso.',
                            confirmButtonText: 'Continuar',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            timer: 2500,
                            timerProgressBar: true
                        }).then(function () {

                            window.location.href =
                                response.redirect ?? '/dashboard';

                        });

                        return;
                    }


                    restaurarBotao();


                    Swal.fire({
                        icon: 'error',
                        title: 'Não foi possível criar a conta',
                        text: response.message
                            ?? 'Não foi possível criar a conta.',
                        confirmButtonText: 'OK'
                    });

                },



                /*
                |--------------------------------------------------------------------------
                | ERROR
                |--------------------------------------------------------------------------
                */
                error: function (xhr) {


                    restaurarBotao();



                    /*
                    |--------------------------------------------------------------------------
                    | LARAVEL VALIDATION
                    |--------------------------------------------------------------------------
                    */


                    if (
                        xhr.status === 422 &&
                        xhr.responseJSON &&
                        xhr.responseJSON.errors
                    ) {


                        var html = '';


                        $.each(

                            xhr.responseJSON.errors,

                            function (
                                campo,
                                mensagens
                            ) {


                                html +=
                                    '<div>' +
                                    mensagens[0] +
                                    '</div>';

                            }

                        );


                        $('#error')
                            .html(html)
                            .show();


                        return;

                    }



                    /*
                    |--------------------------------------------------------------------------
                    | OUTRO ERRO
                    |--------------------------------------------------------------------------
                    */


                    var mensagem =
                        'Ocorreu um erro ao criar a conta.';


                    if (
                        xhr.responseJSON &&
                        xhr.responseJSON.message
                    ) {


                        mensagem =
                            xhr.responseJSON.message;

                    }


                    $('#error')
                        .html(mensagem)
                        .show();

                }


            });

        }


    });



    /*
    |--------------------------------------------------------------------------
    | RESTAURAR BOTÃO
    |--------------------------------------------------------------------------
    */


    function restaurarBotao() {


        $('#botao_salvar')
            .prop(
                'disabled',
                false
            );


        $('#icon_enviar')
            .removeClass(
                'fa-spinner fa-spin'
            )
            .addClass(
                'fa-arrow-right'
            );


        $('#botao_texto')
            .text(
                'Criar Conta'
            );

    }


});

</script>


</body>

</html>