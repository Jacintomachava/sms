<div
    class="tab-pane fade"
    id="quickstart"
    role="tabpanel"
>

    <div class="row">

        <div class="col-xl-8">

            <h5>Envie a sua primeira SMS</h5>

            <p class="text-muted">
                Escolha a sua linguagem, copie o exemplo
                e substitua os dados necessários.
            </p>

        </div>

        <div class="col-xl-4 text-xl-end">

            <span class="badge bg-light-success">
                API v1
            </span>

        </div>

    </div>


    {{-- AVISO API KEY --}}
    <div
        class="alert alert-light-primary mt-3"
        id="apiKeyQuickAlert"
    >

        <strong>API Key:</strong>

        <span id="apiKeyQuickMessage">
            utilize a sua API Key no lugar de
            <code>SUA_API_KEY</code>.
        </span>

    </div>


    {{-- DADOS DA INTEGRAÇÃO --}}
    <div class="row mt-4">

        <div class="col-md-6">

            <label class="form-label">
                URL Base
            </label>

            <div class="input-group">

                <input
                    type="text"
                    class="form-control"
                    value="{{ url('/api/v1') }}"
                    readonly
                >

            </div>

        </div>


        <div class="col-md-6">

            <label class="form-label">
                Sender
            </label>

            <select
                class="form-select"
                id="quickSender"
            >

                @forelse($senders as $sender)

                    <option
                        value="{{ $sender->sender }}"
                    >
                        {{ $sender->sender }}
                    </option>

                @empty

                    <option value="SEU_SENDER">
                        Nenhum Sender activo
                    </option>

                @endforelse

            </select>

        </div>

    </div>


    {{-- TABS DAS LINGUAGENS --}}
    <div class="mt-4">

        <ul
            class="nav nav-pills"
            id="quickLanguageTabs"
            role="tablist"
        >

            <li class="nav-item">
                <button
                    class="nav-link active"
                    data-bs-toggle="pill"
                    data-bs-target="#quickCurl"
                    type="button"
                >
                    cURL
                </button>
            </li>

            <li class="nav-item">
                <button
                    class="nav-link"
                    data-bs-toggle="pill"
                    data-bs-target="#quickPhp"
                    type="button"
                >
                    PHP
                </button>
            </li>

            <li class="nav-item">
                <button
                    class="nav-link"
                    data-bs-toggle="pill"
                    data-bs-target="#quickJava"
                    type="button"
                >
                    Java
                </button>
            </li>

            <li class="nav-item">
                <button
                    class="nav-link"
                    data-bs-toggle="pill"
                    data-bs-target="#quickPython"
                    type="button"
                >
                    Python
                </button>
            </li>

        </ul>


        <div class="tab-content mt-3">

            {{-- CURL --}}
            <div
                class="tab-pane fade show active"
                id="quickCurl"
            >

                <div class="position-relative">

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary position-absolute end-0 m-3"
                        onclick="copiarCodigo('codeCurl')"
                    >
                        Copiar
                    </button>

                    <pre class="api-code-block"><code id="codeCurl"></code></pre>

                </div>

            </div>


            {{-- PHP --}}
            <div
                class="tab-pane fade"
                id="quickPhp"
            >

                <div class="position-relative">

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary position-absolute end-0 m-3"
                        onclick="copiarCodigo('codePhp')"
                    >
                        Copiar
                    </button>

                    <pre class="api-code-block"><code id="codePhp"></code></pre>

                </div>

            </div>


            {{-- JAVA --}}
            <div
                class="tab-pane fade"
                id="quickJava"
            >

                <div class="position-relative">

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary position-absolute end-0 m-3"
                        onclick="copiarCodigo('codeJava')"
                    >
                        Copiar
                    </button>

                    <pre class="api-code-block"><code id="codeJava"></code></pre>

                </div>

            </div>


            {{-- PYTHON --}}
            <div
                class="tab-pane fade"
                id="quickPython"
            >

                <div class="position-relative">

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary position-absolute end-0 m-3"
                        onclick="copiarCodigo('codePython')"
                    >
                        Copiar
                    </button>

                    <pre class="api-code-block"><code id="codePython"></code></pre>

                </div>

            </div>

        </div>

    </div>

</div>