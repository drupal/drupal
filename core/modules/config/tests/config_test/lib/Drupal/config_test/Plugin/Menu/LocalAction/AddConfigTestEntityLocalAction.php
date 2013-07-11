<?php

/**
 * @file
 * Contains \Drupal\config_test\Plugin\Menu\AddConfigTestEntityLocalAction.
 */

namespace Drupal\config_test\Plugin\Menu\LocalAction;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalActionBase;
use Drupal\Core\Annotation\Menu\LocalAction;

/**
 * @LocalAction(
 *   id = "config_test_entity_add_local_action",
 *   route_name = "config_test_entity_add",
 *   title = @Translation("Add test configuration"),
 *   appears_on = {"config_test_list_page"}
 * )
 */
class AddConfigTestEntityLocalAction extends LocalActionBase {

}
