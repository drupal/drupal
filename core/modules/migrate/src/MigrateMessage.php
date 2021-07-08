<?php

namespace Drupal\migrate;

use Drupal\Core\Logger\RfcLogLevel;

/**
 * Defines a migrate message class.
 */
class MigrateMessage implements MigrateMessageInterface {

  /**
   * The map between migrate status and watchdog severity.
   *
   * @var array
   */
  protected $map = [
    'status' => RfcLogLevel::INFO,
    'error' => RfcLogLevel::ERROR,
  ];

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
    $type = $this->map[$type] ?? RfcLogLevel::NOTICE;
    \Drupal::logger('migrate')->log($type, $message);
  }

}
