<?php

/**
 * @file
 * Definition of views_handler_filter_user_roles.
 */

use Drupal\views\Plugins\views\filter\ManyToOne;

/**
 * Filter handler for user roles.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_user_roles extends ManyToOne {
  function get_value_options() {
    $this->value_options = user_roles(TRUE);
    unset($this->value_options[DRUPAL_AUTHENTICATED_RID]);
  }

  /**
   * Override empty and not empty operator labels to be clearer for user roles.
   */
  function operators() {
    $operators = parent::operators();
    $operators['empty']['title'] = t("Only has the 'authenticated user' role");
    $operators['not empty']['title'] = t("Has roles in addition to 'authenticated user'");
    return $operators;
  }
}
