<?php

namespace Drupal\global_page_translate\Service;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Cache\CacheBackendInterface;

class TranslateService {

  protected $httpClient;
  protected $config;
  protected $stringTranslation;
  protected $cache;

  public function __construct(Client $http_client, ConfigFactoryInterface $config_factory, TranslationInterface $string_translation, CacheBackendInterface $cache) {
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('auto_translate.settings');
    $this->stringTranslation = $string_translation;
    $this->cache = $cache;
  }

  public function translateHtml($html, $targetLang) {
    $apiKey = '0a8115d7-6895-4ffd-900a-243ccc610d38:fx';
    $url = "https://api-free.deepl.com/v2/translate";
    $targetLang = strtoupper($targetLang);

    $currentLang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if ($currentLang !== 'es') {
      return $html;
    }

    $originalHtml = $html;

    // Evitar traducir si ya contiene traducciones típicas
    /*if (str_contains($html, 'Save') && str_contains($html, 'Preview')) {
      return $html;
    }*/

    // Verificar caché
    $hash = 'cache_translate:' . md5($html . $targetLang);
    if ($cached = $this->cache->get($hash)) {
      return $cached->data;
    }

    // Envolver atributos traducibles
    $html = preg_replace_callback(
      '/\b(value|placeholder|title|alt|aria-label|label|data-label|data-title)=("|\')([^"\']+)\2/i',
      function ($matches) {
        return "{$matches[1]}={$matches[2]}__TRANSLATE_OPEN__{$matches[3]}__TRANSLATE_CLOSE__{$matches[2]}";
      },
      $html
    );

    // Dividir HTML en fragmentos
    $parts = preg_split(
      '/(<\/p>|<\/div>|<br\s*\/?>|<\/tr>|<\/th>|<\/li>|<\/h[1-6]>|<\/section>|<\/header>|<\/article>)/i',
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
        $translatedChunk = $body['translations'][0]['text'] ?? '';
        $translatedHtml .= $translatedChunk;

        \Drupal::logger('global_page_translate')->notice('ENVIADO: ' . substr(strip_tags($chunk), 0, 300));
        \Drupal::logger('global_page_translate')->notice('TRADUCIDO: ' . substr(strip_tags($translatedChunk), 0, 300));
      }

      // Restaurar atributos
      $translatedHtml = preg_replace_callback(
        '/\b(value|placeholder|title|alt|aria-label|label|data-label|data-title)=("|\')__TRANSLATE_OPEN__(.*?)__TRANSLATE_CLOSE__\2/i',
        function ($matches) {
          return "{$matches[1]}={$matches[2]}{$matches[3]}{$matches[2]}";
        },
        $translatedHtml
      );

    } catch (\Exception $e) {
      \Drupal::logger('global_page_translate')->error('DeepL API error: @message', ['@message' => $e->getMessage()]);
      return $html;
    }

    // Reemplazos manuales
    $replacements = [
      'value="Aplicar"' => 'value="Apply"',
      'placeholder="Buscar una película"' => 'placeholder="Search for a movie"',
    ];
    $translatedHtml = strtr($translatedHtml, $replacements);

    if (strpos($originalHtml, 'Artículo de prueba') !== false) {
      $translatedHtml = str_replace('Artículo de prueba', 'Test article', $translatedHtml);
    }

    // Fallback si no hubo cambios significativos
    $fallbacks = [
      'Guardar' => $this->stringTranslation->translate('Guardar', [], ['langcode' => strtolower($targetLang)]),
      'Vista previa' => $this->stringTranslation->translate('Vista previa', [], ['langcode' => strtolower($targetLang)]),
      'Comentarios' => $this->stringTranslation->translate('Comentarios', [], ['langcode' => strtolower($targetLang)]),
      'Comentario' => $this->stringTranslation->translate('Comentario', [], ['langcode' => strtolower($targetLang)]),
      'Asunto' => $this->stringTranslation->translate('Asunto', [], ['langcode' => strtolower($targetLang)]),
      'Formato de texto' => $this->stringTranslation->translate('Formato de texto', [], ['langcode' => strtolower($targetLang)]),
    ];
    $translatedHtml = strtr($translatedHtml, $fallbacks);

    // Marcar que se aplicó traducción personalizada
    $translatedHtml .= "\n<!-- Traducción personalizada aplicada -->";

    // Guardar en caché por 1 hora
    $this->cache->set($hash, $translatedHtml, time() + 3600);

    return $translatedHtml;
  }
}
