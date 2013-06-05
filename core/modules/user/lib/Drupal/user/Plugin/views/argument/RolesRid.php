<?php

/**
 * @file
 * Definition of views_handler_argument_users_roles_rid.
 */

namespace Drupal\user\Plugin\views\argument;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\argument\ManyToOne;

/**
 * Allow role ID(s) as argument.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("users_roles_rid")
 */
class RolesRid extends ManyToOne {

  public function titleQuery() {
    return array(entity_load('user_role', $this->value)->label());
  }

}
