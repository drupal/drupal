<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\rest\resource\DBLogResource.
 */

namespace Drupal\rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\Core\Annotation\Plugin;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource for database watchdog log entries.
 *
 * @Plugin(
 *  id = "dblog",
 *  label = "Watchdog database log"
 * )
 */
class DBLogResource extends ResourceBase {

  /**
   * Overrides \Drupal\rest\Plugin\ResourceBase::routes().
   */
  public function routes() {
    // Only expose routes if the dblog module is enabled.
    if (module_exists('dblog')) {
      return parent::routes();
    }
    return new RouteCollection();
  }

  /**
   * Responds to GET requests.
   *
   * Returns a watchdog log entry for the specified ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($id = NULL) {
    if ($id) {
      $result = db_select('watchdog', 'w')
        ->condition('wid', $id)
        ->fields('w')
        ->execute()
        ->fetchAll();
      if (empty($result)) {
        throw new NotFoundHttpException('Not Found');
      }
      // @todo remove hard coded format here.
      return new Response(drupal_json_encode($result[0]), 200, array('Content-Type' => 'application/json'));
    }
  }
}
