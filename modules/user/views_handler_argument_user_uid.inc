<?php

/**
 * @file
 * Definition of views_handler_argument_user_uid.
 */

/**
 * Argument handler to accept a user id.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_user_uid extends views_handler_argument_numeric {
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
