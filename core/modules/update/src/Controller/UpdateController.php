<?php

namespace Drupal\update\Controller;

use Drupal\update\UpdateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for update routes.
 */
class UpdateController extends ControllerBase {

  /**
   * Update manager service.
   *
   * @var \Drupal\update\UpdateManagerInterface
   */
  protected $updateManager;

  /**
   * Constructs update status data.
   *
   * @param \Drupal\update\UpdateManagerInterface $update_manager
   *   Update Manager Service.
   */
  public function __construct(UpdateManagerInterface $update_manager) {
    $this->updateManager = $update_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
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
    $build = [
      '#theme' => 'update_report'
    ];
    if ($available = update_get_available(TRUE)) {
      $this->moduleHandler()->loadInclude('update', 'compare.inc');
      $build['#data'] = update_calculate_project_data($available);
    }
    return $build;
  }

  /**
   * Manually checks the update status without the use of cron.
   */
  public function updateStatusManually() {
    $this->updateManager->refreshUpdateData();
    $batch = [
      'operations' => [
        [[$this->updateManager, 'fetchDataBatch'], []],
      ],
      'finished' => 'update_fetch_data_finished',
      'title' => t('Checking available update data'),
      'progress_message' => t('Trying to check available update data ...'),
      'error_message' => t('Error checking available update data.'),
    ];
    batch_set($batch);
    return batch_process('admin/reports/updates');
  }

}
