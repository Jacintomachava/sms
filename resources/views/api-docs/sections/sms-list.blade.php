<section id="doc-sms-list" class="api-doc-section">

    <h4>Listar SMS</h4>

    <p>
        Consulta o histórico de SMS da conta autenticada.
    </p>

    <div class="api-endpoint">
        <span class="api-method api-method-get">GET</span>
        <span class="api-endpoint-path">/sms</span>
    </div>

    <h6>Filtros disponíveis</h6>

    <div class="table-responsive">
        <table class="table">

            <thead>
            <tr>
                <th>Parâmetro</th>
                <th>Exemplo</th>
                <th>Descrição</th>
            </tr>
            </thead>

            <tbody>
            <tr>
                <td><code>status</code></td>
                <td>SENT</td>
                <td>Filtrar pelo estado da SMS.</td>
            </tr>

            <tr>
                <td><code>sender</code></td>
                <td>EAGUAS</td>
                <td>Filtrar por Sender.</td>
            </tr>

            <tr>
                <td><code>to</code></td>
                <td>25884XXXXXXX</td>
                <td>Filtrar pelo destinatário.</td>
            </tr>

            <tr>
                <td><code>from</code></td>
                <td>2026-09-01</td>
                <td>Data inicial.</td>
            </tr>

            <tr>
                <td><code>to_date</code></td>
                <td>2026-09-30</td>
                <td>Data final.</td>
            </tr>

            <tr>
                <td><code>page</code></td>
                <td>1</td>
                <td>Página.</td>
            </tr>

            <tr>
                <td><code>per_page</code></td>
                <td>50</td>
                <td>Resultados por página. Máximo 100.</td>
            </tr>
            </tbody>

        </table>
    </div>

    <h6 class="mt-4">Exemplo</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>GET /sms?status=SENT&amp;sender=EAGUAS&amp;page=1&amp;per_page=50</code></pre>
    </div>

    <h6 class="mt-4">Response</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>{
    "status": 1,
    "message": "SMS consultadas com sucesso.",
    "data": [
        {
            "id": 128,
            "to": "25884XXXXXXX",
            "sender": "EAGUAS",
            "message": "Factura paga com sucesso.",
            "encoding": "GSM7",
            "characters": 25,
            "segments": 1,
            "sms_status": "SENT",
            "sent_at": "2026-09-27T14:00:00Z",
            "created_at": "2026-09-27T14:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 50,
        "total": 1,
        "last_page": 1,
        "from": 1,
        "to": 1
    }
}</code></pre>
    </div>

</section>