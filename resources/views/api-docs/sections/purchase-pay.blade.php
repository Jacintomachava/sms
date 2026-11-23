<div class="card mb-4" id="purchase-pay">
    <div class="card-header">
        <h5 class="mb-0">Pagamento de Compra de SMS</h5>
    </div>

    <div class="card-body">
        <p>
            Permite solicitar o pagamento de uma compra
            de SMS através do M-Pesa.
        </p>

        <h6>Autenticação</h6>
        <span class="badge bg-warning text-dark mb-3">
            Bearer Token
        </span>

        <h6>Endpoint</h6>
        <pre class="bg-light p-3 rounded"><code>POST /api/sms/compras/{id}/pagar</code></pre>

        <h6>Headers</h6>
        <pre class="bg-light p-3 rounded"><code>Authorization: Bearer SEU_TOKEN
Accept: application/json
Content-Type: application/json</code></pre>

        <h6>Parâmetros</h6>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Campo</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>id</td>
                        <td>integer</td>
                        <td>Identificador da compra.</td>
                    </tr>
                    <tr>
                        <td>telefone</td>
                        <td>string</td>
                        <td>Telefone associado ao M-Pesa.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h6>Exemplo de requisição</h6>
        <pre class="bg-light p-3 rounded"><code>{
    "telefone": "841234567"
}</code></pre>

        <h6>Exemplo ilustrativo de pagamento confirmado</h6>
        <pre class="bg-light p-3 rounded"><code>{
    "status": 1,
    "message": "Pagamento confirmado.",
    "data": {
        "compra_id": 15,
        "estado": "PAGA",
        "sms_creditados": 1000
    }
}</code></pre>

        <h6>Possíveis situações</h6>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Situação</th>
                        <th>Significado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PAGA</td>
                        <td>Pagamento confirmado e compra concluída.</td>
                    </tr>
                    <tr>
                        <td>PENDENTE</td>
                        <td>Compra ainda sem pagamento confirmado.</td>
                    </tr>
                    <tr>
                        <td>INCERTA</td>
                        <td>Resultado do pagamento ainda não confirmado.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="alert alert-warning mb-0">
            <strong>Atenção:</strong>
            se o resultado do pagamento for incerto,
            não repita automaticamente a cobrança.
            Consulte primeiro o estado da transacção.
        </div>
    </div>
</div>