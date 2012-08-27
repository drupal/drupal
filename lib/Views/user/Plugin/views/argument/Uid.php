<?php

/**
 * @file
 * Definition of Views\user\Plugin\views\argument\Uid.
 */

namespace Views\user\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Numeric;

/**
 * Argument handler to accept a user id.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "user_uid",
 *   module = "user"
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
      return array(config('user.settings')->get('anonymous'));
    }

    $titles = array();

    $users = user_load_multiple($this->value);
    foreach ($users as $account) {
      $titles[] = check_plain($account->label());
    }
    return $titles;
  }

}
