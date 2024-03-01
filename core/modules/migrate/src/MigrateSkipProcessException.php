<?php

namespace Drupal\migrate;

/**
 * This exception is thrown when the rest of the process should be skipped.
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Return FALSE from a process
 *   plugin's isPipelineStopped() method to stop further processing on a
 *   pipeline.
 * @see https://www.drupal.org/node/3414511
 */
class MigrateSkipProcessException extends \Exception {

  public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = NULL) {
    trigger_error(__CLASS__ . " is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Return TRUE from a process plugin's isPipelineStopped() method to halt further processing on a pipeline. See https://www.drupal.org/node/3414511", E_USER_DEPRECATED);
    parent::__construct($message, $code, $previous);
  }

}
