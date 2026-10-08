@extends('layouts.app')

@section('css')
<style>

.api-code-block {
    background: #1e1e1e;
    color: #0b0d97;
    border-radius: 6px;
    padding: 24px;
    padding-top: 55px;
    min-height: 180px;
    max-height: 520px;
    overflow: auto;
    margin: 0;
}

.api-code-block code {
    color: inherit;
    font-family: Consolas, Monaco, "Courier New", monospace;
    font-size: 13px;
    line-height: 1.7;
    white-space: pre;
}

.api-code-wrapper {
    position: relative;
}

.api-copy-button {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 2;
}


.api-doc-menu {
    position: sticky;
    top: 90px;
    border-right: 1px solid #eee;
    padding-right: 20px;
}

.api-doc-link {
    color: #555;
    padding: 7px 12px;
    border-left: 2px solid transparent;
}

.api-doc-link:hover {
    color: var(--theme-default);
}

.api-doc-link.active {
    color: var(--theme-default);
    border-left-color: var(--theme-default);
    font-weight: 600;
}

.api-doc-section {
    padding-bottom: 50px;
    margin-bottom: 35px;
    border-bottom: 1px solid #eee;
    scroll-margin-top: 110px;
}

.api-doc-section:last-child {
    border-bottom: 0;
}

.api-endpoint {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 18px 0;
}

.api-method {
    font-weight: 600;
    min-width: 55px;
}

.api-method-get {
    color: #198754;
}

.api-method-post {
    color: #0d6efd;
}

.api-endpoint-path {
    font-family: Consolas, Monaco, monospace;
    background: #f5f5f5;
    padding: 7px 12px;
    border-radius: 5px;
}

</style>


@endsection

@section('conteudo')


<div class="row">
    <div class="col-sm-12">

        <div class="card">

            <div class="card-header">

                <ul
                    class="nav nav-tabs border-tab"
                    id="apiTabs"
                    role="tablist"
                >

                    <li class="nav-item">
                        <a
                            class="nav-link active"
                            id="overview-tab"
                            data-bs-toggle="tab"
                            href="#overview"
                            role="tab"
                        >
                            Visão Geral
                        </a>
                    </li>


                    <li class="nav-item">
                        <a
                            class="nav-link"
                            id="keys-tab"
                            data-bs-toggle="tab"
                            href="#keys"
                            role="tab"
                        >
                            API Keys
                        </a>
                    </li>


                    <li class="nav-item">
                        <a
                            class="nav-link"
                            id="quickstart-tab"
                            data-bs-toggle="tab"
                            href="#quickstart"
                            role="tab"
                        >
                            Início Rápido
                        </a>
                    </li>


                    <li class="nav-item">
                        <a
                            class="nav-link"
                            id="docs-tab"
                            data-bs-toggle="tab"
                            href="#docs"
                            role="tab"
                        >
                            Documentação
                        </a>
                    </li>

                </ul>

            </div>


            <div class="card-body">

                <div class="tab-content" id="apiTabsContent">

                    @include(
                        'api-keys.tabs.overview'
                    )

                    @include(
                        'api-keys.tabs.keys'
                    )

                    @include(
                        'api-keys.tabs.quickstart'
                    )

                    @include(
                        'api-keys.tabs.documentation'
                    )

                   
                </div>

            </div>

        </div>

    </div>
</div>

</div>


</div>


<div class="modal fade" id="modalApiKey" tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form id="formApiKey">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">
                        Gerar nova API Key
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <div class="mb-3">

                        <label class="form-label">
                            Nome
                        </label>

                        <input
                            type="text"
                            name="nome"
                            class="form-control"
                            value="API Principal"
                            maxlength="100"
                        >

                        <small class="text-muted">
                            Ex.: API Principal, ERP,
                            Sistema de Facturação.
                        </small>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnGerarApiKey"
                    >
                        Gerar API Key
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection

