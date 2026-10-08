<section
    id="doc-introduction"
    class="api-doc-section"
>

    <h4>Introdução</h4>

    <p class="text-muted">
        A API SMS da INFORDATA permite integrar o envio
        e a consulta de SMS directamente nas suas
        aplicações e sistemas.
    </p>


    <h6 class="mt-4">
        URL Base
    </h6>

    <div class="input-group">

        <input
            type="text"
            class="form-control"
            id="docsBaseUrl"
            value="{{ url('/api/v1') }}"
            readonly
        >

        <button
            class="btn btn-outline-primary"
            onclick="copiarTexto('docsBaseUrl')"
        >
            Copiar
        </button>

    </div>


    <h6 class="mt-4">
        Formato
    </h6>

    <p>
        Todas as requisições e respostas utilizam
        <code>JSON</code>.
    </p>


    <h6 class="mt-4">
        Endpoints disponíveis
    </h6>

    <div class="table-responsive">

        <table class="table">

            <tbody>

            <tr>
                <td><strong>POST</strong></td>
                <td><code>/sms/send</code></td>
                <td>Enviar uma SMS</td>
            </tr>

            <tr>
                <td><strong>POST</strong></td>
                <td><code>/sms/send-many</code></td>
                <td>
                    Enviar a mesma mensagem
                    para vários números
                </td>
            </tr>

            <tr>
                <td><strong>POST</strong></td>
                <td><code>/sms/bulk</code></td>
                <td>Envio em massa</td>
            </tr>

            <tr>
                <td><strong>GET</strong></td>
                <td><code>/sms</code></td>
                <td>Listar SMS</td>
            </tr>

            <tr>
                <td><strong>GET</strong></td>
                <td><code>/sms/{id}</code></td>
                <td>Consultar uma SMS</td>
            </tr>

            <tr>
                <td><strong>GET</strong></td>
                <td><code>/senders</code></td>
                <td>Listar Senders disponíveis</td>
            </tr>

            <tr>
                <td><strong>GET</strong></td>
                <td><code>/balance</code></td>
                <td>Consultar saldo</td>
            </tr>

            </tbody>

        </table>

    </div>

</section>