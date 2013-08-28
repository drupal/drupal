<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\display_extender\DefaultDisplayExtender.
 */

namespace Drupal\views\Plugin\views\display_extender;

use Drupal\views\Annotation\ViewsDisplayExtender;
use Drupal\Core\Annotation\Translation;

/**
 * @todo
 *
 * @ViewsDisplayExtender(
 *   id = "default",
 *   title = @Translation("Empty display extender"),
 *   help = @Translation("Default settings for this view."),
 *   no_ui = TRUE
 * )
 */
class DefaultDisplayExtender extends DisplayExtenderPluginBase {

}
