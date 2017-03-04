<?php

namespace Drupal\dblog\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource for database watchdog log entries.
 *
 * @RestResource(
 *   id = "dblog",
 *   label = @Translation("Watchdog database log"),
 *   uri_paths = {
 *     "canonical" = "/dblog/{id}"
 *   }
 * )
 */
class DBLogResource extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a watchdog log entry for the specified ID.
   *
   * @param int $id
   *   The ID of the watchdog log entry.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the log entry.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the log entry was not found.
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when no log entry was provided.
   */
  public function get($id = NULL) {
    if ($id) {
      $record = db_query("SELECT * FROM {watchdog} WHERE wid = :wid", [':wid' => $id])
        ->fetchAssoc();
      if (!empty($record)) {
        return new ResourceResponse($record);
      }

      throw new NotFoundHttpException(t('Log entry with ID @id was not found', ['@id' => $id]));
    }

    throw new BadRequestHttpException(t('No log entry ID was provided'));
  }

}
