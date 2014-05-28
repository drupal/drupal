<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\argument_default\CurrentUser.
 */

namespace Drupal\user\Plugin\views\argument_default;

use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument plugin to extract the current user
 *
 * This plugin actually has no options so it odes not need to do a great deal.
 *
 * @ViewsArgumentDefault(
 *   id = "current_user",
 *   title = @Translation("User ID from logged in user")
 * )
 */
class CurrentUser extends ArgumentDefaultPluginBase {

  public function getArgument() {
    return \Drupal::currentUser()->id();
  }

}
