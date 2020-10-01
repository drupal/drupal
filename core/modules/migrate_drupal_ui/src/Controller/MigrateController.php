<?php

namespace Drupal\migrate_drupal_ui\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides controller methods for the migration.
 */
class MigrateController extends ControllerBase {

  /**
   * Sets a log filter and redirects to the log.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   */
  public function showLog() {
    // Sets both the session and the query parameter so that it works correctly
    // with both the watchdog view and the fallback.
    $_SESSION['dblog_overview_filter'] = [];
    $_SESSION['dblog_overview_filter']['type'] = ['migrate_drupal_ui' => 'migrate_drupal_ui'];
    return $this->redirect('dblog.overview', [], ['query' => ['type' => ['migrate_drupal_ui']]]);
  }

}
