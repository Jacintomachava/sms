<section id="doc-send-many" class="api-doc-section">

    <h4>Enviar para vários números</h4>

    <p>
        Envia a mesma mensagem, utilizando o mesmo Sender,
        para vários destinatários.
    </p>

    <div class="api-endpoint">
        <span class="api-method api-method-post">POST</span>
        <span class="api-endpoint-path">/sms/send-many</span>
    </div>

    <h6>Request</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>{
    "sender": "EAGUAS",
    "message": "A sua factura encontra-se disponível.",
    "to": [
        "25884XXXXXXX",
        "25887XXXXXXX",
        "25886XXXXXXX"
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
                <td><code>sender</code></td>
                <td>string</td>
                <td>Sim</td>
                <td>Sender autorizado na conta.</td>
            </tr>

            <tr>
                <td><code>message</code></td>
                <td>string</td>
                <td>Sim</td>
                <td>Mensagem enviada para todos os destinatários.</td>
            </tr>

            <tr>
                <td><code>to</code></td>
                <td>array</td>
                <td>Sim</td>
                <td>
                    Lista de destinatários. Máximo de
                    1000 números por requisição.
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <h6 class="mt-4">Response</h6>

    <div class="api-code-wrapper">
        <pre class="api-code-block"><code>{
            "status": 1,
            "message": "Envio processado com sucesso.",
            "data": {
                "recipients": 3,
                "sent": 3,
                "failed": 0,
                "total_segments": 3,
                "balance": 989,
                "results": [
                    {
                        "id": 126,
                        "to": "25884XXXXXXX",
                        "segments": 1,
                        "sms_status": "SENT"
                    }
                ]
            }
        }</code>
    </pre>
    </div>

    <div class="alert alert-light-primary mt-3">
        O resultado de cada destinatário é devolvido individualmente.
        Assim, uma requisição pode conter envios bem-sucedidos e falhados.
    </div>

</section>