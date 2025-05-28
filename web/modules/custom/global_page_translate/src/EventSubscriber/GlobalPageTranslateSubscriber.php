<?php

namespace Drupal\global_page_translate\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\global_page_translate\Service\TranslateService;

class GlobalPageTranslateSubscriber implements EventSubscriberInterface {

  protected $translateService;

  public function __construct(TranslateService $translateService) {
    $this->translateService = $translateService;
  }

  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onRespond', -100], // prioridad negativa para que se ejecute tarde
    ];
  }
  
  public function onRespond(ResponseEvent $event): void {
    $request = $event->getRequest();
    $response = $event->getResponse();
  
    if (!$request->query->has('translate') || $request->query->get('translate') !== 'en') {
      return;
    }
  
    if (strpos($response->headers->get('Content-Type'), 'text/html') === false) {
      return;
    }
  
    $originalContent = $response->getContent();
    $translatedContent = $this->translateService->translateHtml($originalContent, 'en');
    $response->setContent($translatedContent);
  }
}
