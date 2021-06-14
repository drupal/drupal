<?php

namespace Drupal\migrate\Audit;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Defines an exception to throw if an error occurs during a migration audit.
 */
class AuditException extends \RuntimeException {

  /**
   * AuditException constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration that caused the exception.
   * @param string $message
   *   The reason the audit failed.
   * @param \Exception $previous
   *   (optional) The previous exception.
   */
  public function __construct(MigrationInterface $migration, $message, \Exception $previous = NULL) {
    $message = sprintf('Cannot audit migration %s: %s', $migration->id(), $message);
    parent::__construct($message, 0, $previous);
  }

}