@section('script')

<script>

let currentApiKey = null;

function actualizarExemplosApi(apiKey = null) {

    const key = apiKey || 'SUA_API_KEY';

    const sender =
        document.getElementById('quickSender')
            ?.value || 'SEU_SENDER';

    const baseUrl =
        "{{ url('/api/v1') }}";

    const endpoint =
        baseUrl + '/sms/send';


    /*
    |--------------------------------------------------------------------------
    | cURL
    |--------------------------------------------------------------------------
    */
    const curl = [
        `curl -X POST "${endpoint}" \\`,
        `-H "X-API-Key: ${key}" \\`,
        `-H "Accept: application/json" \\`,
        `-H "Content-Type: application/json" \\`,
        `-d '{`,
        `  "to": "25884XXXXXXX",`,
        `  "sender": "${sender}",`,
        `  "message": "Minha primeira SMS através da API."`,
        `}'`
    ].join('\n');


    /*
    |--------------------------------------------------------------------------
    | PHP
    |--------------------------------------------------------------------------
    */
    const php = [
        '<? php',
        '',
        `$url = '${endpoint}';`,
        '',
        '$data = [',
        `    'to' => '25884XXXXXXX',`,
        `    'sender' => '${sender}',`,
        `    'message' => 'Minha primeira SMS através da API.'`,
        '];',
        '',
        '$ch = curl_init($url);',
        '',
        'curl_setopt_array($ch, [',
        '    CURLOPT_POST => true,',
        '    CURLOPT_RETURNTRANSFER => true,',
        '    CURLOPT_HTTPHEADER => [',
        `        'X-API-Key: ${key}',`,
        `        'Accept: application/json',`,
        `        'Content-Type: application/json'`,
        '    ],',
        '    CURLOPT_POSTFIELDS => json_encode($data)',
        ']);',
        '',
        '$response = curl_exec($ch);',
        '',
        'curl_close($ch);',
        '',
        'echo $response;'
    ].join('\n');


    /*
    |--------------------------------------------------------------------------
    | Java 11+
    |--------------------------------------------------------------------------
    */

    const java = [
        'import java.net.URI;',
        'import java.net.http.HttpClient;',
        'import java.net.http.HttpRequest;',
        'import java.net.http.HttpResponse;',
        '',
        'public class SmsExample {',
        '',
        '    public static void main(String[] args) throws Exception {',
        '',
        '        String json = """',
        '        {',
        '            "to": "25884XXXXXXX",',
        `            "sender": "${sender}",`,
        '            "message": "Minha primeira SMS através da API."',
        '        }',
        '        """;',
        '',
        '        HttpRequest request = HttpRequest.newBuilder()',
        `            .uri(URI.create("${endpoint}"))`,
        `            .header("X-API-Key", "${key}")`,
        '            .header("Accept", "application/json")',
        '            .header("Content-Type", "application/json")',
        '            .POST(HttpRequest.BodyPublishers.ofString(json))',
        '            .build();',
        '',
        '        HttpClient client = HttpClient.newHttpClient();',
        '',
        '        HttpResponse<String> response = client.send(',
        '            request,',
        '            HttpResponse.BodyHandlers.ofString()',
        '        );',
        '',
        '        System.out.println(response.body());',
        '    }',
        '}'
    ].join('\n');


    /*
    |--------------------------------------------------------------------------
    | Python
    |--------------------------------------------------------------------------
    */

    const python = [
        'import requests',
        '',
        `url = "${endpoint}"`,
        '',
        'headers = {',
        `    "X-API-Key": "${key}",`,
        '    "Accept": "application/json",',
        '    "Content-Type": "application/json"',
        '}',
        '',
        'data = {',
        '    "to": "25884XXXXXXX",',
        `    "sender": "${sender}",`,
        '    "message": "Minha primeira SMS através da API."',
        '}',
        '',
        'response = requests.post(',
        '    url,',
        '    headers=headers,',
        '    json=data',
        ')',
        '',
        'print(response.json())'
    ].join('\n');


    /*
    |--------------------------------------------------------------------------
    | Mostrar código
    |--------------------------------------------------------------------------
    */

    $('#codeCurl').text(curl);
    $('#codePhp').text(php);
    $('#codeJava').text(java);
    $('#codePython').text(python);


    /*
    |--------------------------------------------------------------------------
    | Informação da API Key
    |--------------------------------------------------------------------------
    */

    if (apiKey) {

        $('#apiKeyQuickMessage').html(
            `A chave recém-gerada foi inserida
             automaticamente nos exemplos abaixo.
             <strong>
                Ela desaparecerá quando actualizar
                ou sair desta página.
             </strong>`
        );

    } else {

        $('#apiKeyQuickMessage').html(
            `Utilize a sua API Key no lugar de
             <code>SUA_API_KEY</code>.`
        );
    }
}


