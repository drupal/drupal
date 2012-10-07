<?php

/**
 * @file
 * Definition of views_handler_argument_users_roles_rid.
 */

namespace Drupal\user\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\ManyToOne;

/**
 * Allow role ID(s) as argument.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "users_roles_rid",
 *   module = "user"
 * )
 */
class RolesRid extends ManyToOne {

  function title_query() {
    $titles = array();

    $query = db_select('role', 'r');
    $query->addField('r', 'name');
    $query->condition('r.rid', $this->value);
    $result = $query->execute();
    foreach ($result as $term) {
      $titles[] = check_plain($term->name);
    }
    return $titles;
  }

}
