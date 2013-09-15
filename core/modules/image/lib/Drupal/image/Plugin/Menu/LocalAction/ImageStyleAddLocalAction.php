<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\Menu\ImageStyleAddLocalAction.
 */

namespace Drupal\image\Plugin\Menu\LocalAction;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalActionBase;
use Drupal\Core\Annotation\Menu\LocalAction;

/**
 * @LocalAction(
 *   id = "image_style_add_action",
 *   route_name = "image.style_add",
 *   title = @Translation("Add image style"),
 *   appears_on = {"image.style_list"}
 * )
 */
class ImageStyleAddLocalAction extends LocalActionBase {

}