$('#formApiKey').on('submit', function (e) {

    e.preventDefault();

    const button =
        $('#btnGerarApiKey');

    button.prop('disabled', true);


    $.ajax({

        url:
            "{{ route('api-keys.store') }}",

        type:
            "POST",

        data:
            $(this).serialize(),

        success: function (response) {

            button.prop(
                'disabled',
                false
            );


            if (response.status !== 1) {
                return;
            }


            currentApiKey =
                response.data.api_key;


            /*
             * Fecha o modal Bootstrap.
             */
            const modalElement =
                document.getElementById(
                    'modalApiKey'
                );

            const modal =
                bootstrap.Modal
                    .getInstance(
                        modalElement
                    );

            modal?.hide();


            /*
             * Actualiza exemplos da página
             * sem guardar a chave na BD.
             */
            actualizarExemplosApi(
                currentApiKey
            );


            Swal.fire({

                icon:
                    'success',

                title:
                    'API Key criada com sucesso',

                html: `

                    <p>
                        Esta é a única vez que a chave
                        completa será apresentada.
                    </p>

                    <div
                        class="alert alert-warning text-start"
                    >
                        <strong>Importante:</strong>

                        a chave ficará disponível nos
                        exemplos desta página enquanto
                        não actualizar ou sair.

                        <br><br>

                        Ao recarregar a página, a chave
                        completa deixará de ser exibida.
                    </div>


                    <div class="input-group mt-3">

                        <input
                            id="novaApiKey"
                            class="form-control"
                            value="${currentApiKey}"
                            readonly
                        >

                        <button
                            type="button"
                            class="btn btn-primary"
                            onclick="
                                copiarTexto('novaApiKey')
                            "
                        >
                            Copiar
                        </button>

                    </div>

                `,

                confirmButtonText:
                    'Já guardei',

                allowOutsideClick:
                    false,

                width:
                    650

            });

        },

        error: function (xhr) {

            button.prop(
                'disabled',
                false
            );

            Swal.fire({
                icon: 'error',

                title: 'Erro',

                text:
                    xhr.responseJSON
                        ?.message
                    ?? 'Não foi possível gerar a API Key.'
            });

        }

    });

});

window.abrirTab = function (id) {

    const element = document.getElementById(id);

    if (!element) {
        return;
    }

    const tab = new bootstrap.Tab(element);

    tab.show();
};

$(document).ready(function () {

    actualizarExemplosApi();

    $('#quickSender').on('change', function () {

        actualizarExemplosApi(
            currentApiKey
        );

    });

});

window.copiarCodigo = function (id) {

    const element =
        document.getElementById(id);

    if (!element) {
        return;
    }

    navigator.clipboard
        .writeText(
            element.innerText
        )
        .then(function () {

            Swal.fire({
                icon: 'success',
                title: 'Copiado',
                text: 'Código copiado.',
                timer: 1000,
                showConfirmButton: false
            });

        });
};

</script>

@endsection