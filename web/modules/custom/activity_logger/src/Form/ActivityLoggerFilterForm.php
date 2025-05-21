<?php

namespace Drupal\activity_logger\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para filtrar los logs de actividad.
 */
class ActivityLoggerFilterForm extends FormBase {

  public function getFormId() {
    return 'activity_logger_filter_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request()->query;

    $form['entity_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entidad'),
      '#default_value' => $request->get('entity_type'),
      '#size' => 20,
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Usuario'),
      '#default_value' => $request->get('username'),
      '#size' => 20,
    ];

    $form['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Acción'),
      '#options' => [
        '' => '- Cualquiera -',
        'creada' => 'creada',
        'modificada' => 'modificada',
        'eliminada' => 'eliminada',
      ],
      '#default_value' => $request->get('action'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Aplicar'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $params = [];

    foreach (['entity_type', 'username', 'action'] as $key) {
      $value = $form_state->getValue($key);
      if (!empty($value)) {
        $params[$key] = $value;
      }
    }

    $form_state->setRedirect('activity_logger.custom_logs', [], ['query' => $params]);
  }
}
