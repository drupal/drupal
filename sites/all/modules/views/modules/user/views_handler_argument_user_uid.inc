<?php
// $Id: views_handler_argument_user_uid.inc,v 1.1.6.1 2009/11/02 22:01:27 merlinofchaos Exp $
/**
 * @file
 * Provide user uid argument handler.
 */

/**
 * Argument handler to accept a user id.
 */
class views_handler_argument_user_uid extends views_handler_argument_numeric {
  /**
   * Override the behavior of title(). Get the name of the user.
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

