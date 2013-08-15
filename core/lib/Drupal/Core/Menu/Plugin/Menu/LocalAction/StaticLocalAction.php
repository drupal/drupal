<?php

/**
 * @file
 * Contains \Drupal\Core\Annotation\StaticLocalAction.
 */

namespace Drupal\Core\Menu\Plugin\Menu\LocalAction;

use Drupal\Core\Annotation\Menu\LocalAction;
use Drupal\Core\Menu\LocalActionBase;

/**
 * @LocalAction(
 *   id = "local_action_static",
 *   derivative = "Drupal\Core\Menu\Plugin\Derivative\StaticLocalActionDeriver"
 * )
 */
class StaticLocalAction extends LocalActionBase {

}
