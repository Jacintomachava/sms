<section id="doc-otp-resend" class="mb-5">

    <h5>Reenviar OTP</h5>

    <p>
        Permite solicitar novamente um código OTP,
        respeitando o intervalo mínimo de reenvio
        configurado para a conta.
    </p>

    <div class="mb-3">
        <span class="badge bg-primary">POST</span>
        <code>/otp/resend</code>
    </div>

    <h6>Autenticação</h6>

    <pre><code>X-API-KEY: SUA_API_KEY
Content-Type: application/json</code></pre>

    <h6>Exemplo de requisição</h6>

    <pre><code>{
    "telefone": "841234567"
}</code></pre>

    <h6>Exemplo de resposta</h6>

    <pre><code>{
    "status": 1,
    "message": "Novo código OTP solicitado com sucesso."
}</code></pre>

</section>
