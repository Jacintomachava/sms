<!doctype html>
<html lang="pt-MZ">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="InforSMS - Plataforma de comunicação empresarial por SMS em Moçambique.">
    <title>InforSMS | Infordata</title>
    <link rel="icon" href="{{ asset('assets/inforsms/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/inforsms/site.css') }}">
</head>
<body>
@include('includes.menuMaster')
<main>
    @include('leading.banner')
    @include('leading.plano')
    @include('leading.servico')
    @include('leading.como-funciona')
</main>
@include('includes.footerMaster')
<script src="{{ asset('assets/inforsms/site.js') }}" defer></script>
</body>
</html>
