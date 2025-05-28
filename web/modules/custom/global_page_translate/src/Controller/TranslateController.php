<?php

namespace Drupal\global_page_translate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\global_page_translate\Service\TranslateService;

class TranslateController extends ControllerBase {

  protected $translateService;

  public function __construct(TranslateService $translateService) {
    $this->translateService = $translateService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('global_page_translate.translate_service')
    );
  }

  public function translateContent($langcode, Request $request): JsonResponse {
    $page_url = $request->request->get('page_url');

    // Asegura que el path esté limpio
    $parsed_url = parse_url($page_url);
    $path = $parsed_url['path'] ?? '/';

    // Sub-request a Drupal para obtener el HTML renderizado
    $sub_request = Request::create($path, 'GET');
    $sub_request->headers->set('Accept', 'text/html');

    $response = \Drupal::service('http_kernel')->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
    $html = $response->getContent();

    // Convertir $langcode en string si es objeto
    $langcode_str = is_object($langcode) && method_exists($langcode, 'getId') ? $langcode->getId() : (string) $langcode;

    $translated_html = $this->translateService->translateHtml($html, $langcode_str);

    return new JsonResponse([
      'translated' => $translated_html,
    ]);
  }
}