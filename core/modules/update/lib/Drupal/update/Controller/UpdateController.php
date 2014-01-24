<?php

/**
 * @file
 * Contains \Drupal\update\Controller\UpdateController.
 */

namespace Drupal\update\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\update\UpdateManagerInterface;
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
   * Update manager service.
   *
   * @var \Drupal\update\UpdateManagerInterface
   */
  protected $updateManager;

  /**
   * Constructs update status data.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module Handler Service.
   *
   * @param \Drupal\update\UpdateManagerInterface $update_manager
   *   Update Manager Service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, UpdateManagerInterface $update_manager) {
    $this->moduleHandler = $module_handler;
    $this->updateManager = $update_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('update.manager')
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
   * Manually checks the update status without the use of cron.
   */
  public function updateStatusManually() {
    $this->updateManager->refreshUpdateData();
    $batch = array(
      'operations' => array(
        array(array($this->updateManager, 'fetchDataBatch'), array()),
      ),
      'finished' => 'update_fetch_data_finished',
      'title' => t('Checking available update data'),
      'progress_message' => t('Trying to check available update data ...'),
      'error_message' => t('Error checking available update data.'),
    );
    batch_set($batch);
    return batch_process('admin/reports/updates');
  }

}
