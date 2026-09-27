<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Carteira;
use App\Models\SenderId;
use App\Models\Sms;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SmsController extends Controller
{

    public function index(Request $request)
    {
        $contaId = (int) $request->attributes->get('conta_id');

        $request->validate([
            'status' => ['nullable', 'string'],
            'sender' => ['nullable', 'string', 'max:50'],
            'to' => ['nullable', 'string', 'max:20'],

            'from' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],

            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) $request->input('per_page', 50);
        $query = Sms::query()->where('conta_id', $contaId)->with('sender');

        /*
        * Estado público -> estado interno
        */
        if ($request->filled('status')) {

            $statusMap = [
                'PENDING'   => 'PENDENTE',
                'PROCESSING'=> 'PROCESSANDO',
                'SENT'      => 'ENVIADO',
                'DELIVERED' => 'ENTREGUE',
                'FAILED'    => 'FALHADO',
                'REJECTED'  => 'REJEITADO',
                'EXPIRED'   => 'EXPIRADO',
            ];

            $publicStatus = strtoupper($request->status);

            if (!isset($statusMap[$publicStatus])) {

                return response()->json([
                    'status' => 0,
                    'message' => 'O estado informado é inválido.',
                    'error' => [
                        'code' => 'INVALID_STATUS',
                    ],
                ], 422);
            }

            $query->where('estado', $statusMap[$publicStatus]);
        }

        /*
        * Sender
        */
        if ($request->filled('sender')) {

            $sender = strtoupper(trim($request->sender));

            $query->whereHas('sender', fn ($q) => $q->where('sender', $sender));
        }

        /*
        * Destinatário.
        *
        * Aceitamos 84... ou 25884...
        */
        if ($request->filled('to')) {

            $telefone = preg_replace('/\D/','',$request->to);

            if (strlen($telefone) === 9) {
                
                $telefone = '258' . $telefone;
            }

            $query->where('telefone', $telefone);
        }


        /*
        * Datas
        */
        if ($request->filled('from')) {

            $query->whereDate('created_at','>=', $request->from);
        }

        if ($request->filled('to_date')) {

            $query->whereDate('created_at','<=',$request->to_date);
        }

        $sms = $query->latest('id')->paginate($perPage);

        $data = collect(
            $sms->items()
        )->map(function ($item) {

            return [
                'id' => $item->id,
                'to' => $item->telefone,
                'sender' => $item->sender?->sender,
                'message' => $item->mensagem,
                'encoding' => $item->encoding,
                'characters' => $item->caracteres,
                'segments' => $item->segmentos,
                'sms_status' => $this->publicSmsStatus($item->estado),
                'sent_at' => $item->enviado_em?->toIso8601String(),
                'created_at' => $item->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => 1,
            'message' => 'SMS consultadas com sucesso.',
            'data' => $data,

            'meta' => [
                'current_page' => $sms->currentPage(),
                'per_page' => $sms->perPage(),
                'total' => $sms->total(),
                'last_page' => $sms->lastPage(),
                'from' => $sms->firstItem(),
                'to' => $sms->lastItem(),
            ],
        ]);
    }

    public function show(Request $request, int $id) {
        
        $contaId = (int) $request->attributes->get('conta_id');

        /*
        * A conta faz parte obrigatoriamente
        * da consulta.
        *
        * Assim uma API Key de outra empresa
        * nunca consegue consultar esta SMS.
        */
        $sms = Sms::query() ->where('conta_id', $contaId)->where('id', $id)->with('sender')->first();

        if (!$sms) {

            return response()->json([
                'status' => 0,
                'message' => 'SMS não encontrada.',
                'error' => [
                    'code' =>
                        'SMS_NOT_FOUND',
                ],

            ], 404);
        }

        return response()->json([
            'status' => 1,
            'message' => 'SMS consultada com sucesso.',
            'data' => [
                'id' => $sms->id,
                'to' => $sms->telefone,
                'sender' => $sms->sender?->sender,
                'message' => $sms->mensagem,
                'encoding' => $sms->encoding,
                'characters' => $sms->caracteres,
                'segments' => $sms->segmentos,
                'sms_status' => $this->publicSmsStatus($sms->estado),
                'sent_at' => $sms->enviado_em?->toIso8601String(),
                'delivered_at' => $sms->entregue_em ?->toIso8601String(),
                'created_at' => $sms->created_at ?->toIso8601String(),
            ],
        ]);
    }

    public function send(Request $request, SmsService $smsService) {
        try {

            $validated = $request->validate([
                'to' => ['required','string','max:20',],
                'sender' => ['required','string','max:50',],
                'message' => ['required','string','max:5000',],
            ]);

            /*
             * A conta NUNCA vem do JSON.
             * Ela vem da API Key.
             */
            $contaId = (int) $request->attributes->get('conta_id');
            $origem = 'API';

            /*
             * Procuramos o Sender dentro dos Senders
             * autorizados para esta conta.
             */
            $sender = SenderId::query()
                ->where('sender', $validated['sender'])
                ->where('estado', 'APROVADO')
                ->whereHas(
                    'contas',
                    function ($query) use ($contaId) {

                        $query
                            ->where('contas.id', $contaId)
                            ->where('conta_sender_ids.estado','ACTIVO');
                    }
                )
                ->first();


            if (!$sender) {

                return response()->json([
                    'status' => 0,
                    'message' => 'O Sender informado não está activo ou autorizado para esta conta.',
                    'error' => [
                        'code' => 'SENDER_NOT_ALLOWED',
                    ],
                ], 403);
            }

            /*
             * API não possui utilizador humano.
             * Por isso criado_por será null.
             *
             * Vamos ajustar SmsService para aceitar
             * ?int $userId.
             */
            $sms = $smsService->enviar($contaId, $sender->id, $validated['to'], $validated['message'], null, $origem);

            $saldo = Carteira::where('conta_id', $contaId)->value('saldo_sms');

            return response()->json([
                'status' => 1,
                'message' => 'SMS enviado com sucesso.',
                'data' => [
                    'id' => $sms->id,
                    'to' => $sms->telefone,
                    'sender' => $sender->sender,
                    'message' => $sms->mensagem,
                    'encoding' => $sms->encoding,
                    'characters' => $sms->caracteres,
                    'segments' => $sms->segmentos,
                    'sms_status' => 'SENT',
                    'balance' =>(int) ($saldo ?? 0),
                    'created_at' => $sms->created_at->toIso8601String(),
                ],
            ], 201);


        } catch (ValidationException $e) {

            return response()->json([
                'status' => 0,
                'message' => 'Os dados enviados são inválidos.',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],

                'errors' => $e->errors(),

            ], 422);


        } catch (RuntimeException $e) {

            /*
             * Por enquanto tratamos erros de negócio aqui.
             * Mais adiante criaremos Exceptions próprias.
             */
            $code = match ($e->getMessage()) {

                'Saldo SMS insuficiente.' => 'INSUFFICIENT_CREDITS',
                'Carteira não encontrada.' => 'WALLET_NOT_FOUND',
                'A carteira encontra-se bloqueada.' => 'WALLET_BLOCKED',
                'Número de telefone inválido.' => 'INVALID_PHONE',
                default
                    => 'SMS_SEND_FAILED',
            };


            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
                'error' => [
                    'code' => $code,
                ],
            ], 422);


        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' => 'Ocorreu um erro interno ao processar a SMS.',
                'error' => [
                    'code' =>'INTERNAL_ERROR',
                ],
            ], 500);
        }
    }

    public function sendMany(Request $request, SmsService $smsService) {
        
        try {

            $validated = $request->validate([
                'sender' => ['required','string','max:50',],
                'message' => ['required','string','max:5000',],
                'to' => ['required','array','min:1','max:1000',],
                'to.*' => ['required','string','max:20',
                ],
            ]);

            $contaId = (int) $request->attributes->get('conta_id');
            $sender = $this->obterSender($contaId, $validated['sender']);
            $origem = 'API';

            if (!$sender) {

                return response()->json([
                    'status' => 0,
                    'message' => 'O Sender informado não está activo ou autorizado para esta conta.',
                    'error' => [
                        'code' =>
                            'SENDER_NOT_ALLOWED',
                    ],

                ], 403);
            }

            $results = [];

            $sent = 0;
            $failed = 0;
            $totalSegments = 0;


            foreach ($validated['to'] as $telefone) {

                try {

                    $sms = $smsService->enviar($contaId, $sender->id, $telefone, $validated['message'], null, $origem);

                    $results[] = [
                        'id' => $sms->id,
                        'to' => $sms->telefone,
                        'sender' => $sender->sender,
                        'segments' => $sms->segmentos,
                        'sms_status' => 'SENT',
                    ];

                    $sent++;

                    $totalSegments += $sms->segmentos;

                } catch (Throwable $e) {

                    $failed++;

                    $results[] = [
                        'to' => $telefone,
                        'sender' => $sender->sender,
                        'sms_status' => 'FAILED',

                        'error' => [
                            'code' => $this->errorCode($e),
                            'message' => $e->getMessage(),
                        ],
                    ];
                }
            }

            $saldo = Carteira::where('conta_id', $contaId)->value('saldo_sms');

            return response()->json([
                'status' => 1,
                'message' => $failed > 0 ? 'Envio processado com algumas falhas.' : 'SMS enviadas com sucesso.',
                'data' => [
                    'recipients' => count($validated['to']),
                    'sent' => $sent,
                    'failed' => $failed,
                    'total_segments' => $totalSegments,
                    'balance' => (int) ($saldo ?? 0),
                    'results' => $results,
                ],

            ], 200);


        } catch (ValidationException $e) {

            return $this->validationError($e);


        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' => 'Não foi possível processar o envio.',
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                ],

            ], 500);
        }
    }

    public function bulk(Request $request, SmsService $smsService) {
        
        try {

            $validated = $request->validate([
                'messages' => ['required','array','min:1','max:1000',],
                'messages.*.to' => ['required','string','max:20',],
                'messages.*.sender' => ['required','string','max:50',],
                'messages.*.message' => ['required','string','max:5000',],
            ]);

            $contaId = (int) $request->attributes->get('conta_id');
            $origem = 'API';

            $results = [];

            $sent = 0;
            $failed = 0;
            $totalSegments = 0;

            /*
            * Cache dos Senders para não consultar
            * a BD repetidamente.
            */
            $senderCache = [];

            foreach ($validated['messages'] as $item) {

                try {

                    $senderName = strtoupper(trim($item['sender']));

                    if (!array_key_exists($senderName, $senderCache)) {

                        $senderCache[$senderName] = $this->obterSender($contaId, $senderName);
                    }

                    $sender = $senderCache[$senderName];

                    if (!$sender) {

                        throw new RuntimeException(
                            'Sender não autorizado.'
                        );
                    }

                    $sms = $smsService->enviar($contaId, $sender->id, $item['to'], $item['message'], null, $origem);

                    $results[] = [
                        'id' => $sms->id,
                        'to' => $sms->telefone,
                        'sender' => $sender->sender,
                        'segments' => $sms->segmentos,
                        'sms_status' => 'SENT',
                    ];

                    $sent++;

                    $totalSegments += $sms->segmentos;

                } catch (Throwable $e) {

                    $failed++;

                    $results[] = [
                        'to' => $item['to'],
                        'sender' => $item['sender'],
                        'sms_status' => 'FAILED',

                        'error' => [
                            'code' => $e->getMessage() === 'Sender não autorizado.' ? 'SENDER_NOT_ALLOWED' : $this->errorCode($e),
                            'message' => $e->getMessage(),
                        ],
                    ];
                }
            }

            $saldo = Carteira::where('conta_id', $contaId)->value('saldo_sms');

            return response()->json([
                'status' => 1,
                'message' => $failed > 0 ? 'Envio em massa processado com algumas falhas.' : 'Envio em massa processado com sucesso.',

                'data' => [
                    'total' => count($validated['messages']),
                    'sent' => $sent,
                    'failed' => $failed,
                    'total_segments' => $totalSegments,
                    'balance' => (int) ($saldo ?? 0),
                    'results' => $results,
                ],

            ], 200);


        } catch (ValidationException $e) {

            return $this->validationError($e);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => 0,
                'message' => 'Não foi possível processar o envio em massa.',
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                ],

            ], 500);
        }
    }

    //Obter Sender de Uma conta  ---Metodo Auxiliar---
    private function obterSender(int $contaId, string $sender): ?SenderId {

        return SenderId::query()

            ->where('sender', strtoupper(trim($sender)))
            ->where('estado', 'APROVADO')
            ->whereHas('contas',

                function ($query) use ($contaId) {

                    $query
                        ->where('contas.id', $contaId)
                        ->where('conta_sender_ids.estado', 'ACTIVO');
                }
            )
            ->first();
    }

    //Obter Erros ---Metodo Auxiliar---
    private function errorCode(Throwable $e): string {

        return match ($e->getMessage()) {

            'Saldo SMS insuficiente.' => 'INSUFFICIENT_CREDITS',
            'Carteira não encontrada.' => 'WALLET_NOT_FOUND',
            'A carteira encontra-se bloqueada.' => 'WALLET_BLOCKED',
            'Número de telefone inválido.' => 'INVALID_PHONE',

            default 
               => 'SMS_SEND_FAILED',
        };
    }

    //Validar os dados de fronte end ---Metodo Auxiliar---
    private function validationError(ValidationException $e) {

        return response()->json([
            'status' => 0,
            'message' => 'Os dados enviados são inválidos.',
            'error' => [
                'code' =>
                    'VALIDATION_ERROR',
            ],
            'errors' => $e->errors(),

        ], 422);
    }

    private function publicSmsStatus(string $status): string {

        return match ($status) {

            'PENDENTE' => 'PENDING',
            'PROCESSANDO' => 'PROCESSING',
            'ENVIADO' => 'SENT',
            'ENTREGUE' => 'DELIVERED',
            'FALHADO' => 'FAILED',
            'REJEITADO' => 'REJECTED',
            'EXPIRADO' => 'EXPIRED',

            default =>
                'UNKNOWN',
        };
    }
}