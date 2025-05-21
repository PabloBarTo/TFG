<?php

namespace Drupal\activity_logger\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Muestra los registros del log personalizado.
 */
class ActivityLoggerController extends ControllerBase {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  public function logsPage() {
    $header = [
      'id' => $this->t('ID'),
      'timestamp' => $this->t('Fecha'),
      'action' => $this->t('Acción'),
      'entity_type' => $this->t('Entidad'),
      'entity_id' => $this->t('ID Entidad'),
      'title' => $this->t('Título'),
      'username' => $this->t('Usuario'),
      'ip' => $this->t('IP'),
    ];
  
    // Obtener valores de filtro desde la URL
    $filters = \Drupal::request()->query->all();
  
    $query = $this->database->select('activity_logger', 'a')
      ->fields('a')
      ->orderBy('timestamp', 'DESC');
  
    // Aplicar filtros si están presentes
    if (!empty($filters['entity_type'])) {
      $query->condition('entity_type', $filters['entity_type']);
    }
    if (!empty($filters['username'])) {
      $query->condition('username', $filters['username']);
    }
    if (!empty($filters['action'])) {
      $query->condition('action', $filters['action']);
    }
  
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(20);
    $results = $pager->execute();
  
    $rows = [];
    foreach ($results as $record) {
      $rows[] = [
        'id' => $record->id,
        'timestamp' => \Drupal::service('date.formatter')->format($record->timestamp, 'short'),
        'action' => $record->action,
        'entity_type' => $record->entity_type,
        'entity_id' => $record->entity_id,
        'title' => $record->title ?? '',
        'username' => $record->username,
        'ip' => $record->ip,
      ];
    }
  
    // Formulario de filtros
    $form = \Drupal::formBuilder()->getForm('Drupal\activity_logger\Form\ActivityLoggerFilterForm');
  
    return [
      'filter_form' => $form,
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No se han registrado eventos aún.'),
      ],
      'pager' => ['#type' => 'pager'],
    ];
  }
}
