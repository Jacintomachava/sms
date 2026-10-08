<section id="doc-bulk" class="api-doc-section">

    <h4>Envio em massa</h4>

    <p>
        Permite enviar mensagens diferentes para vários
        destinatários numa única requisição.
    </p>

    <div class="api-endpoint">
        <span class="api-method api-method-post">POST</span>
        <span class="api-endpoint-path">/sms/bulk</span>
    </div>

    <h6>Request</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>{
    "messages": [
        {
            "to": "25884XXXXXXX",
            "sender": "EAGUAS",
            "message": "Factura paga com sucesso."
        },
        {
            "to": "25887XXXXXXX",
            "sender": "INFORDATA",
            "message": "A sua factura encontra-se disponível."
        }
    ]
}</code></pre>
    </div>

    <h6 class="mt-4">Parâmetros</h6>

    <div class="table-responsive">
        <table class="table">

            <thead>
            <tr>
                <th>Campo</th>
                <th>Tipo</th>
                <th>Obrigatório</th>
                <th>Descrição</th>
            </tr>
            </thead>

            <tbody>
            <tr>
                <td><code>messages</code></td>
                <td>array</td>
                <td>Sim</td>
                <td>Lista de mensagens. Máximo de 1000 por requisição.</td>
            </tr>

            <tr>
                <td><code>messages[].to</code></td>
                <td>string</td>
                <td>Sim</td>
                <td>Número do destinatário.</td>
            </tr>

            <tr>
                <td><code>messages[].sender</code></td>
                <td>string</td>
                <td>Sim</td>
                <td>Sender autorizado na conta.</td>
            </tr>

            <tr>
                <td><code>messages[].message</code></td>
                <td>string</td>
                <td>Sim</td>
                <td>Conteúdo da mensagem.</td>
            </tr>
            </tbody>

        </table>
    </div>

    <h6 class="mt-4">Response</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>{
    "status": 1,
    "message": "Envio em massa processado com sucesso.",
    "data": {
        "total": 2,
        "sent": 2,
        "failed": 0,
        "total_segments": 2,
        "balance": 987,
        "results": [
            {
                "id": 127,
                "to": "25884XXXXXXX",
                "sender": "EAGUAS",
                "segments": 1,
                "sms_status": "SENT"
            },
            {
                "id": 128,
                "to": "25887XXXXXXX",
                "sender": "INFORDATA",
                "segments": 1,
                "sms_status": "SENT"
            }
        ]
    }
}</code></pre>
    </div>

</section>