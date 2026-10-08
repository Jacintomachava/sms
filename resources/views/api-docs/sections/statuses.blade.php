<section id="doc-statuses" class="api-doc-section">

    <h4>Estados da SMS</h4>

    <p>
        O campo <code>sms_status</code> indica o estado actual
        de processamento da mensagem.
    </p>

    <div class="table-responsive">

        <table class="table">

            <thead>
            <tr>
                <th>Estado</th>
                <th>Descrição</th>
            </tr>
            </thead>

            <tbody>

            <tr>
                <td><code>PENDING</code></td>
                <td>A SMS aguarda processamento.</td>
            </tr>

            <tr>
                <td><code>PROCESSING</code></td>
                <td>A SMS encontra-se em processamento.</td>
            </tr>

            <tr>
                <td><code>SENT</code></td>
                <td>
                    A SMS foi aceite para envio pelo sistema.
                </td>
            </tr>

            <tr>
                <td><code>DELIVERED</code></td>
                <td>
                    A entrega ao destinatário foi confirmada.
                </td>
            </tr>

            <tr>
                <td><code>FAILED</code></td>
                <td>O envio falhou.</td>
            </tr>

            <tr>
                <td><code>REJECTED</code></td>
                <td>A SMS foi rejeitada.</td>
            </tr>

            <tr>
                <td><code>EXPIRED</code></td>
                <td>A SMS expirou antes da entrega.</td>
            </tr>

            </tbody>

        </table>

    </div>

</section>