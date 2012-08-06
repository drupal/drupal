<?php

/**
 * @file
 * Definition of views_handler_argument_users_roles_rid.
 */

use Drupal\views\Plugin\views\argument\ManyToOne;

/**
 * Allow role ID(s) as argument.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_users_roles_rid extends ManyToOne {
  function title_query() {
    $titles = array();

    $result = db_query("SELECT name FROM {role} WHERE rid IN (:rids)", array(':rids' => $this->value));
    foreach ($result as $term) {
      $titles[] = check_plain($term->name);
    }
    return $titles;
  }
}
