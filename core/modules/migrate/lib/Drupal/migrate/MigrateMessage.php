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
   * Displays a migrate message.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The type of message, for example: status or warning.
   */
  function display($message, $type = 'status') {
    drupal_set_message($message, $type);
  }

}
