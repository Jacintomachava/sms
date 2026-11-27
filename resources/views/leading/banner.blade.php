@php $registerUrl = \Illuminate\Support\Facades\Route::has('register') ? route('register') : url('/register'); @endphp
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-content">
            <span class="eyebrow">⚡ Plataforma de SMS Empresarial</span>
            <h1>Envie SMS em massa de forma <span>simples, rápida e segura.</span></h1>
            <p>Solução completa para comunicar com os seus clientes, alunos, utentes e colaboradores, numa única plataforma.</p>
            <div class="hero-buttons">
                <a href="{{ $registerUrl }}" class="btn btn-primary">Criar Conta Grátis <span aria-hidden="true">→</span></a>
                <a href="#como-funciona" class="btn btn-outline">Conhecer a Plataforma</a>
            </div>
            <div class="trust-points">
                <span>⚡ Envio rápido</span><span>🛡️ Gestão segura</span><span>▥ Relatórios detalhados</span>
            </div>
            <div class="offer"><span>Comece a enviar SMS com apenas</span> <strong>200 MT</strong></div>
        </div>
        <div class="hero-visual" aria-label="Exemplo ilustrativo do painel de SMS">
            <div class="glow-circle"></div>
            <div class="dashboard-demo">
                <div class="demo-top"><strong>◉ InfoSMS</strong><span>☰ &nbsp; ◉</span></div>
                <div class="demo-layout">
                    <aside class="demo-sidebar"><span>▦ Painel</span><span>✉ Campanhas</span><span>♙ Contactos</span><span>▥ Relatórios</span></aside>
                    <div class="demo-main">
                        <strong>Painel</strong>
                        <div class="demo-stats"><div><small>Saldo</small><b>125.000</b><small>SMS</small></div><div><small>Enviadas</small><b>342.560</b><small>Mensagens</small></div><div><small>Entregues</small><b>98,7%</b><small>Taxa ilustrativa</small></div></div>
                        <p>Envios dos últimos 30 dias</p>
                        <svg class="demo-chart" viewBox="0 0 460 150" role="img" aria-label="Gráfico ilustrativo de crescimento de envios"><path d="M0 145H460M0 100H460M0 55H460M0 10H460" stroke="#e6edfa" stroke-width="1"/><polyline points="0,125 46,112 92,86 138,100 184,65 230,78 276,42 322,58 368,22 414,40 460,8" fill="none" stroke="#0764e8" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                </div>
            </div>
            <div class="phone-demo"><div class="phone-notch"></div><div class="phone-header"><span>‹</span><img src="{{ asset('assets/inforsms/logo.png') }}" alt=""><strong>InfoSMS</strong></div><div class="phone-body"><div class="phone-message"><p>A sua mensagem chegou ao destino com sucesso! <span class="message-check">✓</span></p></div><small class="phone-time">10:24</small><div class="phone-compose">Mensagem <span>➤</span></div></div></div>
        </div>
    </div>
</section>
