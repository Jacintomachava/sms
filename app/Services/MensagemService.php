<?php

namespace App\Services;

use App\Models\SMSDestinatario;
use App\Models\AlunoRelacaoPessoa;
use App\Models\SaldoSMS;
use App\Models\AnoAcademico;
use App\Models\MensagemSessao;
use App\Models\Mensagem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Twilio\Rest\Client;
use App\Models\Aluno; // Modelo dos alunos
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MensagemService
{

    //No tipo e para identificar se e Recebido ou ENviado
    //Na canal e para dizer se e WhatsApp ou SMS
    public static function mensagem($telefone,$mensagem1,$qtd,$tipo,$canal,$interativo)
    {

        try {

            DB::beginTransaction();

                $inicioDoMes = Carbon::now()->startOfMonth();
                $fimDoMes = Carbon::now()->endOfMonth();

                $creditoFree = Mensagem::where('canal', 'WhatsApp')
                                    ->whereBetween('created_at', [$inicioDoMes, $fimDoMes])
                                    ->count();

                $saldoCredito = SaldoSMS::where('codigo','saldo')->first();

                $credito = 0;

                $mensagem = new Mensagem();
                $mensagem->telefone = substr($telefone, -9);
                $mensagem->descricao = $mensagem1;
                $mensagem->tipo = $tipo;
                $mensagem->qtd = $qtd;
                $mensagem->canal = $canal;

                //$mensagem->custo_real = $canal;
                
                if($canal=='SMS'){
                    $credito = $qtd*1.5;
                    $saldoCredito->saldo = $saldoCredito->saldo - $credito;
                    $mensagem->custo_real = 1.2;
                }

                if($canal=='WhatsApp' && $tipo=='Enviada' && $interativo==1){
                    $credito = 0.20;
                    $saldoCredito->saldo = $saldoCredito->saldo - $credito;

                    if ($creditoFree < 4000) {
                        $mensagem->custo_real = 0.0;
                    } else {
                        $mensagem->custo_real = 0.15;
                    }
                    
                }

                if($canal=='WhatsApp' && $tipo=='Recebida' && $interativo==1){
                    $credito = 0.20;
                    $saldoCredito->saldo = $saldoCredito->saldo - $credito;
                   
                    if ($creditoFree < 4000) {
                        $mensagem->custo_real = 0.0;
                    } else {
                        $mensagem->custo_real = 0.15;
                    }
                }

                if($canal=='WhatsApp' && $tipo=='Enviada' && $interativo==0){
                    $credito = 2.5;
                    $saldoCredito->saldo = $saldoCredito->saldo - $credito;
                    $mensagem->custo_real = 0.005*64.6+0.0225*64.6;  //Preco Mensagem * dolar cambio de 64.6
                }
                
                $mensagem->credito = $credito;
                

                if($mensagem->save() && $saldoCredito->save())
                {
                    // Atualizando a sessão do usuário
                    MensagemSessao::updateOrCreate(
                        ['telefone' => substr($telefone, -9)], // Critério de busca
                        [
                            'ultima_conversa' => $mensagem1,
                            'conversas' => DB::raw('conversas + 1'), // Incrementar conversas
                            'created_at' => now() // Atualizar data de criação quando necessário
                        ]
                    );

                    DB::commit();  // 🔥 Faltava isso para confirmar a transação
                    \Log::info("Mensagem Enviada com Sucesso Gravar Mensagem no Servico");

                    return true;
                }

                \Log::info("Ocorreu um erro");

                return false;

            } catch (\Exception $e) {

                DB::rollBack();

                \Log::info("Ocorreu um erro ".$e->getMessage());
               
                return false;
            }

    }

    

}
