<?php

namespace Drupal\jsonapi\Revisions;

/**
 * Used when a version ID is valid, but the requested version does not exist.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class VersionNotFoundException extends \InvalidArgumentException {

  /**
   * {@inheritdoc}
   */
  public function __construct($message = '', $code = 0, ?\Throwable $previous = NULL) {
    parent::__construct(!is_null($message) ? $message : 'The identified version could not be found.', $code, $previous);
  }

}
