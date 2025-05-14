<?php
    namespace Drupal\pruebas_module\Controller;
    use Drupal\Core\Controller\ControllerBase;

    class PruebasController extends ControllerBase{
        public function content () {
             return array(
                '#type' => 'markup',
                '#markup' => $this->t('Hola mundo'),
             );
        }
    }

?>