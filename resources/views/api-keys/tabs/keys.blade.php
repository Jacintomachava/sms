<div
    class="tab-pane fade"
    id="keys"
    role="tabpanel"
>

    <div
        class="d-flex justify-content-between
        align-items-center mb-4"
    >

        <div>
            <h5>API Keys</h5>

            <p class="text-muted mb-0">
                Utilize as chaves abaixo para autenticar
                os seus sistemas na API.
            </p>
        </div>


        <button
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#modalApiKey"
        >
            Gerar nova chave
        </button>

    </div>


    <div class="alert alert-light-primary">

        <strong>Segurança:</strong>

        a chave completa é apresentada apenas no momento
        da criação. Depois disso mostramos somente uma
        versão parcial da chave.

    </div>


    <div class="table-responsive">

        <table class="table">

            <thead>
            <tr>
                <th>Nome</th>
                <th>API Key</th>
                <th>Estado</th>
                <th>Último uso</th>
                <th>Criada em</th>
                <th>Último IP</th>
                <th></th>
            </tr>
            </thead>

            <tbody>

            @forelse($keys as $key)

                <tr>

                    <td>
                        {{ $key->nome }}
                    </td>

                    <td>
                        <code>
                            {{ $key->prefixo }}
                        </code>
                    </td>

                    <td>

                        @if($key->estado === 'ACTIVA')

                            <span class="badge bg-success">
                                ACTIVA
                            </span>

                        @else

                            <span class="badge bg-secondary">
                                REVOGADA
                            </span>

                        @endif

                    </td>

                    <td>
                        {{ $key->ultimo_uso_em
                            ? $key->ultimo_uso_em
                                ->format('d/m/Y H:i')
                            : 'Nunca'
                        }}
                    </td>

                    <td>
                        {{ $key->created_at
                            ->format('d/m/Y H:i')
                        }}
                    </td>

                    <td>
                        {{ $key->ultimo_ip ?? '-' }}
                    </td>

                    <td>

                        @if($key->estado === 'ACTIVA')

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger btn-revogar"
                                data-id="{{ $key->id }}"
                            >
                                Revogar
                            </button>

                        @endif

                    </td>

                </tr>

            @empty

                <tr>
                    <td
                        colspan="6"
                        class="text-center"
                    >
                        Ainda não possui uma API Key.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>