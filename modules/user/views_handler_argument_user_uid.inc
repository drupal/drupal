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

    $result = db_query("SELECT u.name FROM {users} u WHERE u.uid IN (:uids)", array(':uids' => $this->value));
    foreach ($result as $term) {
      $titles[] = check_plain($term->name);
    }
    return $titles;
  }
}
