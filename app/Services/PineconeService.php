<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PineconeService
{
    protected $pineconeApiKey;
    protected $pineconeEnv;
    protected $pineconeIndex;
    protected $openaiKey;
    protected $pineconeHost;

    public function __construct()
    {
        $this->pineconeApiKey = config('services.pinecone.key');
        $this->pineconeEnv = config('services.pinecone.env');
        $this->pineconeIndex = config('services.pinecone.index');
        $this->openaiKey = config('services.openai.key');
        $this->pineconeHost = 'https://lhaysso-index-5nh5tsv.svc.aped-4627-b74a.pinecone.io';
    }

    public function embed(string $text)
    {
        $response = Http::withToken($this->openaiKey)
            ->timeout(30)
        ->post('https://api.openai.com/v1/embeddings', [
            'input' => $text,
            'model' => 'text-embedding-3-small', // Modelo recomendado
        ]);

        return $response->json('data.0.embedding'); // Extrai o vetor
    }

    public function upsert(string $id, string $text): array
    {
        try {
            $vector = $this->embed($text);

            $response = Http::withHeaders([
                'Api-Key' => $this->pineconeApiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->pineconeHost.'/vectors/upsert', [
                'vectors' => [
                    [
                        'id' => $id,
                        'values' => $vector,
                        'metadata' => [
                            'text' => $text,
                            'timestamp' => now()->toDateTimeString(),
                        ],
                    ],
                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Erro Pinecone: '.$response->status().' - '.$response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Falha completa no upsert', [
                'error' => $e->getMessage(),
                'host' => $this->pineconeHost,
                'api_key' => substr($this->pineconeApiKey, 0, 5).'...', // Log parcial da chave
            ]);
            throw new \Exception('Erro ao fazer upsert: '.$e->getMessage());
        }
    }

    public function upsertWeb(string $id, string $url): array
    {
        try {
            $vector = $this->embed($url);

            $response = Http::withHeaders([
                'Api-Key' => $this->pineconeApiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->pineconeHost.'/vectors/upsert', [
                'vectors' => [
                     [
                        'id' => $id,
                        'values' => $vector,
                        'metadata' => [
                            'source' => $url,
                            'type' => 'web',
                            'timestamp' => now()->toDateTimeString(),
                        ]
                     ],

                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Erro Pinecone: '.$response->status().' - '.$response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Falha completa no upsert', [
                'error' => $e->getMessage(),
                'host' => $this->pineconeHost,
                'api_key' => substr($this->pineconeApiKey, 0, 5).'...', // Log parcial da chave
            ]);
            throw new \Exception('Erro ao fazer upsert: '.$e->getMessage());
        }
    }

    public function query(string $queryText): array
    {
        $vector = $this->embed($queryText);

        $response = Http::withHeaders([
            'Api-Key' => $this->pineconeApiKey,
            'Content-Type' => 'application/json',
        ])->post($this->pineconeHost.'/query', [
            'vector' => $vector,
            'topK' => 1,
            'includeMetadata' => true,
            'includeValues' => false,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao consultar Pinecone: '.$response->status().' - '.$response->body());
        }

        $match = $response->json('matches.0');

        if (!$match || empty($match['metadata']['text'])) {
            return [
                'text' => 'Desculpe, nenhum conteúdo relevante foi encontrado.',
                'score' => 0,
                'id' => null,
            ];
        }

        return [
            'text' => $match['metadata']['text'],
            'score' => $match['score'],
            'id' => $match['id'],
        ];
    }

    /*public function query(string $queryText)
    {
        $vector = $this->embed($queryText);

        $host = 'https://lhaysso-index-5nh5tsv.svc.aped-4627-b74a.pinecone.io';

        $response = Http::withHeaders([
            'Api-Key' => $this->pineconeApiKey,
            'Content-Type' => 'application/json',
        ])->post("$host/query", [
            'vector' => $vector,
            'topK' => 1,
            'includeMetadata' => true,
        ]);

        return $response->json('matches.0.metadata.text') ?? 'Desculpe, não encontrei resposta nos documentos.';
    }*/
}
