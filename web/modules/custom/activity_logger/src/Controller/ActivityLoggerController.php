<?php

namespace Drupal\activity_logger\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Muestra los logs del módulo Activity Logger.
 */
class ActivityLoggerController extends ControllerBase {

  /**
   * La conexión a la base de datos.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructor por inyección de dependencias.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Muestra los logs en una tabla.
   */
  public function logsPage() {
    $query = $this->database->select('watchdog', 'w')
      ->fields('w', ['wid', 'type', 'message', 'variables', 'timestamp'])
      ->condition('type', 'activity_logger')
      ->orderBy('timestamp', 'DESC')
      ->range(0, 50); // Mostrar solo los últimos 50

    $result = $query->execute();

    $rows = [];
    foreach ($result as $record) {
      // Procesar mensaje con placeholders si hay variables
      $message = $record->message;
      $variables = unserialize($record->variables ?? '') ?: [];
    
      if (!empty($variables)) {
        $message = \Drupal::translation()->translate($message, $variables);
      }
    
      $rows[] = [
        $record->wid,
        $message,
        date('Y-m-d H:i:s', $record->timestamp),
      ];
    }

    $header = ['ID', 'Mensaje', 'Fecha'];
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No se han registrado eventos aún.'),
    ];
  }
}
