<div class="card mb-4" id="purchase-show">
    <div class="card-header">
        <h5 class="mb-0">Consultar Compra de SMS</h5>
    </div>

    <div class="card-body">
        <p>
            Permite consultar os detalhes de uma compra
            de SMS e verificar a situação do pagamento.
        </p>

        <h6>Autenticação</h6>
        <span class="badge bg-warning text-dark mb-3">
            Bearer Token
        </span>

        <h6>Endpoint</h6>
        <pre class="bg-light p-3 rounded"><code>GET /api/sms/compras/{id}</code></pre>

        <h6>Headers</h6>
        <pre class="bg-light p-3 rounded"><code>Authorization: Bearer SEU_TOKEN
Accept: application/json</code></pre>

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
                        <td>id</td>
                        <td>integer</td>
                        <td>Sim</td>
                        <td>Identificador da compra.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h6>Exemplo de requisição</h6>
        <pre class="bg-light p-3 rounded"><code>GET /api/sms/compras/15</code></pre>

        <h6>Exemplo ilustrativo de resposta</h6>
        <pre class="bg-light p-3 rounded"><code>{
    "status": 1,
    "data": {
        "id": 15,
        "quantidade_sms": 1000,
        "preco_unitario": "1.2500",
        "valor_total": "1250.00",
        "estado": "PAGA",
        "created_at": "2026-11-23 14:30:00"
    }
}</code></pre>

        <h6>Erros possíveis</h6>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>HTTP</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>401</td>
                        <td>Utilizador não autenticado.</td>
                    </tr>
                    <tr>
                        <td>403</td>
                        <td>Sem permissão para consultar a compra.</td>
                    </tr>
                    <tr>
                        <td>404</td>
                        <td>Compra não encontrada.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>