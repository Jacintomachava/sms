@extends('layouts.app')

@push('css')
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<script src="https://cdn.jsdelivr.net/npm/echarts@5.3.3/dist/echarts.min.js"></script>
<style>
    #tipoChart, #estadoChart {
        width: 100%;
        height: 400px;
    }

</style>
@endpush

@section('conteudo')


<div class="row">
  
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card project-widget widget-1 title-line">
            <div class="card-header card-no-border pb-0"> 
            <div class="header-top">
                <div> 
                <h3 class="f-w-600">Saldo SMS</h3>
                </div>
                <div class="card-header-right-icon">
                <div class="dropdown icon-dropdown d-xxl-none1">
                    <button class="btn dropdown-toggle" id="active-project" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <svg>
                        <use href="{{ URL('/public/assets/svg/icon-sprite.svg#more-horizontal') }}"></use>
                    </svg>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="active-project">
                    </div>
                </div>
                </div>
            </div>
            <div class="widget-middle-content">
                <div class="d-flex align-items-center"> 
                <h2>{{$totalVideos}}</h2>
                    <span class="f-w-500 txt-warning f-12"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up me-1"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg><span>+100%</span></span>
                </div>
            </div>
            </div>
            <div class="card-body widget-bottom-content">
            <div class="progress" style="height: 5px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: 56%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card project-widget widget-1 title-line">
            <div class="card-header card-no-border pb-0"> 
            <div class="header-top">
                <div> 
                <h3 class="f-w-600">SLd fff</h3>
                </div>
                <div class="card-header-right-icon">
                <div class="dropdown icon-dropdown d-xxl-none1">
                    <button class="btn dropdown-toggle" id="active-project" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <svg>
                        <use href="{{ URL('/public/assets/svg/icon-sprite.svg#more-horizontal') }}"></use>
                    </svg>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="active-project">
                    </div>
                </div>
                </div>
            </div>
            <div class="widget-middle-content">
                <div class="d-flex align-items-center"> 
                <h2>{{$totalAprovado}}</h2>
                    <span class="f-w-500 txt-warning f-12"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up me-1"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg><span>
                        @if($totalAprovado > 0)
                            {{$totalAprovado/$totalVideos*100}}%
                        @else
                             0%
                        @endif
                    </span></span>
                </div>
            </div>
            </div>
            <div class="card-body widget-bottom-content">
            <div class="progress" style="height: 5px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: 56%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card project-widget widget-1 title-line">
            <div class="card-header card-no-border pb-0"> 
            <div class="header-top">
                <div> 
                <h3 class="f-w-600">Videos Rejeitados</h3>
                </div>
                <div class="card-header-right-icon">
                <div class="dropdown icon-dropdown d-xxl-none1">
                    <button class="btn dropdown-toggle" id="active-project" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <svg>
                        <use href="{{ URL('/public/assets/svg/icon-sprite.svg#more-horizontal') }}"></use>
                    </svg>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="active-project">
                    </div>
                </div>
                </div>
            </div>
            <div class="widget-middle-content">
                <div class="d-flex align-items-center"> 
                <h2>{{$totalRejeitado}}</h2>
                    <span class="f-w-500 txt-warning f-12"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up me-1"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg><span>
                        
                        @if($totalRejeitado > 0)
                            {{$totalRejeitado/$totalVideos*100}}%
                        @else
                             0%
                        @endif
                    </span></span>
                </div>
            </div>
            </div>
            <div class="card-body widget-bottom-content">
            <div class="progress" style="height: 5px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: 56%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card project-widget widget-1 title-line">
            <div class="card-header card-no-border pb-0"> 
            <div class="header-top">
                <div> 
                <h3 class="f-w-600">Videos Pendentes</h3>
                </div>
                <div class="card-header-right-icon">
                <div class="dropdown icon-dropdown d-xxl-none1">
                    <button class="btn dropdown-toggle" id="active-project" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <svg>
                        <use href="{{ URL('/public/assets/svg/icon-sprite.svg#more-horizontal') }}"></use>
                    </svg>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="active-project">
                    </div>
                </div>
                </div>
            </div>
            <div class="widget-middle-content">
                <div class="d-flex align-items-center"> 
                <h2>{{$totalPendente}}</h2>
                    <span class="f-w-500 txt-warning f-12"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up me-1"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg><span>
                        @if($totalPendente > 0)
                            {{$totalPendente/$totalVideos*100}}%
                        @else
                             0%
                        @endif
                    </span></span>
                </div>
            </div>
            </div>
            <div class="card-body widget-bottom-content">
            <div class="progress" style="height: 5px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: 56%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            </div>
        </div>
    </div>

</div>

<div class="row" style="background-color: white">
    <div class="col-xl-6">
        <br>
        <div id="tipoChart" width="300px" height="300px"></div>
    </div>
    
    <div class="col-xl-6">
        <br>
        <div id="estadoChart" width="300px" height="300px"></div>
    </div>
</div>

<br><br>



@endsection

@push('js')

<script>
        // Dados para o gráfico de Tipos
        var tiposData = @json($tipos); // Passando dados do Laravel para o JS
        var tipos = tiposData.map(function(item) {
            return {
                name: item.tipo,
                value: item.count
            };
        });

        // Gráfico de Tipos
        var tipoChart = echarts.init(document.getElementById('tipoChart'));
        var tipoOption = {
            title: {
                text: 'Distribuição de Tipos',
                subtext: 'Alfabeto, Palavra, Frase, Número, Outro',
                left: 'center'
            },
            tooltip: {
                trigger: 'item',
                formatter: '{a} <br/>{b}: {c} ({d}%)'
            },
            legend: {
                orient: 'vertical',
                left: 'left',
                data: tiposData.map(function(item) { return item.tipo; })
            },
            series: [
                {
                    name: 'Tipo',
                    type: 'pie',
                    radius: '50%',
                    data: tipos,
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(190, 31, 31, 0.5)'
                        }
                    },
                    label: {
                        formatter: '{b}: {c} ({d}%)'
                    }
                }
            ]
        };
        tipoChart.setOption(tipoOption);

        // Dados para o gráfico de Estados
        var estadosData = @json($estados);
        var estados = estadosData.map(function(item) {
            return {
                name: item.estado,
                value: item.count
            };
        });

        // Gráfico de Estados
        var estadoChart = echarts.init(document.getElementById('estadoChart'));
        var estadoOption = {
            title: {
                text: 'Distribuição de Estados',
                subtext: 'Pendente, Rejeitado, Aprovado',
                left: 'center'
            },
            tooltip: {
                trigger: 'item',
                formatter: '{a} <br/>{b}: {c} ({d}%)'
            },
            legend: {
                orient: 'vertical',
                left: 'left',
                data: estadosData.map(function(item) { return item.estado; })
            },
            series: [
                {
                    name: 'Estado',
                    type: 'pie',
                    radius: '50%',
                    data: estados,
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(224, 81, 81, 0.5)'
                        }
                    },
                    label: {
                        formatter: '{b}: {c} ({d}%)'
                    }
                }
            ]
        };
        estadoChart.setOption(estadoOption);
    </script>

@endpush
