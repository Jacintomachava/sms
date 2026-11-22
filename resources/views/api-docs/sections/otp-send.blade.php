<section id="doc-otp-send" class="mb-5">

    <h5>Enviar OTP</h5>

    <p>
        Permite solicitar o envio de um código OTP por SMS
        para verificar um número de telefone.
    </p>

    <p>
        O código é gerado de acordo com a configuração OTP
        da conta, incluindo validade, tamanho do código
        e limite de tentativas.
    </p>

    <div class="mb-3">
        <span class="badge bg-primary">POST</span>
        <code>/otp/send</code>
    </div>

    <h6>Autenticação</h6>

    <pre><code>X-API-KEY: SUA_API_KEY
Content-Type: application/json
Accept: application/json</code></pre>

    <h6>Exemplo de requisição</h6>

    <pre><code>{
    "telefone": "841234567"
}</code></pre>

    <h6>Exemplo de resposta</h6>

    <pre><code>{
    "status": 1,
    "message": "Código OTP solicitado com sucesso."
}</code></pre>

</section>
