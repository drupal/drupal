<?php

/**
 * @file
 * Contains \Drupal\menu_test\Plugin\Menu\MenuTestLocalAction.
 */

namespace Drupal\menu_test\Plugin\Menu\LocalAction;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalActionBase;
use Drupal\Core\Annotation\Menu\LocalAction;

/**
 * @LocalAction(
 *   id = "menu_test_local_action3",
 *   route_name = "menu_test.local_action3",
 *   title = @Translation("My routing action"),
 *   appears_on = {"menu_test.local_action1"}
 * )
 */
class MenuTestLocalAction extends LocalActionBase {

}
