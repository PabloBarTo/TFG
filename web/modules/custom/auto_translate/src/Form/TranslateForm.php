<?php

namespace Drupal\auto_translate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\auto_translate\Service\TranslateService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TranslateForm extends FormBase {
  protected $translateService;

  public function __construct(TranslateService $translate_service) {
    $this->translateService = $translate_service;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('auto_translate.translate_service'));
  }

  public function getFormId() {
    return 'auto_translate_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Texto a traducir'),
      '#required' => TRUE,
    ];

    $form['target_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Idioma de destino'),
      '#options' => [
        'EN' => 'Inglés',
        'ES' => 'Español',
        'FR' => 'Francés',
        'DE' => 'Alemán',
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Traducir'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $text = $form_state->getValue('text');
    $targetLang = $form_state->getValue('target_language');

    $translation = $this->translateService->translateText($text, $targetLang);

    \Drupal::messenger()->addMessage($this->t('Texto traducido: @translation', ['@translation' => $translation]));
  }
}
