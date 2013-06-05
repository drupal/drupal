<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\argument\Uid.
 */

namespace Drupal\user\Plugin\views\argument;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\argument\Numeric;

/**
 * Argument handler to accept a user id.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("user_uid")
 */
class Uid extends Numeric {

  /**
   * Override the behavior of title(). Get the name of the user.
   *
   * @return array
   *    A list of usernames.
   */
  public function titleQuery() {
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
