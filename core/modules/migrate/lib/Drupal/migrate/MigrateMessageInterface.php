<?php
/**
 * @file
 * Contains
 */

namespace Drupal\migrate;


interface MigrateMessageInterface {

  /**
   * @param $message
   * @param string $type
   *
   * @return mixed
   */
  function display($message, $type = 'status');
}
