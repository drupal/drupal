<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\filter\Roles.
 */

namespace Drupal\user\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter handler for user roles.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("user_roles")
 */
class Roles extends ManyToOne {

  public function getValueOptions() {
    $this->value_options = user_role_names(TRUE);
    unset($this->value_options[DRUPAL_AUTHENTICATED_RID]);
  }

  /**
   * Override empty and not empty operator labels to be clearer for user roles.
   */
  function operators() {
    $operators = parent::operators();
    $operators['empty']['title'] = $this->t("Only has the 'authenticated user' role");
    $operators['not empty']['title'] = $this->t("Has roles in addition to 'authenticated user'");
    return $operators;
  }

}
