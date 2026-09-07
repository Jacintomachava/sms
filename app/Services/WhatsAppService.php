<?php

namespace App\Services;

use App\Models\SMSDestinatario;
use App\Models\AlunoRelacaoPessoa;
use App\Models\SaldoSMS;
use App\Models\AnoAcademico;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Twilio\Rest\Client;
use App\Models\Aluno; // Modelo dos alunos
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WhatsAppService
{

    protected $token;
    protected $phoneNumberId;

    public function __construct()
    {
        $this->token = env('WHATSAPP_TOKEN'); // coloque no .env
        $this->phoneNumberId = env('WHATSAPP_PHONE_ID'); // coloque no .env
    }

    public function enviarTexto($numero, $mensagem)
    {
        $numeroFormatado = str_replace(['+', ' '], '', $numero); // e.g. 258840000000

        $response = Http::withToken($this->token)
            ->post("https://graph.facebook.com/v19.0/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $numeroFormatado,
                'type' => 'text',
                'text' => [
                    'body' => $mensagem
                ],
            ]);

        return $response->json();
    }

}
