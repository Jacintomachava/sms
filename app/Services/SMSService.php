<?php

namespace App\Services;

use App\Models\AlunoRelacaoPessoa;
use App\Models\AnoAcademico;
use App\Models\SaldoSMS;
use App\Models\SMSDestinatario;

class SMSService
{
    /**
     * Envia uma mensagem SMS usando a API da MozeSMS.
     *
     * @param string $telefone Número de telefone do destinatário (com código de país, ex.: +258...)
     * @param string $mensagem conteúdo da mensagem a ser enviada
     *
     * @return array resposta da API usando Moz SMS
     */
    public static function sendSMS1($telefone, $mensagem, $sender)
    {
        $curl = curl_init();

        $param = [
            'from' => 'LHAYSSO', // ID do remetente (Sender ID)
            'to' => '+258'.$telefone, // Número de telefone do destinatário
            'message' => $mensagem, // Mensagem que deseja enviar
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => 'http://api.mozesms.com/message/v2',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $param,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer 135:35LM0y-4eEAYk-miUkhG-YQ5nfC',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
    }

    /**
     * Envia uma mensagem SMS usando a API da MozeSMS.
     *
     * @param string $telefone Número de telefone do destinatário (com código de país, ex.: +258...)
     * @param string $mensagem conteúdo da mensagem a ser enviada
     *
     * @return array resposta da API Usando Airtrim
     */
    public static function sendSMS($telefone, $mensagem, $sender)
    {

        $url = "https://api.airtexts.com/sms/send-message";
        $bearerToken = 'M2NiNDEzMjIxNjU4NjM2ZmUxNTc5YzNiM2M0NGQyOWZiY2VlYmY5NDE3OGE4NjA2YTUzYTIyNjdlYmY4MGVmMi0wMjkzY2E2NjNlZGY2MmMzOWQ1OTI1OWJiNzkyYmUyODI5Y2FmMThkOGY3M2FhNDdkMTBkOGMyZTkzMDg1MzYyYTJkNjg0NzQ3NGJhZGE4ZGJm';

        $data = [
            "sender" => "INFOMSG",
            "recipient" => '+258'.$telefone,
            "message" => $mensagem
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $bearerToken",
            'Content-Type: application/json'
        ]);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode === 200) {
            \Log::info("Mensagem Enviada com Sucesso $response");
            return true; // ✅ Sucesso
        } else {
            \Log::error("HTTP Error: $httpCode\nResponse: $response\n");
            return false; // ❌ Falhou
        }


    }

     /**
     * Envia uma mensagem SMS usando a API da MozeSMS.
     *
     * @param string $telefone Número de telefone do destinatário (com código de país, ex.: +258...)
     * @param string $mensagem conteúdo da mensagem a ser enviada
     *
     * @return array resposta da API Usando Airtrim
     */
    public static function sendSMS4($telefone, $mensagem, $sender)
    {

        //Usando MOZSMS
        $curl = curl_init();

        $data = json_encode([
            'phone' => '+258'.$telefone,
            'message' => $mensagem,
            'sender_id' => 'LHAYSSO'
        ]);

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.mozesms.com/v2/sms/send',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer 135:35LM0y-4eEAYk-miUkhG-YQ5nfC',
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode === 200) {
            //echo "$response\n";
            \Log::info("Mensagem Enviada com Sucesso $response");
            return true; // ✅ Sucesso
        } else {
            //echo "HTTP Error: $httpCode\nResponse: $response\n";
            \Log::info("HTTP Error: $httpCode\nResponse: $response\n ");
            return false; // ❌ Falhou
        }


    }


    public static function decrimentoSaldoSMS($quantidade)
    {
        $saldo = SaldoSMS::where('codigo', 'saldo')->first();
        $saldo->saldo = $saldo->saldo - $quantidade;

        if ($saldo->save()) {
            return true;
        }

        return false;
    }

    public static function saldoSMS()
    {
        $saldo = SaldoSMS::where('codigo', 'saldo')->first();

        return $saldo->saldo;
    }


    public static function quantidadeSMS($sms)
    {
        $messageText = $sms;
        $isGSM7Bit = self::isGSM7Bit($messageText);
        $charsPerSegment = $isGSM7Bit ? 153 : 67;
        $messageCount = ceil(strlen($messageText) / $charsPerSegment);
        $encoding = $isGSM7Bit ? 'GSM_7BIT' : 'Unicode';

        return $messageCount;
    }

    // Função para verificar se o texto é GSM_7BIT
    private static function isGSM7Bit($text)
    {
        $gsm7bitRegex = '/^[\x20-\x7E\x0A\x0D\xC0-\xC6\xC8-\xCB\xCC-\xCF\xD2-\xD6\xD8-\xDD\xE0-\xE6\xE8-\xEB\xEC-\xEF\xF2-\xF6\xF8-\xFD\xDF]+$/';

        return preg_match($gsm7bitRegex, $text);
    }
}
