<?php

/**
 * @file
 * Contains \Drupal\system\Controller\BatchController.
 */

namespace Drupal\system\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for batch routes.
 */
class BatchController {

  /**
   * Returns a system batch page.
   *
   * @param \Symfony\Component\HttpFoundation\Request
   *   The current request object.
   *
   * @return mixed
   *   A \Symfony\Component\HttpFoundation\Response object or page element.
   */
  public function batchPage(Request $request) {
    require_once DRUPAL_ROOT . '/core/includes/batch.inc';
    $output = _batch_page($request);

    if ($output === FALSE) {
      throw new AccessDeniedHttpException();
    }
    elseif ($output instanceof Response) {
      return $output;
    }
    elseif (isset($output)) {
      // Force a page without blocks or messages to
      // display a list of collected messages later.
      drupal_set_page_content($output);
      $page = element_info('page');
      $page['#show_messages'] = FALSE;
      return $page;
    }
  }

}
