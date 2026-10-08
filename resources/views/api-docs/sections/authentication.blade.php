<section
    id="doc-authentication"
    class="api-doc-section"
>

    <h4>Autenticação</h4>

    <p>
        A API utiliza uma API Key para autenticar
        cada requisição.
    </p>

    <p>
        Envie a chave através do header:
    </p>


    <div class="api-code-wrapper">

        <button
            class="btn btn-sm btn-light api-copy-button"
            onclick="copiarCodigo('docAuthCode')"
        >
            Copiar
        </button>

        <pre class="api-code-block"><code id="docAuthCode">X-API-Key: SUA_API_KEY</code></pre>

    </div>


    <div class="alert alert-warning mt-3">

        <strong>Importante:</strong>

        nunca exponha a sua API Key em código
        JavaScript executado directamente no navegador,
        aplicações públicas ou repositórios de código.

    </div>


    <p>
        Exemplo:
    </p>


    <div class="api-code-wrapper">

        <button
            class="btn btn-sm btn-light api-copy-button"
            onclick="copiarCodigo('docAuthCurl')"
        >
            Copiar
        </button>

        <pre class="api-code-block"><code id="docAuthCurl">curl "{{ url('/api/v1/balance') }}" \
        -H "X-API-Key: SUA_API_KEY" \
        -H "Accept: application/json"</code></pre>

    </div>

</section>