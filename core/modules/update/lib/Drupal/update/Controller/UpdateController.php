<?php

/**
 * @file
 * Contains \Drupal\update\Controller\UpdateController.
 */

namespace Drupal\update\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for update routes.
 */
class UpdateController implements ContainerInjectionInterface {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs update status data.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module Handler Service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Returns a page about the update status of projects.
   *
   * @return array
   *   A build array with the update status of projects.
   */
  public function updateStatus() {
    $build = array(
      '#theme' => 'update_report'
    );
    if ($available = update_get_available(TRUE)) {
      $this->moduleHandler->loadInclude('update', 'compare.inc');
      $build['#data'] = update_calculate_project_data($available);
    }
    else {
      $build['#data'] = _update_no_data();
    }
    return $build;
  }

  /**
   * @todo Remove update_manual_status().
   */
  public function updateStatusManually() {
    module_load_include('fetch.inc', 'update');
    return update_manual_status();
  }

}
