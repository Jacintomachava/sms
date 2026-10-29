<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">

    <title>Factura {{ $factura->numero }}</title>

    <style>
        @page {
            margin: 30px 35px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #263238;
        }

        .header {
            width: 100%;
            border-bottom: 3px solid #009db3;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header td {
            vertical-align: top;
        }

        .empresa {
            font-size: 21px;
            font-weight: bold;
            color: #009db3;
        }

        .subtitulo {
            font-size: 10px;
            color: #666;
        }

        .titulo {
            font-size: 19px;
            font-weight: bold;
            text-align: right;
        }

        .numero {
            text-align: right;
            margin-top: 5px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #009db3;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .box {
            border: 1px solid #ddd;
            padding: 12px;
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 5px;
            vertical-align: top;
        }

        .label {
            color: #666;
            font-size: 10px;
        }

        .value {
            font-weight: bold;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th {
            background: #009db3;
            color: white;
            padding: 10px 8px;
            text-align: left;
        }

        .data-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
        }

        .text-right {
            text-align: right;
        }

        .totais {
            width: 45%;
            margin-left: auto;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .totais td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .total-final {
            background: #e8f8fa;
            font-size: 14px;
            font-weight: bold;
        }

        .estado {
            display: inline-block;
            padding: 6px 12px;
            font-weight: bold;
            border-radius: 4px;
        }

        .paga {
            background: #d4edda;
            color: #155724;
        }

        .pendente {
            background: #fff3cd;
            color: #856404;
        }

        .vencida {
            background: #f8d7da;
            color: #721c24;
        }

        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            color: #777;
            font-size: 9px;
        }
    </style>
</head>

<body>

    {{-- CABEÇALHO --}}

    <table class="header">
        <tr>
            <td width="55%">
                <div class="empresa">InfoSMS</div>

                <div class="subtitulo">
                    Plataforma de Comunicação Empresarial
                </div>
            </td>

            <td width="45%">
                <div class="titulo">FACTURA</div>

                <div class="numero">
                    Nº {{ $factura->numero }}
                </div>
            </td>
        </tr>
    </table>

    {{-- DADOS DO CLIENTE --}}

    <div class="section-title">
        DADOS DO CLIENTE
    </div>

    <div class="box">

        <table class="info-table">

            <tr>
                <td width="50%">
                    <div class="label">Cliente / Empresa</div>

                    <div class="value">
                        {{ $factura->conta->nome_legal
                            ?: $factura->conta->nome }}
                    </div>
                </td>

                <td width="50%">
                    <div class="label">NUIT</div>

                    <div class="value">
                        {{ $factura->conta->nuit ?: '-' }}
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="label">Email</div>

                    <div class="value">
                        {{ $factura->conta->email ?: '-' }}
                    </div>
                </td>

                <td>
                    <div class="label">Telefone</div>

                    <div class="value">
                        {{ $factura->conta->telefone ?: '-' }}
                    </div>
                </td>
            </tr>

        </table>

    </div>

    {{-- INFORMAÇÕES DA FACTURA --}}

    <div class="section-title">
        INFORMAÇÕES DA FACTURA
    </div>

    <div class="box">

        <table class="info-table">

            <tr>
                <td width="50%">
                    <div class="label">Data de Emissão</div>

                    <div class="value">
                        {{ $factura->data_emissao?->format('d/m/Y') }}
                    </div>
                </td>

                <td width="50%">
                    <div class="label">Data de Vencimento</div>

                    <div class="value">
                        {{ $factura->data_vencimento?->format('d/m/Y') ?? '-' }}
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="label">Período de Consumo</div>

                    <div class="value">
                        {{ $factura->periodo_inicio?->format('d/m/Y') }}
                        a
                        {{ $factura->periodo_fim?->format('d/m/Y') }}
                    </div>
                </td>

                <td>
                    <div class="label">Estado</div>

                    <span class="estado
                        {{ $factura->estado === 'PAGA'
                            ? 'paga'
                            : ($factura->estado === 'VENCIDA'
                                ? 'vencida'
                                : 'pendente') }}">
                        {{ $factura->estado }}
                    </span>
                </td>
            </tr>

        </table>

    </div>

    {{-- CONSUMO SMS --}}

    <div class="section-title">
        DETALHES DO CONSUMO
    </div>

    <table class="data-table">

        <thead>
            <tr>
                <th>Descrição</th>
                <th class="text-right">Quantidade</th>
                <th class="text-right">Preço Unit.</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>

        <tbody>

            <tr>
                <td>
                    Consumo de SMS Empresarial

                    <br>

                    <small>
                        {{ number_format(
                            $factura->quantidade_sms,
                            0,
                            ',',
                            '.'
                        ) }} mensagens /
                        {{ number_format(
                            $factura->segmentos,
                            0,
                            ',',
                            '.'
                        ) }} segmentos
                    </small>
                </td>

                <td class="text-right">
                    {{ number_format(
                        $factura->segmentos,
                        0,
                        ',',
                        '.'
                    ) }}
                </td>

                <td class="text-right">
                    {{ number_format(
                        $factura->preco_unitario,
                        4,
                        ',',
                        '.'
                    ) }}
                </td>

                <td class="text-right">
                    {{ number_format(
                        $factura->subtotal,
                        2,
                        ',',
                        '.'
                    ) }}
                </td>
            </tr>

        </tbody>

    </table>

    {{-- TOTAIS --}}

    <table class="totais">

        <tr>
            <td>Subtotal</td>

            <td class="text-right">
                {{ number_format(
                    $factura->subtotal,
                    2,
                    ',',
                    '.'
                ) }} MZN
            </td>
        </tr>

        <tr>
            <td>Total da Factura</td>

            <td class="text-right">
                {{ number_format(
                    $factura->valor_total,
                    2,
                    ',',
                    '.'
                ) }} MZN
            </td>
        </tr>

        <tr>
            <td>Total Pago</td>

            <td class="text-right">
                {{ number_format(
                    $totalPago,
                    2,
                    ',',
                    '.'
                ) }} MZN
            </td>
        </tr>

        <tr class="total-final">
            <td>Saldo Pendente</td>

            <td class="text-right">
                {{ number_format(
                    $saldo,
                    2,
                    ',',
                    '.'
                ) }} MZN
            </td>
        </tr>

    </table>

    {{-- OBSERVAÇÕES --}}

    @if($factura->observacao)

        <div style="margin-top: 30px;">

            <div class="section-title">
                OBSERVAÇÕES
            </div>

            <p>{{ $factura->observacao }}</p>

        </div>

    @endif

    {{-- RODAPÉ --}}

    <div class="footer">
        Documento gerado pela plataforma InfoSMS.
    </div>

</body>
</html>