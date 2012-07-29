<?php

/**
 * @file
 * Definition of views_handler_argument_user_uid.
 */

namespace Views\user\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Numeric;

/**
 * Argument handler to accept a user id.
 *
 * @ingroup views_argument_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "user_uid"
 * )
 */
class Uid extends Numeric {
  /**
   * Override the behavior of title(). Get the name of the user.
   *
   * @return array
   *    A list of usernames.
   */
  function title_query() {
    if (!$this->argument) {
      return array(variable_get('anonymous', t('Anonymous')));
    }

    $titles = array();

    $users = user_load_multiple($this->value);
    foreach ($users as $account) {
      $titles[] = check_plain($account->label());
    }
    return $titles;
  }
}
