<section id="doc-otp-verify" class="mb-5">

    <h5>Validar OTP</h5>

    <p>
        Permite verificar se o código OTP introduzido
        corresponde ao código enviado para o telefone.
    </p>

    <div class="mb-3">
        <span class="badge bg-primary">POST</span>
        <code>/otp/verify</code>
    </div>

    <h6>Autenticação</h6>

    <pre><code>X-API-KEY: SUA_API_KEY
Content-Type: application/json</code></pre>

    <h6>Exemplo de requisição</h6>

    <pre><code>{
    "telefone": "841234567",
    "codigo": "123456"
}</code></pre>

    <h6>Exemplo de resposta</h6>

    <pre><code>{
    "status": 1,
    "message": "Código OTP validado com sucesso."
}</code></pre>

</section>
