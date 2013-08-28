<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\argument_default\CurrentUser.
 */

namespace Drupal\user\Plugin\views\argument_default;

use Drupal\views\Annotation\ViewsArgumentDefault;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument plugin to extract the global $user
 *
 * This plugin actually has no options so it odes not need to do a great deal.
 *
 * @ViewsArgumentDefault(
 *   id = "current_user",
 *   module = "user",
 *   title = @Translation("User ID from logged in user")
 * )
 */
class CurrentUser extends ArgumentDefaultPluginBase {

  public function getArgument() {
    global $user;
    return $user->id();
  }

}
