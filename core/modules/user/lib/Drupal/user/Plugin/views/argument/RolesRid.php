<?php

/**
 * @file
 * Definition of views_handler_argument_users_roles_rid.
 */

namespace Drupal\user\Plugin\views\argument;

use Drupal\Component\Annotation\Plugin;
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
    return array(entity_load('user_role', $this->value)->label());
  }

}
