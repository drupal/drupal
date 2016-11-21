<?php

namespace Drupal\migrate_drupal_ui\Batch;

use Drupal\migrate\MigrateMessageInterface;

/**
 * Allows capturing messages rather than displaying them directly.
 */
class MigrateMessageCapture implements MigrateMessageInterface {

  /**
   * Array of recorded messages.
   *
   * @var array
   */
  protected $messages = [];

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
    $this->messages[] = $message;
  }

  /**
   * Clears out any captured messages.
   */
  public function clear() {
    $this->messages = [];
  }

  /**
   * Returns any captured messages.
   *
   * @return array
   *   The captured messages.
   */
  public function getMessages() {
    return $this->messages;
  }

}
