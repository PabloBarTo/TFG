<?php

namespace Drupal\global_page_translate\Service;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;

class TranslateService {

  protected $httpClient;
  protected $config;

  public function __construct(Client $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('auto_translate.settings');
  }

  public function translateHtml($html, $targetLang) {
    $apiKey = 'cae967dc-368d-45da-a380-c2ba435ec871:fx';
    $url = "https://api-free.deepl.com/v2/translate";
    $targetLang = strtoupper($targetLang);

    // 🔄 Envolver atributos traducibles para que DeepL no los ignore
    $html = preg_replace_callback(
      '/\b(value|placeholder)=("|\')([^"\']+)\2/i',
      function ($matches) {
        // $matches[1] = atributo (value o placeholder)
        // $matches[2] = comilla usada
        // $matches[3] = contenido del atributo
        return "{$matches[1]}={$matches[2]}__TRANSLATE_OPEN__{$matches[3]}__TRANSLATE_CLOSE__{$matches[2]}";
      },
      $html
    );

    // 🔀 Dividir HTML en fragmentos seguros para la API
    $parts = preg_split(
      '/(<\/p>|<\/div>|<br\s*\/?>|<\/tr>|<\/th>)/i',
      $html,
      -1,
      PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
    );
    $chunks = [];
    $currentChunk = '';

    foreach ($parts as $part) {
      if (strlen($currentChunk . $part) < 10000) {
        $currentChunk .= $part;
      } else {
        $chunks[] = $currentChunk;
        $currentChunk = $part;
      }
    }
    if (!empty($currentChunk)) {
      $chunks[] = $currentChunk;
    }

    $translatedHtml = '';

    try {
      foreach ($chunks as $chunk) {
        $response = $this->httpClient->post($url, [
          'form_params' => [
            'auth_key' => $apiKey,
            'text' => $chunk,
            'target_lang' => $targetLang,
            'tag_handling' => 'xml',
            'ignore_tags' => 'script,style',
          ],
        ]);

        $body = json_decode($response->getBody()->getContents(), TRUE);
        $translatedChunk = $body['translations'][0]['text'] ?? 'Error al traducir fragmento';
        $translatedHtml .= $translatedChunk;
      }

      // 🔁 Restaurar atributos previamente envueltos
      $translatedHtml = preg_replace_callback(
        '/\b(value|placeholder)=("|\')__TRANSLATE_OPEN__(.*?)__TRANSLATE_CLOSE__\2/i',
        function ($matches) {
          return "{$matches[1]}={$matches[2]}{$matches[3]}{$matches[2]}";
        },
        $translatedHtml
      );

    } catch (\Exception $e) {
      \Drupal::logger('global_page_translate')->error('DeepL API error: @message', ['@message' => $e->getMessage()]);
      $translatedHtml = '<p>Error al conectar con el servicio de traducción: ' . $e->getMessage() . '</p>';
    }

    $replacements = [
      'value="Aplicar"' => 'value="Apply"',
      'placeholder="Buscar una película"' => 'placeholder="Search for a movie"',
      // Añade aquí cualquier otro caso conocido que no se traduzca automáticamente
    ];
    
    $translatedHtml = strtr($translatedHtml, $replacements);

    return $translatedHtml;
  }
}
