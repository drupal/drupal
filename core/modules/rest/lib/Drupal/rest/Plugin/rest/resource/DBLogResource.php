<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\rest\resource\DBLogResource.
 */

namespace Drupal\rest\Plugin\rest\resource;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource for database watchdog log entries.
 *
 * @Plugin(
 *   id = "dblog",
 *   label = @Translation("Watchdog database log")
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
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the log entry.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($id = NULL) {
    if ($id) {
      $record = db_query("SELECT * FROM {watchdog} WHERE wid = :wid", array(':wid' => $id))
        ->fetchObject();
      if (!empty($record)) {
        // Serialization is done here, so we indicate with NULL that there is no
        // subsequent serialization necessary.
        $response = new ResourceResponse(NULL, 200, array('Content-Type' => 'application/vnd.drupal.ld+json'));
        // @todo remove hard coded format here.
        $response->setContent(drupal_json_encode($record));
        return $response;
      }
    }
    throw new NotFoundHttpException('Not Found');
  }
}
