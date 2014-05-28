<?php

/**
 * @file
 * Contains \Drupal\migrate\MigrateMessage.
 */

namespace Drupal\migrate;

/**
 * Defines a migrate message class.
 */
class MigrateMessage implements MigrateMessageInterface {

  /**
   * The map between migrate status and watchdog severity.
   *
   * @var array
   */
  protected $map = array(
    'status' => WATCHDOG_INFO,
    'error' => WATCHDOG_ERROR,
  );

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
    watchdog('migrate', $message, array(), isset($this->map[$type]) ? $this->map[$type] : WATCHDOG_NOTICE);
  }

}
