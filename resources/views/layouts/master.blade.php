<!DOCTYPE html>
<html lang="en">
<head>

  @include('includes.cssMaster')

  @stack('css')

<body>

  @include('includes.menuMaster')


  <!-- =======================
  Banner
  ======================= -->
  @include('leading.banner')

  <!-- =======================
  Plan -- Plano
  ======================= -->
  @include('leading.plano')

  <!-- =======================
  Service -- Servico
  ======================= -->
  @include('leading.servico')
  

  @include('includes.footerMaster')

<div class="copy-right-info">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <p>© 2026 Infordata lda. | Todas os Direitos Reservados.</p>
      </div>
    </div>
  </div>
</div>

<!-- back to Top button -->
<button id="scrolltoTop" class="scroll-top d-flex align-items-center justify-content-center">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-up-circle" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-7.5 3.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707z"/>
</svg>
</button>

@include('includes.jsMaster')
@stack('js')

</body>

</html>
