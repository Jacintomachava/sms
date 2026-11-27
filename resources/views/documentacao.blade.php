
<!DOCTYPE html>
<html lang="pt">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Documentação oficial da API InfoSMS. Integre SMS, OTP e serviços de comunicação nas suas aplicações.">

    <title>Documentação de APIs | InfoSMS</title>

    @include('includes.cssMaster')

    <!-- CSS da Landing Page -->
    <link rel="stylesheet"
          href="{{ asset('assets/inforsms/site.css') }}">

    <!-- CSS da Documentação -->
    <link rel="stylesheet"
          href="{{ asset('assets/inforsms/documentacao.css') }}">

    @stack('css')

</head>

<body class="api-public-page">

    @include('includes.menuMaster')

    <!-- Banner da documentação -->
    <section class="api-public-banner">

        <div class="api-container">

            <div class="api-breadcrumb">
                <a href="{{ url('/') }}">Início</a>
                <span>/</span>
                <span>Developers</span>
            </div>

            <span class="api-version">
                &lt;/&gt; INFOSMS DEVELOPERS · API V1
            </span>

            <h1>Documentação de <span>APIs</span></h1>

            <p>
                Integre SMS, autenticação OTP e compra de
                créditos directamente nas suas aplicações.
            </p>

            <div class="api-banner-actions">

                <a href="#api-documentation"
                   class="api-start-button">
                    Começar integração →
                </a>

                <a href="{{ Route::has('register') ? route('register') : url('/register') }}"
                   class="api-account-button">
                    Criar conta grátis
                </a>

            </div>

        </div>

    </section>

    <!-- Conteúdo da documentação -->
    <main class="api-public-content"
          id="api-documentation">

        <div class="api-container">

            @include('api-docs.content')

        </div>

    </main>

    @include('includes.footerMaster')

    @include('includes.jsMaster')

    <script src="{{ asset('assets/inforsms/site.js') }}"></script>

    <script src="{{ asset('assets/inforsms/documentacao.js') }}"></script>

    @stack('js')

</body>

</html>
