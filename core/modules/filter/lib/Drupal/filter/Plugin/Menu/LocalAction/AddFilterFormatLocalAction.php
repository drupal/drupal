<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Menu\AddFilterFormatLocalAction.
 */

namespace Drupal\filter\Plugin\Menu\LocalAction;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalActionBase;
use Drupal\Core\Annotation\Menu\LocalAction;

/**
 * @LocalAction(
 *   id = "filter_format_add_local_action",
 *   route_name = "filter.format_add",
 *   title = @Translation("Add text format"),
 *   appears_on = {"filter.admin_overview"}
 * )
 */
class AddFilterFormatLocalAction extends LocalActionBase {

}
