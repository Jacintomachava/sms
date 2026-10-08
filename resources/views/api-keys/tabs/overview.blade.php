<div
    class="tab-pane fade show active"
    id="overview"
    role="tabpanel"
>

    <div class="row">

        <div class="col-xl-8">

            <h5>
                Integre o seu sistema em poucos minutos
            </h5>

            <p class="text-muted">
                Utilize a API SMS da INFORDATA para enviar
                mensagens directamente a partir das suas
                aplicações e sistemas.
            </p>


            <div class="mt-4">

                <h6>URL Base</h6>

                <div class="input-group">

                    <input
                        type="text"
                        class="form-control"
                        id="apiBaseUrl"
                        value="{{ url('/api/v1') }}"
                        readonly
                    >

                    <button
                        class="btn btn-outline-primary"
                        type="button"
                        onclick="copiarTexto('apiBaseUrl')"
                    >
                        Copiar
                    </button>

                </div>

            </div>


            <div class="mt-4">

                <h6>Autenticação</h6>

                <p class="text-muted">
                    Inclua a sua API Key no header
                    <code>X-API-Key</code>
                    de cada requisição.
                </p>

<pre><code>X-API-Key: SUA_API_KEY</code></pre>

            </div>

        </div>


        <div class="col-xl-4">

            <div class="card bg-light-primary">

                <div class="card-body">

                    <h5>
                        Começar
                    </h5>

                    <div class="mt-4">

                        <p>
                            <strong>1.</strong>
                            Gere uma API Key.
                        </p>

                        <p>
                            <strong>2.</strong>
                            Utilize um Sender activo.
                        </p>

                        <p>
                            <strong>3.</strong>
                            Copie um exemplo de integração.
                        </p>

                        <p>
                            <strong>4.</strong>
                            Envie a sua primeira SMS.
                        </p>

                    </div>


                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="abrirTab('keys-tab')"
                    >
                        Gerir API Keys
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        onclick="abrirTab('quickstart-tab')"
                    >
                        Ver exemplos
                    </button>

                </div>

            </div>

        </div>

    </div>


    <hr class="my-4">


    <div class="row">

        <div class="col-md-6">

            <h6>Senders disponíveis</h6>

            @forelse($senders as $sender)

                <span class="badge bg-primary me-2 mb-2">
                    {{ $sender->sender }}
                </span>

            @empty

                <div class="alert alert-warning">
                    Não possui nenhum Sender activo.
                </div>

            @endforelse

        </div>


        <div class="col-md-6">

            <h6>Tipo de cobrança</h6>

            @if($conta->tipo_cobranca === 'PRE_PAGO')

                <span class="badge bg-success">
                    PRÉ-PAGO
                </span>

                <p class="text-muted mt-2">
                    Cada segmento enviado é descontado
                    do seu saldo SMS.
                </p>

            @else

                <span class="badge bg-info">
                    PÓS-PAGO
                </span>

            @endif

        </div>

    </div>

</div>