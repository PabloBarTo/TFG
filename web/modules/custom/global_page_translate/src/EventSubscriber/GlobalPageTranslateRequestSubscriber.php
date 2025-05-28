<?php

namespace Drupal\global_page_translate\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Language\LanguageManagerInterface;

class GlobalPageTranslateRequestSubscriber implements EventSubscriberInterface {

  protected $languageManager;

  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onRequest', 20],
    ];
  }

  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();

    // Solo si no está ya la query translate
    if ($request->query->has('translate')) {
      return;
    }

    $language = $this->languageManager->getCurrentLanguage()->getId();
    if ($language === 'en') {
      $uri = $request->getUri();
      $newUri = $uri . (str_contains($uri, '?') ? '&' : '?') . 'translate=en';
      $event->setResponse(new RedirectResponse($newUri));
    }
  }
}
