<?php

namespace App\Services;

class SmsSegmentService
{
    /**
     * GSM 03.38 - alfabeto básico.
     *
     * Cada carácter desta tabela consome 1 septet.
     */
    private const GSM7_BASIC =
        "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ" .
        "ÆæßÉ !\"#¤%&'()*+,-./" .
        "0123456789:;<=>?" .
        "¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§" .
        "¿abcdefghijklmnopqrstuvwxyzäöñüà";

    /**
     * GSM 03.38 - tabela estendida.
     *
     * Estes caracteres são válidos em GSM-7,
     * mas consomem 2 septets.
     */
    private const GSM7_EXTENDED = "^{}\\[~]|€";


    public function calcular(string $texto): array
    {
        $caracteres = mb_strlen($texto, 'UTF-8');

        $septets = $this->contarSeptetsGsm7($texto);

        if ($septets !== null) {

            $segmentos = $septets <= 160 ? 1 : (int) ceil($septets / 153);

            return [
                'encoding' => 'GSM7',
                'caracteres' => $caracteres,
                'unidades' => $septets,
                'segmentos' => $segmentos,
            ];
        }

        /*
         * Unicode/UCS-2.
         *
         * Para o nosso cálculo comercial:
         * 70 caracteres = 1 SMS
         * multipart = 67 por segmento
         */
        $segmentos = $caracteres <= 70 ? 1 : (int) ceil($caracteres / 67);

        return [
            'encoding' => 'UNICODE',
            'caracteres' => $caracteres,
            'unidades' => $caracteres,
            'segmentos' => $segmentos,
        ];
    }


    private function contarSeptetsGsm7(string $texto): ?int
    {
        $chars = preg_split('//u', $texto, -1, PREG_SPLIT_NO_EMPTY);

        if ($chars === false) {
            return null;
        }

        $septets = 0;

        foreach ($chars as $char) {

            /*
             * Tabela básica:
             * 1 septet.
             */
            if (mb_strpos(
                self::GSM7_BASIC,
                $char,
                0,
                'UTF-8'
            ) !== false) {

                $septets++;

                continue;
            }


            /*
             * Tabela estendida:
             * ESC + carácter = 2 septets.
             */
            if (mb_strpos(
                self::GSM7_EXTENDED,
                $char,
                0,
                'UTF-8'
            ) !== false) {

                $septets += 2;

                continue;
            }

            /*
             * Encontrámos um carácter fora
             * do GSM 03.38.
             */
            return null;
        }

        return $septets;
    }
}