<?php

namespace Drupal\auto_translate\Controller;

use Drupal\Core\Controller\ControllerBase;

class TranslateController extends ControllerBase {
  public function content() {
    return \Drupal::formBuilder()->getForm('Drupal\auto_translate\Form\TranslateForm');
  }
}
