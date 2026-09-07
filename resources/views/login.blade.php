<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>InfoSMS - Plataforma de SMS para Empresas e Programadores</title>

    <meta name="description"
          content="Envie SMS em massa, integre sistemas através da nossa API REST e acompanhe as suas mensagens numa única plataforma.">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;
            --secondary: #7c3aed;
            --dark: #0f172a;
            --text: #475569;
            --light: #f8fafc;
            --border: #e2e8f0;
            --success: #16a34a;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--dark);
            background: #fff;
        }

        section {
            scroll-margin-top: 90px;
        }

        /* Navbar */

        .navbar {
            background: rgba(255, 255, 255, .95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, .8);
        }

        .navbar-brand {
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .nav-link {
            font-weight: 500;
            color: #334155;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        /* Buttons */

        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: .7rem 1.2rem;
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        /* Hero */

        .hero {
            padding: 155px 0 100px;
            overflow: hidden;
            position: relative;
            background:
                radial-gradient(circle at 85% 20%, rgba(124, 58, 237, .13), transparent 28%),
                radial-gradient(circle at 15% 30%, rgba(37, 99, 235, .12), transparent 30%),
                linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 13px;
            border-radius: 100px;
            background: var(--primary-light);
            color: var(--primary);
            font-size: .88rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero h1 {
            font-size: clamp(2.7rem, 6vw, 4.6rem);
            line-height: 1.03;
            letter-spacing: -3px;
            font-weight: 800;
            max-width: 750px;
        }

        .gradient-text {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-description {
            max-width: 650px;
            color: var(--text);
            font-size: 1.18rem;
            line-height: 1.8;
            margin: 25px 0 30px;
        }

        .hero-checks {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-top: 30px;
            color: #475569;
            font-size: .94rem;
        }

        .hero-checks i {
            color: var(--success);
        }

        /* API Preview */

        .api-window {
            background: #0b1120;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(15, 23, 42, .22);
            transform: rotate(1deg);
        }

        .api-window-header {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 15px 18px;
            background: #111827;
            border-bottom: 1px solid #1e293b;
        }

        .window-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #475569;
        }

        .api-window pre {
            color: #dbeafe;
            padding: 25px;
            margin: 0;
            font-size: .85rem;
            line-height: 1.8;
            overflow: auto;
        }

        .api-status {
            padding: 17px 22px;
            border-top: 1px solid #1e293b;
            color: #86efac;
            font-size: .9rem;
        }

        /* General */

        .section-padding {
            padding: 100px 0;
        }

        .section-label {
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: .78rem;
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            letter-spacing: -1.5px;
            margin-top: 10px;
        }

        .section-description {
            color: var(--text);
            font-size: 1.05rem;
            max-width: 650px;
        }

        /* Feature cards */

        .feature-card {
            height: 100%;
            padding: 30px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: white;
            transition: .25s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(15, 23, 42, .08);
            border-color: #bfdbfe;
        }

        .feature-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 1.35rem;
            margin-bottom: 22px;
        }

        .feature-card h5 {
            font-weight: 700;
        }

        .feature-card p {
            color: var(--text);
            margin-bottom: 0;
            line-height: 1.7;
        }

        /* Audience */

        .audience-card {
            padding: 40px;
            border-radius: 22px;
            border: 1px solid var(--border);
            height: 100%;
        }

        .audience-card.developer {
            background: #0f172a;
            color: white;
            border: none;
        }

        .audience-card ul {
            padding: 0;
            margin: 25px 0;
            list-style: none;
        }

        .audience-card li {
            margin: 13px 0;
        }

        .audience-card li i {
            color: #22c55e;
            margin-right: 8px;
        }

        /* Sender */

        .sender-section {
            background: var(--light);
        }

        .sender-box {
            background: white;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 35px;
            height: 100%;
        }

        .sender-name {
            display: inline-block;
            padding: 8px 15px;
            background: #eef2ff;
            color: #4338ca;
            border-radius: 9px;
            font-weight: 700;
            margin: 10px 0 20px;
        }

        /* API section */

        .api-section {
            background: #0b1120;
            color: white;
        }

        .code-box {
            background: #111827;
            border: 1px solid #1e293b;
            border-radius: 16px;
            overflow: hidden;
        }

        .code-tabs {
            padding: 12px 15px 0;
            border-bottom: 1px solid #1e293b;
        }

        .code-tab {
            border: 0;
            background: transparent;
            color: #94a3b8;
            padding: 10px 13px;
            font-size: .85rem;
        }

        .code-tab.active {
            color: white;
            border-bottom: 2px solid #60a5fa;
        }

        .code-box pre {
            margin: 0;
            padding: 25px;
            color: #dbeafe;
            font-size: .85rem;
            min-height: 260px;
            overflow: auto;
        }

        /* Pricing */

        .pricing-card {
            height: 100%;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 30px;
            background: white;
            position: relative;
        }

        .pricing-card.featured {
            border: 2px solid var(--primary);
            box-shadow: 0 20px 50px rgba(37, 99, 235, .12);
        }

        .popular-badge {
            position: absolute;
            top: -13px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 5px 12px;
            border-radius: 100px;
            font-size: .75rem;
            font-weight: 700;
        }

        .price {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .price small {
            font-size: .85rem;
            color: #64748b;
            font-weight: 500;
        }

        /* Steps */

        .step-number {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-bottom: 18px;
        }

        /* CTA */

        .cta-box {
            padding: 65px 30px;
            border-radius: 28px;
            color: white;
            background:
                radial-gradient(circle at 90% 20%, rgba(255,255,255,.18), transparent 25%),
                linear-gradient(135deg, #2563eb, #6d28d9);
        }

        /* Footer */

        footer {
            background: #080d18;
            color: #94a3b8;
            padding: 70px 0 25px;
        }

        footer h6 {
            color: white;
            font-weight: 700;
            margin-bottom: 18px;
        }

        footer a {
            display: block;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 10px;
        }

        footer a:hover {
            color: white;
        }

        @media (max-width: 991px) {
            .hero {
                padding-top: 125px;
            }

            .api-window {
                margin-top: 55px;
                transform: none;
            }

            .hero h1 {
                letter-spacing: -2px;
            }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container py-2">

        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <span class="brand-icon">
                <i class="bi bi-chat-dots-fill"></i>
            </span>
            <span>Info<span class="text-primary">SMS</span></span>
        </a>

        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">

            <ul class="navbar-nav mx-auto gap-lg-3">
                <li class="nav-item">
                    <a class="nav-link" href="#recursos">Recursos</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#api">API</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#sender">Sender ID</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#precos">Preços</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">Documentação</a>
                </li>
            </ul>

            <div class="d-flex gap-2 mt-3 mt-lg-0">
                <a href="{{ route('login') }}"
                   class="btn btn-outline-secondary">
                    Entrar
                </a>

                <a href="{{ route('register') }}"
                   class="btn btn-primary">
                    Criar Conta
                </a>
            </div>

        </div>
    </div>
</nav>


<!-- HERO -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center">

            <div class="col-lg-7">

                <div class="hero-badge">
                    <i class="bi bi-lightning-charge-fill"></i>
                    SMS para empresas e programadores
                </div>

                <h1>
                    Envie SMS.
                    <span class="gradient-text">Integre sistemas.</span>
                    Comunique melhor.
                </h1>

                <p class="hero-description">
                    Uma plataforma simples para enviar notificações,
                    campanhas e OTP. Utilize o portal ou integre o seu
                    sistema através da nossa API REST.
                </p>

                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('register') }}"
                       class="btn btn-primary btn-lg">
                        Começar agora
                        <i class="bi bi-arrow-right ms-1"></i>
                    </a>

                    <a href="#api"
                       class="btn btn-outline-dark btn-lg">
                        <i class="bi bi-code-slash me-1"></i>
                        Explorar API
                    </a>
                </div>

                <div class="hero-checks">
                    <span>
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Sandbox
                    </span>

                    <span>
                        <i class="bi bi-check-circle-fill me-1"></i>
                        API REST
                    </span>

                    <span>
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Bulk SMS
                    </span>

                    <span>
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Sender ID
                    </span>
                </div>

            </div>

            <div class="col-lg-5">

                <div class="api-window">

                    <div class="api-window-header">
                        <span class="window-dot"></span>
                        <span class="window-dot"></span>
                        <span class="window-dot"></span>

                        <span class="text-secondary ms-2 small">
                            POST /v1/sms/send
                        </span>
                    </div>

                    <pre><code>{
  "phone": "258841234567",
  "message": "Olá! A sua inscrição foi confirmada.",
  "sender_id": "InfoSMS"
}</code></pre>

                    <div class="api-status">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        202 Accepted · QUEUED
                    </div>

                </div>

            </div>

        </div>
    </div>
</section>


<!-- RECURSOS -->
<section id="recursos" class="section-padding">
    <div class="container">

        <div class="text-center mb-5">
            <span class="section-label">Plataforma completa</span>

            <h2 class="section-title">
                Tudo o que precisa para comunicar por SMS
            </h2>

            <p class="section-description mx-auto">
                Desde uma simples mensagem até integrações de sistemas
                empresariais e grandes campanhas.
            </p>
        </div>

        <div class="row g-4">

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-send"></i>
                    </div>

                    <h5>Envio de SMS</h5>

                    <p>
                        Envie mensagens individuais directamente pelo portal
                        de forma simples e rápida.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-people"></i>
                    </div>

                    <h5>Bulk SMS</h5>

                    <p>
                        Importe contactos através de Excel ou CSV e envie
                        campanhas para milhares de destinatários.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-code-square"></i>
                    </div>

                    <h5>API REST</h5>

                    <p>
                        Integre websites, aplicações mobile, ERPs,
                        sistemas escolares e outras plataformas.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>

                    <h5>Sandbox</h5>

                    <p>
                        Desenvolva e teste a integração antes de utilizar
                        saldo ou enviar mensagens reais.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-bar-chart"></i>
                    </div>

                    <h5>Relatórios</h5>

                    <p>
                        Consulte mensagens enviadas, estados de entrega,
                        consumo e utilização da sua conta.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-clock"></i>
                    </div>

                    <h5>Agendamento</h5>

                    <p>
                        Prepare campanhas antecipadamente e escolha quando
                        pretende iniciar os seus envios.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>


<!-- EMPRESAS / PROGRAMADORES -->
<section class="section-padding bg-light">
    <div class="container">

        <div class="text-center mb-5">
            <span class="section-label">Flexível</span>
            <h2 class="section-title">Uma plataforma. Duas formas de utilizar.</h2>
        </div>

        <div class="row g-4">

            <div class="col-lg-6">
                <div class="audience-card bg-white">

                    <div class="feature-icon">
                        <i class="bi bi-building"></i>
                    </div>

                    <h3 class="fw-bold">Para Empresas</h3>

                    <p class="text-secondary">
                        Não precisa de conhecimentos de programação.
                        Controle toda a comunicação através do portal.
                    </p>

                    <ul>
                        <li><i class="bi bi-check-circle-fill"></i> Bulk SMS</li>
                        <li><i class="bi bi-check-circle-fill"></i> Importação Excel e CSV</li>
                        <li><i class="bi bi-check-circle-fill"></i> Gestão de contactos</li>
                        <li><i class="bi bi-check-circle-fill"></i> Campanhas</li>
                        <li><i class="bi bi-check-circle-fill"></i> Agendamento</li>
                        <li><i class="bi bi-check-circle-fill"></i> Relatórios</li>
                    </ul>

                    <a href="{{ route('register') }}"
                       class="btn btn-outline-primary">
                        Criar conta empresarial
                    </a>

                </div>
            </div>

            <div class="col-lg-6">
                <div class="audience-card developer">

                    <div class="feature-icon">
                        <i class="bi bi-terminal"></i>
                    </div>

                    <h3 class="fw-bold">Para Programadores</h3>

                    <p class="text-secondary">
                        Uma API simples para adicionar SMS às suas aplicações.
                    </p>

                    <ul>
                        <li><i class="bi bi-check-circle-fill"></i> API REST</li>
                        <li><i class="bi bi-check-circle-fill"></i> Sandbox</li>
                        <li><i class="bi bi-check-circle-fill"></i> API Keys</li>
                        <li><i class="bi bi-check-circle-fill"></i> Webhooks</li>
                        <li><i class="bi bi-check-circle-fill"></i> OTP</li>
                        <li><i class="bi bi-check-circle-fill"></i> Histórico e estados</li>
                    </ul>

                    <a href="#api"
                       class="btn btn-light">
                        Explorar API
                    </a>

                </div>
            </div>

        </div>
    </div>
</section>


<!-- SENDER -->
<section id="sender" class="section-padding sender-section">
    <div class="container">

        <div class="row align-items-center mb-5">
            <div class="col-lg-7">
                <span class="section-label">Sender ID</span>

                <h2 class="section-title">
                    Comece com InfoSMS. Personalize quando quiser.
                </h2>

                <p class="section-description">
                    Comece a utilizar a plataforma com o nosso Sender
                    partilhado ou solicite um Sender exclusivo para a sua organização.
                </p>
            </div>
        </div>

        <div class="row g-4">

            <div class="col-lg-6">
                <div class="sender-box">

                    <span class="badge text-bg-success mb-3">
                        Disponível
                    </span>

                    <h4 class="fw-bold">Sender partilhado</h4>

                    <div class="sender-name">
                        InfoSMS
                    </div>

                    <p class="text-secondary">
                        Disponível para contas elegíveis, permitindo começar
                        a enviar sem solicitar imediatamente um Sender próprio.
                    </p>

                    <div class="mt-4">
                        <div class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Configuração simples
                        </div>

                        <div>
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Gerido pela plataforma
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-lg-6">
                <div class="sender-box">

                    <span class="badge text-bg-primary mb-3">
                        Personalizado
                    </span>

                    <h4 class="fw-bold">Sender próprio</h4>

                    <div class="sender-name">
                        SUA MARCA
                    </div>

                    <p class="text-secondary">
                        Solicite um Sender ID exclusivo para que os clientes
                        reconheçam a sua organização nas mensagens recebidas.
                    </p>

                    <div class="mt-4">
                        <div class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Identidade da sua organização
                        </div>

                        <div>
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Sujeito a aprovação
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>


<!-- API -->
<section id="api" class="section-padding api-section">
    <div class="container">

        <div class="row align-items-center g-5">

            <div class="col-lg-5">

                <span class="section-label text-info">
                    Para programadores
                </span>

                <h2 class="section-title">
                    Integre SMS em minutos.
                </h2>

                <p class="text-secondary fs-5">
                    Uma API REST simples, com Sandbox e o mesmo formato
                    de integração para testes e produção.
                </p>

                <div class="mt-4">

                    <div class="d-flex gap-3 mb-3">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Autenticação através de API Key</span>
                    </div>

                    <div class="d-flex gap-3 mb-3">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Ambiente Sandbox</span>
                    </div>

                    <div class="d-flex gap-3 mb-3">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Estados de entrega normalizados</span>
                    </div>

                    <div class="d-flex gap-3">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Webhooks</span>
                    </div>

                </div>

            </div>

            <div class="col-lg-7">

                <div class="code-box">

                    <div class="code-tabs">
                        <button class="code-tab active" data-language="curl">
                            cURL
                        </button>

                        <button class="code-tab" data-language="php">
                            PHP
                        </button>

                        <button class="code-tab" data-language="laravel">
                            Laravel
                        </button>

                        <button class="code-tab" data-language="java">
                            Java
                        </button>

                        <button class="code-tab" data-language="javascript">
                            JavaScript
                        </button>

                        <button class="code-tab" data-language="python">
                            Python
                        </button>
                    </div>

                    <pre><code id="apiCode"></code></pre>

                </div>

            </div>

        </div>

    </div>
</section>


<!-- COMO FUNCIONA -->
<section class="section-padding">
    <div class="container">

        <div class="text-center mb-5">
            <span class="section-label">Quick Start</span>
            <h2 class="section-title">Do registo ao primeiro SMS</h2>
        </div>

        <div class="row g-4">

            <div class="col-md">
                <div class="step-number">1</div>
                <h5 class="fw-bold">Crie a conta</h5>
                <p class="text-secondary">
                    Registe-se gratuitamente na plataforma.
                </p>
            </div>

            <div class="col-md">
                <div class="step-number">2</div>
                <h5 class="fw-bold">Teste</h5>
                <p class="text-secondary">
                    Utilize a API Key Sandbox para desenvolver.
                </p>
            </div>

            <div class="col-md">
                <div class="step-number">3</div>
                <h5 class="fw-bold">Adquira SMS</h5>
                <p class="text-secondary">
                    Escolha a quantidade adequada à sua utilização.
                </p>
            </div>

            <div class="col-md">
                <div class="step-number">4</div>
                <h5 class="fw-bold">Produção</h5>
                <p class="text-secondary">
                    Utilize a chave de produção ou o portal.
                </p>
            </div>

            <div class="col-md">
                <div class="step-number">5</div>
                <h5 class="fw-bold">Acompanhe</h5>
                <p class="text-secondary">
                    Consulte estados e relatórios de envio.
                </p>
            </div>

        </div>

    </div>
</section>


<!-- PREÇOS -->
<section id="precos" class="section-padding bg-light">
    <div class="container">

        <div class="text-center mb-5">
            <span class="section-label">Preços</span>

            <h2 class="section-title">
                Pague de acordo com o seu volume
            </h2>

            <p class="section-description mx-auto">
                Quanto maior o volume adquirido, melhor o preço por SMS.
            </p>
        </div>

        <div class="row g-4 justify-content-center">

            <div class="col-md-6 col-lg-3">
                <div class="pricing-card">

                    <h5 class="fw-bold">Essencial</h5>

                    <p class="text-secondary">
                        1 – 9.999 SMS
                    </p>

                    <div class="price">
                        1,27
                        <small>MT / SMS</small>
                    </div>

                    <hr>

                    <p class="text-secondary">
                        Ideal para pequenas empresas e primeiros projectos.
                    </p>

                    <a href="{{ route('register') }}"
                       class="btn btn-outline-primary w-100">
                        Começar
                    </a>

                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="pricing-card">

                    <h5 class="fw-bold">Profissional</h5>

                    <p class="text-secondary">
                        10.000 – 99.999 SMS
                    </p>

                    <div class="price">
                        1,10
                        <small>MT / SMS</small>
                    </div>

                    <hr>

                    <p class="text-secondary">
                        Para sistemas e empresas com utilização frequente.
                    </p>

                    <a href="{{ route('register') }}"
                       class="btn btn-outline-primary w-100">
                        Começar
                    </a>

                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="pricing-card featured">

                    <span class="popular-badge">
                        Mais competitivo
                    </span>

                    <h5 class="fw-bold">Empresarial</h5>

                    <p class="text-secondary">
                        100.000 – 299.999 SMS
                    </p>

                    <div class="price">
                        0,95
                        <small>MT / SMS</small>
                    </div>

                    <hr>

                    <p class="text-secondary">
                        Para organizações com elevado volume de comunicação.
                    </p>

                    <a href="{{ route('register') }}"
                       class="btn btn-primary w-100">
                        Começar
                    </a>

                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="pricing-card">

                    <h5 class="fw-bold">Exclusivo</h5>

                    <p class="text-secondary">
                        300.000+ SMS
                    </p>

                    <div class="price fs-3">
                        Sob consulta
                    </div>

                    <hr>

                    <p class="text-secondary">
                        Condições personalizadas para grandes organizações.
                    </p>

                    <a href="#contacto"
                       class="btn btn-outline-primary w-100">
                        Falar connosco
                    </a>

                </div>
            </div>

        </div>

    </div>
</section>


<!-- CTA -->
<section id="contacto" class="section-padding">
    <div class="container">

        <div class="cta-box text-center">

            <h2 class="fw-bold display-6">
                Pronto para enviar o seu primeiro SMS?
            </h2>

            <p class="fs-5 mt-3 mb-4 opacity-75">
                Crie a sua conta, explore o Sandbox e comece a integrar.
            </p>

            <div class="d-flex justify-content-center flex-wrap gap-3">

                <a href="{{ route('register') }}"
                   class="btn btn-light btn-lg">
                    Criar Conta
                </a>

                <a href="#api"
                   class="btn btn-outline-light btn-lg">
                    Ver API
                </a>

            </div>

        </div>

    </div>
</section>


<!-- FOOTER -->
<footer>
    <div class="container">

        <div class="row g-4">

            <div class="col-lg-5">

                <div class="d-flex align-items-center gap-2 text-white fs-4 fw-bold mb-3">
                    <span class="brand-icon">
                        <i class="bi bi-chat-dots-fill"></i>
                    </span>

                    InfoSMS
                </div>

                <p style="max-width: 380px;">
                    Plataforma de comunicação SMS para empresas,
                    instituições e programadores.
                </p>

            </div>

            <div class="col-6 col-lg-2">
                <h6>Produto</h6>
                <a href="#recursos">Bulk SMS</a>
                <a href="#api">API SMS</a>
                <a href="#sender">Sender ID</a>
                <a href="#recursos">Relatórios</a>
            </div>

            <div class="col-6 col-lg-2">
                <h6>Programadores</h6>
                <a href="#">Documentação</a>
                <a href="#api">Quick Start</a>
                <a href="#">Webhooks</a>
                <a href="#">Status API</a>
            </div>

            <div class="col-6 col-lg-3">
                <h6>Empresa</h6>
                <a href="#">Sobre nós</a>
                <a href="#">Contactos</a>
                <a href="#">Termos de utilização</a>
                <a href="#">Política de privacidade</a>
            </div>

        </div>

        <hr class="border-secondary mt-5">

        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 small">

            <span>
                © {{ date('Y') }} InfoSMS. Todos os direitos reservados.
            </span>

            <span>
                Moçambique
            </span>

        </div>

    </div>
</footer>


<script>
    const examples = {};

    examples.curl = [
        'curl -X POST https://api.plataforma.co.mz/v1/sms/send \\',
        '  -H "X-API-Key: sms_test_xxxxxxxxx" \\',
        '  -H "Content-Type: application/json" \\',
        "  -d '{",
        '    "phone": "258841234567",',
        '    "message": "Olá! A sua inscrição foi confirmada."',
        "  }'"
    ].join('\n');


    examples.php = [
        '$' + "data = [",
        "    'phone' => '258841234567',",
        "    'message' => 'Olá! A sua inscrição foi confirmada.'",
        "];",
        "",
        '$' + "ch = curl_init(",
        "    'https://api.plataforma.co.mz/v1/sms/send'",
        ");",
        "",
        "curl_setopt_array($" + "ch, [",
        "    CURLOPT_POST => true,",
        "    CURLOPT_RETURNTRANSFER => true,",
        "    CURLOPT_HTTPHEADER => [",
        "        'X-API-Key: sms_test_xxxxxxxxx',",
        "        'Content-Type: application/json'",
        "    ],",
        "    CURLOPT_POSTFIELDS => json_encode($" + "data)",
        "]);",
        "",
        '$' + "response = curl_exec($" + "ch);"
    ].join('\n');


    examples.laravel = [
        "use Illuminate\\Support\\Facades\\Http;",
        "",
        '$' + "response = Http::withHeaders([",
        "    'X-API-Key' => 'sms_test_xxxxxxxxx'",
        "])->post(",
        "    'https://api.plataforma.co.mz/v1/sms/send',",
        "    [",
        "        'phone' => '258841234567',",
        "        'message' => 'Olá! A sua inscrição foi confirmada.'",
        "    ]",
        ");"
    ].join('\n');


    examples.java = [
        "HttpRequest request = HttpRequest.newBuilder()",
        "    .uri(URI.create(",
        '        "https://api.plataforma.co.mz/v1/sms/send"',
        "    ))",
        '    .header("X-API-Key", "sms_test_xxxxxxxxx")',
        '    .header("Content-Type", "application/json")',
        "    .POST(HttpRequest.BodyPublishers.ofString(",
        '        "{\\"phone\\":\\"258841234567\\",\\"message\\":\\"Olá! A sua inscrição foi confirmada.\\"}"',
        "    ))",
        "    .build();"
    ].join('\n');


    examples.javascript = [
        "const response = await fetch(",
        '    "https://api.plataforma.co.mz/v1/sms/send",',
        "    {",
        '        method: "POST",',
        "        headers: {",
        '            "X-API-Key": "sms_test_xxxxxxxxx",',
        '            "Content-Type": "application/json"',
        "        },",
        "        body: JSON.stringify({",
        '            phone: "258841234567",',
        '            message: "Olá! A sua inscrição foi confirmada."',
        "        })",
        "    }",
        ");"
    ].join('\n');


    examples.python = [
        "import requests",
        "",
        "response = requests.post(",
        '    "https://api.plataforma.co.mz/v1/sms/send",',
        "    headers={",
        '        "X-API-Key": "sms_test_xxxxxxxxx"',
        "    },",
        "    json={",
        '        "phone": "258841234567",',
        '        "message": "Olá! A sua inscrição foi confirmada."',
        "    }",
        ")",
        "",
        "print(response.json())"
    ].join('\n');


    const apiCode = document.getElementById('apiCode');

    apiCode.textContent = examples.curl;


    document.querySelectorAll('.code-tab').forEach(function (button) {

        button.addEventListener('click', function () {

            document.querySelectorAll('.code-tab').forEach(function (tab) {
                tab.classList.remove('active');
            });

            this.classList.add('active');

            apiCode.textContent = examples[this.dataset.language];
        });

    });
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>