<div class="card mb-4" id="purchase-create">
    <div class="card-header">
        <h5 class="mb-0">Criar Compra de SMS</h5>
    </div>

    <div class="card-body">
        <p>
            Permite ao cliente criar uma compra de SMS,
            utilizando o tarifário associado à sua conta.
        </p>

        <h6>Autenticação</h6>
        <p>
            <span class="badge bg-warning text-dark">
                Bearer Token
            </span>
        </p>

        <h6>Endpoint</h6>
        <pre class="bg-light p-3 rounded"><code>POST /api/sms/compras</code></pre>

        <h6>Headers</h6>
        <pre class="bg-light p-3 rounded"><code>Authorization: Bearer SEU_TOKEN
Accept: application/json
Content-Type: application/json
Idempotency-Key: UUID_UNICO</code></pre>

        <h6>Parâmetros</h6>
        <div class="table-responsive">
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
                        <td>Quantidade de SMS pretendida.</td>
                    </tr>
                    <tr>
                        <td>telefone</td>
                        <td>string</td>
                        <td>Sim</td>
                        <td>Número M-Pesa que realizará o pagamento.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h6>Exemplo de requisição</h6>
        <pre class="bg-light p-3 rounded"><code>{
    "quantidade_sms": 1000,
    "telefone": "841234567"
}</code></pre>

        <h6>Exemplo ilustrativo de resposta</h6>
        <pre class="bg-light p-3 rounded"><code>{
    "status": 1,
    "message": "Compra criada.",
    "data": {
        "compra_id": 15,
        "quantidade_sms": 1000,
        "estado": "PENDENTE"
    }
}</code></pre>

        <div class="alert alert-info mb-0">
            A criação de uma compra não significa que
            o pagamento foi confirmado. A confirmação
            deve ser feita pelo fluxo de pagamento.
        </div>
    </div>
</div>