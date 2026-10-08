@extends('layouts.app')

@section('conteudo')

<div class="container-fluid">
    <div class="page-title">
        <div class="row">

            <div class="col-6">
                <h4>Financeiro</h4>
            </div>

            <div class="col-6">
                <div class="text-end">

                    {{-- PRE-PAGO --}}
                    @if($conta->tipo_cobranca === 'PRE_PAGO')

                        <a
                            href="{{ route('compras.sms.index') }}"
                            class="btn btn-primary"
                        >
                            <i class="fa fa-plus-circle me-1"></i>
                            Recarregar SMS
                        </a>

                    {{-- POS-PAGO --}}
                    @elseif($conta->tipo_cobranca === 'POS_PAGO')

                        {{-- Por enquanto, até criarmos as facturas --}}
                        <button
                            type="button"
                            class="btn btn-primary"
                            disabled
                        >
                            <i class="fa fa-file-text-o me-1"></i>
                            Ver Facturas
                        </button>

                    @endif

                </div>
            </div>

        </div>
    </div>
</div>


<div class="container-fluid">

    {{-- ============================================================
         PRE-PAGO
    ============================================================= --}}
    @if($conta->tipo_cobranca === 'PRE_PAGO')

        <div class="row">

            {{-- SMS ENVIADOS --}}
            <div class="col-xl-3 col-md-6">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>

                                <p class="mb-1 text-muted">
                                    SMS enviados
                                </p>

                                <h3 class="mb-1">
                                    {{ number_format(
                                        $dadosPrePago['sms_enviados_mes'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </h3>

                                <small class="text-muted">
                                    Este mês
                                </small>

                            </div>

                            <div>
                                <i class="fa fa-paper-plane fa-2x"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- SEGMENTOS --}}
            <div class="col-xl-3 col-md-6">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>

                                <p class="mb-1 text-muted">
                                    Segmentos enviados
                                </p>

                                <h3 class="mb-1">
                                    {{ number_format(
                                        $dadosPrePago['segmentos_enviados_mes'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </h3>

                                <small class="text-muted">
                                    Este mês
                                </small>

                            </div>

                            <div>
                                <i class="fa fa-comments fa-2x"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- COMPRAS --}}
            <div class="col-xl-3 col-md-6">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>

                                <p class="mb-1 text-muted">
                                    Compras do mês
                                </p>

                                <h3 class="mb-1">
                                    {{ number_format(
                                        $dadosPrePago['sms_comprados_mes'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </h3>

                                <small class="text-muted">
                                    {{ number_format(
                                        $dadosPrePago['valor_comprado_mes'],
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                    MZN
                                </small>

                            </div>

                            <div>
                                <i class="fa fa-shopping-cart fa-2x"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- SALDO --}}
            <div class="col-xl-3 col-md-6">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>

                                <p class="mb-1 text-muted">
                                    Saldo actual
                                </p>

                                <h3 class="mb-1">
                                    {{ number_format(
                                        $dadosPrePago['saldo_sms'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </h3>

                                <small class="text-muted">
                                    Segmentos disponíveis
                                </small>

                            </div>

                            <div>
                                <i class="fa fa-wallet fa-2x"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endif



    {{-- ============================================================
         POS-PAGO
    ============================================================= --}}
    @if($conta->tipo_cobranca === 'POS_PAGO')

        <div class="row">

            {{-- SMS ENVIADOS --}}
            <div class="col-xl-3 col-md-6">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>

                                <p class="mb-1 text-muted">
                                    SMS enviados
                                </p>

                                <h3 class="mb-1">
                                    {{ number_format(
                                        $dadosPosPago['sms_enviados_mes'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </h3>

                                <small class="text-muted">
                                    Este mês
                                </small>

                            </div>

                            <div>
                                <i class="fa fa-paper-plane fa-2x"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- SEGMENTOS --}}
            <div class="col-xl-3 col-md-6">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>

                                <p class="mb-1 text-muted">
                                    Segmentos enviados
                                </p>

                                <h3 class="mb-1">
                                    {{ number_format(
                                        $dadosPosPago['segmentos_enviados_mes'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </h3>

                                <small class="text-muted">
                                    Este mês
                                </small>

                            </div>

                            <div>
                                <i class="fa fa-comments fa-2x"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- PREÇO --}}
            <div class="col-xl-3 col-md-6">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>

                                <p class="mb-1 text-muted">
                                    Preço / segmento
                                </p>

                                <h3 class="mb-1">

                                    {{ number_format(
                                        $dadosPosPago['preco_unitario'],
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                </h3>

                                <small class="text-muted">
                                    MZN
                                </small>

                            </div>

                            <div>
                                <i class="fa fa-tag fa-2x"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- VALOR ESTIMADO --}}
            <div class="col-xl-3 col-md-6">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>

                                <p class="mb-1 text-muted">
                                    Valor estimado
                                </p>

                                <h3 class="mb-1">

                                    {{ number_format(
                                        $dadosPosPago['valor_estimado'],
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                </h3>

                                <small class="text-muted">
                                    MZN • Este mês
                                </small>

                            </div>

                            <div>
                                <i class="fa fa-money fa-2x"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endif



    {{-- ============================================================
         GRÁFICOS
    ============================================================= --}}

    <div class="row">

        {{-- GRÁFICO DE BARRAS --}}
        <div class="col-xl-7">

            <div class="card">

                <div class="card-header">

                    <h4>
                        {{ $grafico['titulo_valores'] }}
                    </h4>

                    <p class="text-muted mb-0">
                        Evolução dos últimos 12 meses
                    </p>

                </div>

                <div class="card-body">

                    <div
                        id="graficoValores"
                        style="width: 100%; height: 360px;"
                    ></div>

                </div>

            </div>

        </div>


        {{-- PIE --}}
        <div class="col-xl-5">

            <div class="card">

                <div class="card-header">

                    <h4>SMS enviados por mês</h4>

                    <p class="text-muted mb-0">
                        Distribuição mensal
                    </p>

                </div>

                <div class="card-body">

                    <div
                        id="graficoSms"
                        style="width: 100%; height: 360px;"
                    ></div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection


@section('script')

<script>

$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | DADOS
    |--------------------------------------------------------------------------
    */

    const labels =
        @json($grafico['labels']);

    const valores =
        @json($grafico['valores']);

    const smsEnviados =
        @json($grafico['sms_enviados']);


    /*
    |--------------------------------------------------------------------------
    | GRÁFICO DE BARRAS
    |--------------------------------------------------------------------------
    */

    const elementoValores =
        document.getElementById('graficoValores');

    if (elementoValores) {

        const graficoValores =
            echarts.init(elementoValores);

        const optionValores = {

            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                },
                formatter: function (params) {

                    let item = params[0];

                    let valor =
                        Number(item.value)
                            .toLocaleString(
                                'pt-PT',
                                {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }
                            );

                    return `
                        ${item.name}<br>
                        <strong>${valor} MZN</strong>
                    `;
                }
            },

            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },

            xAxis: {
                type: 'category',
                data: labels,
                axisLabel: {
                    interval: 0,
                    rotate: 35
                }
            },

            yAxis: {
                type: 'value'
            },

            series: [
                {
                    name: 'Valor',
                    type: 'bar',
                    data: valores,
                    barMaxWidth: 45,
                    itemStyle: {
                        borderRadius: [6, 6, 0, 0]
                    }
                }
            ]
        };

        graficoValores.setOption(
            optionValores
        );


        window.addEventListener(
            'resize',
            function () {
                graficoValores.resize();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PIE - SMS ENVIADOS
    |--------------------------------------------------------------------------
    */

    const elementoSms =
        document.getElementById('graficoSms');

    if (elementoSms) {

        const graficoSms =
            echarts.init(elementoSms);


        /*
        |--------------------------------------------------------------------------
        | TRANSFORMAR DADOS
        |--------------------------------------------------------------------------
        |
        | Para não poluir o gráfico, removemos meses sem SMS.
        |
        */

        let dadosPie = [];

        labels.forEach(
            function (label, index) {

                let quantidade =
                    Number(
                        smsEnviados[index] ?? 0
                    );

                if (quantidade > 0) {

                    dadosPie.push({
                        name: label,
                        value: quantidade
                    });
                }
            }
        );


        const optionSms = {

            tooltip: {
                trigger: 'item',

                formatter:
                    '{b}<br><strong>{c} SMS</strong> ({d}%)'
            },

            legend: {
                bottom: 0,
                left: 'center'
            },

            series: [
                {
                    name: 'SMS enviados',

                    type: 'pie',

                    radius: [
                        '45%',
                        '70%'
                    ],

                    center: [
                        '50%',
                        '45%'
                    ],

                    avoidLabelOverlap: true,

                    itemStyle: {
                        borderRadius: 6,
                        borderWidth: 2
                    },

                    label: {
                        show: true,
                        formatter: '{b}\n{c}'
                    },

                    data: dadosPie
                }
            ]
        };


        graficoSms.setOption(
            optionSms
        );


        window.addEventListener(
            'resize',
            function () {
                graficoSms.resize();
            }
        );
    }

});

</script>

@endsection