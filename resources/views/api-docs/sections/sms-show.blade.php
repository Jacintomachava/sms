<section id="doc-sms-show" class="api-doc-section">

    <h4>Consultar SMS</h4>

    <p>
        Consulta os detalhes de uma SMS através do seu identificador.
    </p>

    <div class="api-endpoint">
        <span class="api-method api-method-get">GET</span>
        <span class="api-endpoint-path">/sms/{id}</span>
    </div>

    <h6>Exemplo</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>GET /sms/128</code></pre>
    </div>

    <h6 class="mt-4">Response</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>{
    "status": 1,
    "message": "SMS consultada com sucesso.",
    "data": {
        "id": 128,
        "to": "25884XXXXXXX",
        "sender": "EAGUAS",
        "message": "Factura paga com sucesso.",
        "encoding": "GSM7",
        "characters": 25,
        "segments": 1,
        "sms_status": "SENT",
        "sent_at": "2026-09-27T14:00:00Z",
        "delivered_at": null,
        "created_at": "2026-09-27T14:00:00Z"
    }
}</code></pre>
    </div>

</section>