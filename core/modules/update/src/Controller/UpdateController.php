<?php

namespace Drupal\update\Controller;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\update\UpdateFetcherInterface;
use Drupal\update\UpdateManagerInterface;

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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs update status data.
   *
   * @param \Drupal\update\UpdateManagerInterface $update_manager
   *   Update Manager Service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(UpdateManagerInterface $update_manager, RendererInterface $renderer) {
    $this->updateManager = $update_manager;
    $this->renderer = $renderer;
  }

  /**
   * Returns a page about the update status of projects.
   *
   * @return array
   *   A build array with the update status of projects.
   */
  public function updateStatus() {
    $build = [
      '#theme' => 'update_report',
    ];
    if ($available = $this->updateManager->getAvailable(TRUE)) {
      $this->moduleHandler()->loadInclude('update', 'compare.inc');
      $build['#data'] = $this->updateManager->calculateProjectData($available);

      // @todo Consider using 'fetch_failures' from the 'update' collection
      // in the key_value_expire service for this?
      $fetch_failed = FALSE;
      foreach ($build['#data'] as $project) {
        if ($project['status'] === UpdateFetcherInterface::NOT_FETCHED) {
          $fetch_failed = TRUE;
          break;
        }
      }
      if ($fetch_failed) {
        $message = ['#theme' => 'update_fetch_error_message'];
        $this->messenger()->addError($this->renderer->renderInIsolation($message));
      }
    }
    return $build;
  }

  /**
   * Manually checks the update status without the use of cron.
   */
  public function updateStatusManually() {
    $this->updateManager->refreshUpdateData();
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Checking available update data'))
      ->addOperation(UpdateManagerInterface::class . ':fetchDataBatch', [])
      ->setProgressMessage($this->t('Trying to check available update data ...'))
      ->setErrorMessage($this->t('Error checking available update data.'))
      ->setFinishCallback(self::class . ':updateFetchDataFinished');
    batch_set($batch_builder->toArray());
    return batch_process('admin/reports/updates');
  }

  /**
   * Finished batch callback.
   *
   * @param bool $success
   *   TRUE if batch successfully completed.
   * @param array $results
   *   Batch results.
   */
  public function updateFetchDataFinished(bool $success, array $results): void {
    if ($success) {
      if (!empty($results)) {
        if (!empty($results['updated'])) {
          $this->messenger()->addStatus($this->formatPlural($results['updated'], 'Checked available update data for one project.', 'Checked available update data for @count projects.'));
        }
        if (!empty($results['failures'])) {
          $this->messenger()->addError($this->formatPlural($results['failures'], 'Failed to get available update data for one project.', 'Failed to get available update data for @count projects.'));
        }
      }
    }
    else {
      $this->messenger()->addError($this->t('An error occurred trying to get available update data.'), 'error');
    }
  }

}
