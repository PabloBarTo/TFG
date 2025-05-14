<?php

namespace Drupal\auto_translate\Service;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;

class TranslateService {
  protected $httpClient;
  protected $config;

  public function __construct(Client $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('auto_translate.settings');
  }
  
  public function translateText($text, $targetLang) {
    $apiKey = 'cae967dc-368d-45da-a380-c2ba435ec871:fx';
    $url = "https://api-free.deepl.com/v2/translate";
  
    $targetLang = strtoupper($targetLang);
  
    try {
      // Log para verificar los valores de los parámetros
      \Drupal::logger('auto_translate')->notice('Traduciendo: @text a: @targetLang', ['@text' => $text, '@targetLang' => $targetLang]);
  
      $response = $this->httpClient->post($url, [
        'form_params' => [
          'auth_key' => $apiKey,
          'text' => $text,
          'target_lang' => $targetLang,
        ],
      ]);
  
      $body = json_decode($response->getBody()->getContents(), TRUE);
      return $body['translations'][0]['text'] ?? 'Error en la traducción';
    }
    catch (\Exception $e) {
      \Drupal::logger('auto_translate')->error('DeepL API error: @message', ['@message' => $e->getMessage()]);
      return 'Error al conectar con el servicio de traducción.';
    }
  }
  
  
  
}
