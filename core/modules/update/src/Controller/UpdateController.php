<?php

namespace Drupal\update\Controller;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\PathChangedHelper;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\update\UpdateFetcherInterface;
use Drupal\update\UpdateManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

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
    if ($available = update_get_available(TRUE)) {
      $this->moduleHandler()->loadInclude('update', 'compare.inc');
      $build['#data'] = update_calculate_project_data($available);

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
      ->addOperation([$this->updateManager, 'fetchDataBatch'], [])
      ->setProgressMessage(t('Trying to check available update data ...'))
      ->setErrorMessage($this->t('Error checking available update data.'))
      ->setFinishCallback('update_fetch_data_finished');
    batch_set($batch_builder->toArray());
    return batch_process('admin/reports/updates');
  }

  /**
   * Provides a redirect to update page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A route match object, used for the route name and the parameters.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns redirect.
   *
   * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use
   *   /admin/appearance/update directly instead of /admin/theme/update.
   *
   * @see https://www.drupal.org/node/3375850
   */
  public function updateRedirect(RouteMatchInterface $route_match, Request $request): RedirectResponse {
    @trigger_error('The path /admin/theme/update is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use /admin/appearance/update. See https://www.drupal.org/node/3382805', E_USER_DEPRECATED);
    $helper = new PathChangedHelper($route_match, $request);
    $params = [
      '%old_path' => $helper->oldPath(),
      '%new_path' => $helper->newPath(),
      '%change_record' => 'https://www.drupal.org/node/3382805',
    ];
    $warning_message = $this->t('You have been redirected from %old_path. Update links, shortcuts, and bookmarks to use %new_path.', $params);
    $this->messenger()->addWarning($warning_message);
    $this->getLogger('update')->warning('A user was redirected from %old_path to %new_path. This redirect will be removed in a future version of Drupal. Update links, shortcuts, and bookmarks to use %new_path. See %change_record for more information.', $params);
    return $helper->redirect();
  }

}
