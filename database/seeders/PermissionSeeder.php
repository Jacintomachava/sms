<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            /*
            |--------------------------------------------------------------------------
            | ACCOUNT - SMS
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'sms.view',
                'nome' => 'Visualizar SMS',
                'modulo' => 'SMS',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite visualizar mensagens SMS.',
            ],
            [
                'codigo' => 'sms.send',
                'nome' => 'Enviar SMS',
                'modulo' => 'SMS',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite enviar mensagens SMS.',
            ],

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT - CAMPANHAS
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'campaigns.view',
                'nome' => 'Visualizar campanhas',
                'modulo' => 'CAMPANHAS',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite visualizar campanhas.',
            ],
            [
                'codigo' => 'campaigns.manage',
                'nome' => 'Gerir campanhas',
                'modulo' => 'CAMPANHAS',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite criar, alterar e gerir campanhas.',
            ],

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT - CONTACTOS
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'contacts.view',
                'nome' => 'Visualizar contactos',
                'modulo' => 'CONTACTOS',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite visualizar contactos.',
            ],
            [
                'codigo' => 'contacts.manage',
                'nome' => 'Gerir contactos',
                'modulo' => 'CONTACTOS',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite criar, alterar e gerir contactos.',
            ],

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT - API
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'api_keys.view',
                'nome' => 'Visualizar chaves API',
                'modulo' => 'API',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite visualizar as chaves API da conta.',
            ],
            [
                'codigo' => 'api_keys.manage',
                'nome' => 'Gerir chaves API',
                'modulo' => 'API',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite criar e revogar chaves API.',
            ],

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT - SANDBOX
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'sandbox.use',
                'nome' => 'Utilizar Sandbox',
                'modulo' => 'SANDBOX',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite utilizar o ambiente Sandbox.',
            ],

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT - WEBHOOKS
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'webhooks.view',
                'nome' => 'Visualizar Webhooks',
                'modulo' => 'WEBHOOKS',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite visualizar Webhooks.',
            ],
            [
                'codigo' => 'webhooks.manage',
                'nome' => 'Gerir Webhooks',
                'modulo' => 'WEBHOOKS',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite configurar e gerir Webhooks.',
            ],

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT - SENDER IDS
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'senders.view',
                'nome' => 'Visualizar Sender IDs',
                'modulo' => 'SENDERS',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite visualizar Sender IDs.',
            ],
            [
                'codigo' => 'senders.request',
                'nome' => 'Solicitar Sender ID',
                'modulo' => 'SENDERS',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite solicitar um Sender ID próprio.',
            ],

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT - FACTURAÇÃO
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'billing.view',
                'nome' => 'Visualizar facturação',
                'modulo' => 'FACTURACAO',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite consultar facturas, pagamentos e saldo.',
            ],
            [
                'codigo' => 'billing.purchase',
                'nome' => 'Comprar SMS',
                'modulo' => 'FACTURACAO',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite efectuar compras de créditos SMS.',
            ],

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT - UTILIZADORES
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'users.view',
                'nome' => 'Visualizar utilizadores',
                'modulo' => 'UTILIZADORES',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite visualizar membros da conta.',
            ],
            [
                'codigo' => 'users.manage',
                'nome' => 'Gerir utilizadores',
                'modulo' => 'UTILIZADORES',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite convidar e gerir membros da conta.',
            ],

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT - CONFIGURAÇÕES
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'settings.view',
                'nome' => 'Visualizar configurações',
                'modulo' => 'CONFIGURACOES',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite visualizar configurações da conta.',
            ],
            [
                'codigo' => 'settings.manage',
                'nome' => 'Gerir configurações',
                'modulo' => 'CONFIGURACOES',
                'scope' => 'ACCOUNT',
                'descricao' => 'Permite alterar configurações da conta.',
            ],

            /*
            |--------------------------------------------------------------------------
            | PLATFORM - CONTAS
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'platform.accounts.view',
                'nome' => 'Visualizar contas',
                'modulo' => 'CONTAS',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite visualizar contas de clientes.',
            ],
            [
                'codigo' => 'platform.accounts.manage',
                'nome' => 'Gerir contas',
                'modulo' => 'CONTAS',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite gerir contas de clientes.',
            ],

            /*
            |--------------------------------------------------------------------------
            | PLATFORM - UTILIZADORES
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'platform.users.view',
                'nome' => 'Visualizar utilizadores',
                'modulo' => 'UTILIZADORES',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite visualizar utilizadores da plataforma.',
            ],
            [
                'codigo' => 'platform.users.manage',
                'nome' => 'Gerir utilizadores',
                'modulo' => 'UTILIZADORES',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite gerir utilizadores da plataforma.',
            ],

            /*
            |--------------------------------------------------------------------------
            | PLATFORM - SMS
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'platform.sms.view',
                'nome' => 'Visualizar SMS',
                'modulo' => 'SMS',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite visualizar mensagens processadas pela plataforma.',
            ],

            /*
            |--------------------------------------------------------------------------
            | PLATFORM - SENDERS
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'platform.senders.view',
                'nome' => 'Visualizar Sender IDs',
                'modulo' => 'SENDERS',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite visualizar solicitações de Sender ID.',
            ],
            [
                'codigo' => 'platform.senders.manage',
                'nome' => 'Gerir Sender IDs',
                'modulo' => 'SENDERS',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite aprovar, rejeitar e gerir Sender IDs.',
            ],

            /*
            |--------------------------------------------------------------------------
            | PLATFORM - PAGAMENTOS
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'platform.payments.view',
                'nome' => 'Visualizar pagamentos',
                'modulo' => 'PAGAMENTOS',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite visualizar pagamentos.',
            ],
            [
                'codigo' => 'platform.payments.manage',
                'nome' => 'Gerir pagamentos',
                'modulo' => 'PAGAMENTOS',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite gerir operações de pagamentos.',
            ],

            /*
            |--------------------------------------------------------------------------
            | PLATFORM - STOCK
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'platform.stock.view',
                'nome' => 'Visualizar stock SMS',
                'modulo' => 'STOCK',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite visualizar o stock de SMS da operadora.',
            ],
            [
                'codigo' => 'platform.stock.manage',
                'nome' => 'Gerir stock SMS',
                'modulo' => 'STOCK',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite registar e gerir stock de SMS.',
            ],

            /*
            |--------------------------------------------------------------------------
            | PLATFORM - PROVIDERS
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'platform.providers.view',
                'nome' => 'Visualizar provedores',
                'modulo' => 'PROVIDERS',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite visualizar provedores SMS.',
            ],
            [
                'codigo' => 'platform.providers.manage',
                'nome' => 'Gerir provedores',
                'modulo' => 'PROVIDERS',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite configurar provedores SMS.',
            ],

            /*
            |--------------------------------------------------------------------------
            | PLATFORM - CONFIGURAÇÕES
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'platform.settings.view',
                'nome' => 'Visualizar configurações',
                'modulo' => 'CONFIGURACOES',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite visualizar configurações da plataforma.',
            ],
            [
                'codigo' => 'platform.settings.manage',
                'nome' => 'Gerir configurações',
                'modulo' => 'CONFIGURACOES',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite alterar configurações da plataforma.',
            ],

            /*
            |--------------------------------------------------------------------------
            | PLATFORM - AUDITORIA
            |--------------------------------------------------------------------------
            */
            [
                'codigo' => 'platform.audit.view',
                'nome' => 'Visualizar auditoria',
                'modulo' => 'AUDITORIA',
                'scope' => 'PLATFORM',
                'descricao' => 'Permite consultar os registos de auditoria.',
            ],
        ];

        foreach ($permissions as $permission) {

            Permission::updateOrCreate(
                [
                    'codigo' => $permission['codigo'],
                ],
                [
                    'nome' => $permission['nome'],
                    'modulo' => $permission['modulo'],
                    'scope' => $permission['scope'],
                    'descricao' => $permission['descricao'],
                    'activo' => true,
                ]
            );
        }
    }
}