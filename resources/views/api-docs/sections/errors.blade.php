<section id="doc-errors" class="api-doc-section">

    <h4>Erros</h4>

    <p>
        Quando uma requisição não pode ser processada,
        a API devolve <code>status: 0</code> e um código
        de erro que pode ser tratado pela aplicação cliente.
    </p>

    <h6 class="mt-4">Formato</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>{
    "status": 0,
    "message": "Mensagem descritiva do erro.",
    "error": {
        "code": "ERROR_CODE"
    }
}</code></pre>
    </div>

    <h6 class="mt-4">Códigos comuns</h6>

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
                <td>401</td>
                <td><code>API_KEY_MISSING</code></td>
                <td>API Key não informada.</td>
            </tr>

            <tr>
                <td>401</td>
                <td><code>API_KEY_INVALID</code></td>
                <td>API Key inválida ou revogada.</td>
            </tr>

            <tr>
                <td>403</td>
                <td><code>SENDER_NOT_ALLOWED</code></td>
                <td>Sender não autorizado para a conta.</td>
            </tr>

            <tr>
                <td>422</td>
                <td><code>VALIDATION_ERROR</code></td>
                <td>Dados da requisição inválidos.</td>
            </tr>

            <tr>
                <td>422</td>
                <td><code>INVALID_PHONE</code></td>
                <td>Número de telefone inválido.</td>
            </tr>

            <tr>
                <td>422</td>
                <td><code>INSUFFICIENT_CREDITS</code></td>
                <td>Saldo de SMS insuficiente.</td>
            </tr>

            <tr>
                <td>404</td>
                <td><code>SMS_NOT_FOUND</code></td>
                <td>SMS não encontrada para a conta autenticada.</td>
            </tr>

            <tr>
                <td>500</td>
                <td><code>INTERNAL_ERROR</code></td>
                <td>Erro interno durante o processamento.</td>
            </tr>

            </tbody>

        </table>

    </div>

</section>