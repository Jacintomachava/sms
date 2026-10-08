<div class="api-documentation">

    <div class="row">

        {{-- MENU --}}
        <div class="col-xl-3 col-lg-4">

            <div class="api-doc-menu">

                <div class="mb-4">

                    <small
                        class="text-muted text-uppercase fw-bold"
                    >
                        Começar
                    </small>

                    <nav class="nav flex-column mt-2">

                        <a
                            class="nav-link api-doc-link active"
                            href="#doc-introduction"
                        >
                            Introdução
                        </a>

                        <a
                            class="nav-link api-doc-link"
                            href="#doc-authentication"
                        >
                            Autenticação
                        </a>

                    </nav>

                </div>


                <div class="mb-4">

                    <small
                        class="text-muted text-uppercase fw-bold"
                    >
                        SMS
                    </small>

                    <nav class="nav flex-column mt-2">

                        <a
                            class="nav-link api-doc-link"
                            href="#doc-send"
                        >
                            Enviar SMS
                        </a>

                        <a
                            class="nav-link api-doc-link"
                            href="#doc-send-many"
                        >
                            Enviar para vários números
                        </a>

                        <a
                            class="nav-link api-doc-link"
                            href="#doc-bulk"
                        >
                            Envio em massa
                        </a>

                        <a
                            class="nav-link api-doc-link"
                            href="#doc-sms-list"
                        >
                            Listar SMS
                        </a>

                        <a
                            class="nav-link api-doc-link"
                            href="#doc-sms-show"
                        >
                            Consultar SMS
                        </a>

                    </nav>

                </div>


                <div class="mb-4">

                    <small
                        class="text-muted text-uppercase fw-bold"
                    >
                        Conta
                    </small>

                    <nav class="nav flex-column mt-2">

                        <a
                            class="nav-link api-doc-link"
                            href="#doc-senders"
                        >
                            Senders
                        </a>

                        <a
                            class="nav-link api-doc-link"
                            href="#doc-balance"
                        >
                            Saldo
                        </a>

                    </nav>

                </div>


                <div>

                    <small
                        class="text-muted text-uppercase fw-bold"
                    >
                        Referência
                    </small>

                    <nav class="nav flex-column mt-2">

                        <a
                            class="nav-link api-doc-link"
                            href="#doc-statuses"
                        >
                            Estados
                        </a>

                        <a
                            class="nav-link api-doc-link"
                            href="#doc-errors"
                        >
                            Erros
                        </a>

                    </nav>

                </div>

            </div>

        </div>


        {{-- CONTEÚDO --}}
        <div class="col-xl-9 col-lg-8">

            <div class="api-doc-content">

                @include(
                    'api-docs.sections.introduction'
                )

                @include(
                    'api-docs.sections.authentication'
                )

                @include(
                    'api-docs.sections.send'
                )

                @include(
                    'api-docs.sections.send-many'
                )

                @include(
                    'api-docs.sections.bulk'
                )

                @include(
                    'api-docs.sections.sms-list'
                )

                @include(
                    'api-docs.sections.sms-show'
                )

                @include(
                    'api-docs.sections.senders'
                )

                @include(
                    'api-docs.sections.balance'
                )

                @include(
                    'api-docs.sections.statuses'
                )

                @include(
                    'api-docs.sections.errors'
                )

            </div>

        </div>

    </div>

</div>