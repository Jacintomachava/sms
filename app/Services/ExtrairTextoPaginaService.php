<?php

namespace App\Services;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ExtrairTextoPaginaService
{
    //Extrair Texto de Uma Pagina Web a partir de um link
    function extrairTextoDaPagina(string $url): string
    {
        $client = new Client();
        $response = $client->get($url);
        $html = (string) $response->getBody();

        $crawler = new Crawler($html);
        
        // Remove scripts, nav, footer, etc.
        $crawler->filter('script, style, nav, footer')->each(function ($node) {
            $node->getNode(0)->parentNode->removeChild($node->getNode(0));
        });

        $text = $crawler->filter('body')->text(null, true);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

}
