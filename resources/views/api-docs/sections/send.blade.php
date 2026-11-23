<section id="doc-send" class="api-doc-section">

    <h4>Enviar SMS único</h4>

    <p>
        Permite enviar uma mensagem SMS para um único
        destinatário, utilizando um Sender autorizado
        na conta autenticada.
    </p>

    <div class="api-endpoint">
        <span class="api-method api-method-post">POST</span>
        <span class="api-endpoint-path">/sms/send</span>
    </div>

    <h6>Request</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>{
    "sender": "EAGUAS",
    "message": "A sua factura encontra-se disponível.",
    "to": "258841234567"
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
                    <td>
                        Sender autorizado na conta.
                        Máximo de 50 caracteres na requisição.
                    </td>
                </tr>

                <tr>
                    <td><code>message</code></td>
                    <td>string</td>
                    <td>Sim</td>
                    <td>
                        Conteúdo da mensagem SMS.
                        Máximo de 5000 caracteres na requisição.
                    </td>
                </tr>

                <tr>
                    <td><code>to</code></td>
                    <td>string</td>
                    <td>Sim</td>
                    <td>
                        Número de telefone do destinatário.
                        Máximo de 20 caracteres na requisição.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <h6 class="mt-4">Response — HTTP 202</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>{
    "status": 1,
    "message": "SMS registada e colocada na fila de envio.",
    "data": {
        "id": 126,
        "to": "258841234567",
        "sender": "EAGUAS",
        "encoding": "GSM-7",
        "characters": 43,
        "segments": 1,
        "sms_status": "PENDING",
        "created_at": "2026-11-23T14:30:00+02:00"
    }
}</code></pre>
    </div>

    <div class="alert alert-light-primary mt-3">
        <strong>Importante:</strong>
        O código HTTP 202 significa que a SMS foi
        aceite e colocada na fila de envio.
        Não significa que já foi entregue ao destinatário.
    </div>

    <h6 class="mt-4">Possíveis erros</h6>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>HTTP</th>
                    <th>Código</th>
                    <th>Descrição</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>403</td>
                    <td><code>ACCOUNT_NOT_FOUND</code></td>
                    <td>Conta não identificada.</td>
                </tr>

                <tr>
                    <td>403</td>
                    <td><code>SENDER_NOT_ALLOWED</code></td>
                    <td>Sender não autorizado para esta conta.</td>
                </tr>

                <tr>
                    <td>422</td>
                    <td><code>VALIDATION_ERROR</code></td>
                    <td>Dados inválidos ou campos obrigatórios em falta.</td>
                </tr>

                <tr>
                    <td>422</td>
                    <td><code>INSUFFICIENT_CREDITS</code></td>
                    <td>Saldo de SMS insuficiente.</td>
                </tr>

                <tr>
                    <td>422</td>
                    <td><code>INVALID_PHONE</code></td>
                    <td>Número de telefone inválido.</td>
                </tr>

                <tr>
                    <td>500</td>
                    <td><code>INTERNAL_ERROR</code></td>
                    <td>Erro interno ao registar a SMS.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h6 class="mt-4">Exemplo de erro</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>{
    "status": 0,
    "message": "Saldo SMS insuficiente.",
    "error": {
        "code": "INSUFFICIENT_CREDITS"
    }
}</code></pre>
    </div>

</section>