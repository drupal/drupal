<?php

namespace Drupal\migrate;

use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Defines the migrate exception class.
 */
class MigrateException extends \Exception {

  /**
   * Constructs a MigrateException object.
   *
   * @param string $message
   *   The message for the exception.
   * @param int $code
   *   The Exception code.
   * @param \Throwable $previous
   *   The previous exception used for the exception chaining.
   * @param int $level
   *   The level of the error, a Migration::MESSAGE_* constant.
   * @param int $status
   *   The status of the item for the map table, a MigrateMap::STATUS_*
   *   constant.
   */
  public function __construct(
    $message = '',
    $code = 0,
    ?\Throwable $previous = NULL,
    protected $level = MigrationInterface::MESSAGE_ERROR,
    protected $status = MigrateIdMapInterface::STATUS_FAILED,
  ) {
    parent::__construct($message);
  }

  /**
   * Gets the level.
   *
   * @return int
   *   An integer status code. @see Migration::MESSAGE_*
   */
  public function getLevel() {
    return $this->level;
  }

  /**
   * Gets the status of the current item.
   *
   * @return int
   *   An integer status code. @see MigrateMap::STATUS_*
   */
  public function getStatus() {
    return $this->status;
  }

}
