<div class="table-responsive">

    <table class="table table-hover align-middle">

        <thead>
            <tr>
                <th>ID</th>
                <th>Conta</th>
                <th>Destinatário</th>
                <th>Mensagem</th>
                <th>Segmentos</th>
                <th>Origem</th>
                <th>Estado</th>
                <th>DLR</th>
                <th>Data</th>
                <th>Acções</th>
            </tr>
        </thead>

        <tbody>

            @forelse($sms as $item)

                @php
                    $cores = [
                        'PENDENTE' => 'secondary',
                        'PROCESSANDO' => 'info',
                        'INCERTO' => 'warning',
                        'ENVIADO' => 'primary',
                        'ENTREGUE' => 'success',
                        'FALHADO' => 'danger',
                        'REJEITADO' => 'danger',
                        'EXPIRADO' => 'dark',
                    ];

                    $cor = $cores[$item->estado] ?? 'secondary';
                @endphp

                <tr>

                    <td>
                        <strong>#{{ $item->id }}</strong>
                    </td>

                    <td>
                        <strong>
                            {{ $item->conta_nome_legal ?: $item->conta_nome }}
                        </strong>

                        <br>

                        <small class="text-muted">
                            {{ $item->tipo_cobranca }}
                        </small>
                    </td>

                    <td>{{ $item->telefone }}</td>

                    <td style="max-width: 250px;">

                        <span
                            class="d-inline-block text-truncate"
                            style="max-width: 230px;"
                            title="{{ $item->mensagem }}"
                        >
                            {{ $item->mensagem }}
                        </span>

                    </td>

                    <td>
                        <span class="badge bg-light text-dark">
                            {{ $item->segmentos }}
                        </span>
                    </td>

                    <td>{{ $item->origem }}</td>

                    <td>
                        <span class="badge bg-{{ $cor }}">
                            {{ $item->estado }}
                        </span>
                    </td>

                    <td>
                        @if($item->dlr_estado)

                            <span class="badge bg-info">
                                {{ $item->dlr_estado }}
                            </span>

                        @else

                            <span class="text-muted">
                                Sem DLR
                            </span>

                        @endif
                    </td>

                    <td>
                        {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}
                    </td>

                    <td>

                        <a
                            href="{{ route('admin.sms.show', $item->id) }}"
                            class="btn btn-sm btn-outline-primary"
                        >
                            Detalhes
                        </a>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="10" class="text-center py-4">
                        Nenhum SMS encontrado.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</div>

<div class="mt-3">
    {{ $sms->links() }}
</div>