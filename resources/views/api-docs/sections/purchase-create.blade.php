
<section id="doc-purchase-create" class="api-doc-section">

    <h4>Comprar créditos SMS</h4>

    <p>
        Permite comprar créditos SMS directamente através
        da API InfoSMS, utilizando pagamento M-Pesa.
    </p>

    <p>
        A conta é identificada automaticamente pela API Key.
        O preço é calculado pelo servidor com base na tarifa
        associada à conta.
    </p>

    <div class="mb-3">
        <span class="badge bg-success">POST</span>
        <code>/api/v1/sms/purchases</code>
    </div>

    <h6>Autenticação</h6>

    <pre><code>X-API-KEY: SUA_API_KEY
Content-Type: application/json
Accept: application/json</code></pre>

    <h6>Parâmetros</h6>

    <table class="table table-bordered">
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
                <td>quantidade_sms</td>
                <td>integer</td>
                <td>Sim</td>
                <td>Quantidade de SMS a comprar.</td>
            </tr>
            <tr>
                <td>telefone</td>
                <td>string</td>
                <td>Sim</td>
                <td>Número M-Pesa com prefixo 84 ou 85.</td>
            </tr>
        </tbody>
    </table>

    <h6>Exemplo de requisição</h6>

    <pre><code>{
    "quantidade_sms": 1000,
    "telefone": "841234567"
}</code></pre>

    <h6>Resposta de sucesso — HTTP 201</h6>

    <pre><code>{
    "status": 1,
    "message": "Pagamento efectuado com sucesso.",
    "data": {
        "compra_id": 125,
        "pagamento_id": 89,
        "saldo_sms": 2500
    }
}</code></pre>

    <h6>Resposta de falha — HTTP 422</h6>

    <pre><code>{
    "status": 0,
    "message": "Não foi possível efectuar o pagamento.",
    "data": {
        "compra_id": 125,
        "transacao_id": 321,
        "pode_tentar_novamente": true
    }
}</code></pre>

    <p class="text-muted">
        Em caso de falha, a compra permanece pendente
        e pode ser paga novamente através do endpoint
        de repetição de pagamento.
    </p>

</section>
