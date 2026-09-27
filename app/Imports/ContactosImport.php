<?php

namespace App\Imports;

use App\Models\Contacto;
use App\Models\GrupoContacto;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ContactosImport implements ToCollection, WithHeadingRow
{
    protected int $contaId;
    protected ?int $grupoId;
    protected int $total = 0;
    protected int $importados = 0;
    protected int $duplicados = 0;
    protected int $invalidos = 0;
    protected array $erros = [];

    public function __construct(int $contaId, ?int $grupoId = null) {
        $this->contaId = $contaId;
        $this->grupoId = $grupoId;
    }


    public function collection(Collection $rows)
    {
        /*
         * Validar novamente o grupo por segurança.
         * Nunca confiamos apenas no grupo_id enviado pelo browser.
         */
        $grupo = null;

        if ($this->grupoId) {

            $grupo = GrupoContacto::query()
                ->where('conta_id', $this->contaId)
                ->where('estado', 'ACTIVO')
                ->find($this->grupoId);
        }


        foreach ($rows as $index => $row) {

            /*
             * +2 porque:
             *
             * linha 1 = cabeçalho
             * index 0 = linha 2
             */
            $numeroLinha = $index + 2;

            /*
             * Ignorar linha completamente vazia.
             */
            if (blank($row['nome'] ?? null) && blank($row['telefone'] ?? null) && blank($row['email'] ?? null) && blank($row['data_nascimento'] ?? null)) {
                continue;
            }

            $this->total++;

            /*
            |--------------------------------------------------------------------------
            | NORMALIZAR DADOS
            |--------------------------------------------------------------------------
            */
            $nome = trim((string) ($row['nome'] ?? ''));
            $telefone = $this->normalizarTelefone($row['telefone'] ?? null);
            $email = trim((string) ($row['email'] ?? ''));
            $email = $email !== '' ? $email : null;

            /*
            |--------------------------------------------------------------------------
            | DATA DE NASCIMENTO
            |--------------------------------------------------------------------------
            */
            try {

                $dataNascimento = $this->converterDataNascimento( $row['data_nascimento'] ?? null);

            } catch (\Throwable $e) {

                $this->invalidos++;

                $this->erros[] = [
                    'linha' => $numeroLinha,
                    'erro' => 'Data de nascimento inválida. Use DD-MM-AAAA.'
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDAR LINHA
            |--------------------------------------------------------------------------
            */
            $validator = Validator::make(
                [
                    'nome' => $nome,
                    'telefone' => $telefone,
                    'email' => $email,
                    'data_nascimento' => $dataNascimento,
                ],
                [
                    'nome' => ['required','string','max:150',],
                    'telefone' => ['required','string','max:20',],
                    'email' => ['nullable','email','max:150',],
                    'data_nascimento' => ['nullable','date','before_or_equal:today',],
                ]
            );

            if ($validator->fails()) {

                $this->invalidos++;
                $this->erros[] = [
                    'linha' => $numeroLinha,
                    'erro' => $validator->errors()->first(),
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | VERIFICAR DUPLICADO
            |--------------------------------------------------------------------------
            */
            $contactoExistente = Contacto::query()->where('conta_id', $this->contaId)->where('telefone', $telefone)->first();

            if ($contactoExistente) {

                $this->duplicados++;

                /*
                 * Mesmo sendo duplicado, se o utilizador
                 * selecionou um grupo, associamos o contacto
                 * existente ao grupo.
                 */
                if ($grupo) {

                    $contactoExistente->grupos()->syncWithoutDetaching([$grupo->id]);
                }

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | CRIAR CONTACTO
            |--------------------------------------------------------------------------
            */
            $contacto = Contacto::create([
                'conta_id' => $this->contaId,
                'nome' => $nome,
                'telefone' => $telefone,
                'email' => $email,
                'data_nascimento' => $dataNascimento,
                'estado' => 'ACTIVO',
            ]);


            /*
            |--------------------------------------------------------------------------
            | ASSOCIAR AO GRUPO
            |--------------------------------------------------------------------------
            */
            if ($grupo) {

                $contacto->grupos()->syncWithoutDetaching([$grupo->id]);
            }

            $this->importados++;
        }
    }


    /**
     * Normalizar telefone.
     */
    private function normalizarTelefone($telefone): string
    {
        $telefone = trim(
            (string) $telefone
        );

        /*
         * Retirar espaços, +, -, parênteses etc.
         */
        $telefone = preg_replace(
            '/\D+/',
            '',
            $telefone
        );


        /*
         * Ex:
         *
         * +258841234567
         * 258841234567
         *
         * passa para:
         *
         * 841234567
         */
        if (
            strlen($telefone) === 12 &&
            str_starts_with(
                $telefone,
                '258'
            )
        ) {
            $telefone = substr(
                $telefone,
                3
            );
        }


        return $telefone;
    }


    /**
     * Converter a data recebida do Excel para Y-m-d.
     *
     * O utilizador trabalha com:
     *
     * DD-MM-AAAA
     *
     * Ex:
     * 25-09-1995
     *
     * BD:
     * 1995-09-25
     */
    private function converterDataNascimento($valor): ?string
    {
        if (
            $valor === null ||
            trim((string) $valor) === ''
        ) {
            return null;
        }


        /*
         * O Excel às vezes devolve datas como número serial.
         *
         * Ex:
         * 35000
         */
        if (is_numeric($valor)) {

            return ExcelDate::excelToDateTimeObject(
                $valor
            )->format('Y-m-d');
        }


        $valor = trim(
            (string) $valor
        );


        /*
         * Exigir exactamente DD-MM-AAAA.
         */
        $data = Carbon::createFromFormat(
            'd-m-Y',
            $valor
        );


        /*
         * Evitar datas que Carbon eventualmente
         * normalize silenciosamente.
         *
         * Ex:
         * 40-15-2020
         */
        if (
            !$data ||
            $data->format('d-m-Y') !== $valor
        ) {
            throw new \Exception(
                'Data inválida.'
            );
        }


        return $data->format('Y-m-d');
    }

    /*
    |--------------------------------------------------------------------------
    | ESTATÍSTICAS
    |--------------------------------------------------------------------------
    */
    public function getTotal(): int
    {
        return $this->total;
    }

    public function getImportados(): int
    {
        return $this->importados;
    }

    public function getDuplicados(): int
    {
        return $this->duplicados;
    }

    public function getInvalidos(): int
    {
        return $this->invalidos;
    }

    public function getErros(): array
    {
        return $this->erros;
    }
}