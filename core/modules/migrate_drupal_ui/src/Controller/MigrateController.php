<?php

namespace Drupal\migrate_drupal_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides controller methods for the migration.
 */
class MigrateController extends ControllerBase {

  /**
   * Sets a log filter and redirects to the log.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   */
  public function showLog(Request $request) {
    // Sets both the session and the query parameter so that it works correctly
    // with both the watchdog view and the fallback.
    $request->getSession()->set('dblog_overview_filter', ['type' => ['migrate_drupal_ui' => 'migrate_drupal_ui']]);
    return $this->redirect('dblog.overview', [], ['query' => ['type' => ['migrate_drupal_ui']]]);
  }

}
