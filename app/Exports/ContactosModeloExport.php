<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ContactosModeloExport implements
    FromArray,
    WithHeadings,
    WithColumnFormatting
{
    /**
     * Cabeçalhos do Excel
     */
    public function headings(): array
    {
        return [
            'nome',
            'telefone',
            'email',
            'data_nascimento',
        ];
    }

    /**
     * Linhas de exemplo
     */
    public function array(): array
    {
        return [
            [
                'João Manuel',
                '841234567',
                'joao@email.com',
                '25-09-1995',
            ],
            [
                'Maria José',
                '871234567',
                '',
                '10-03-2000',
            ],
        ];
    }

    /**
     * Formatação das colunas
     */
    public function columnFormats(): array
    {
        return [
            // Telefone como texto
            'B' => NumberFormat::FORMAT_TEXT,

            // Data como texto para preservar DD-MM-AAAA
            'D' => NumberFormat::FORMAT_TEXT,
        ];
    }
}