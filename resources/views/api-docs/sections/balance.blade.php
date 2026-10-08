<section id="doc-balance" class="api-doc-section">

    <h4>Saldo</h4>

    <p>
        Consulta o saldo de SMS disponível para uma conta pré-paga.
    </p>

    <div class="api-endpoint">
        <span class="api-method api-method-get">GET</span>
        <span class="api-endpoint-path">/balance</span>
    </div>

    <h6>Response</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>{
    "status": 1,
    "message": "Saldo consultado com sucesso.",
    "data": {
        "billing_type": "PREPAID",
        "balance": 987,
        "unit": "SMS"
    }
}</code></pre>
    </div>

    <div class="alert alert-light-primary mt-3">
        O campo <code>balance</code> representa quantidade de
        SMS disponíveis e não um valor monetário.
    </div>

</section>