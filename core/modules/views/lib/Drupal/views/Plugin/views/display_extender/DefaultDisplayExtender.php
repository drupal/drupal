<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\display_extender\DefaultDisplayExtender.
 */

namespace Drupal\views\Plugin\views\display_extender;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * @todo
 *
 * @Plugin(
 *   id = "default",
 *   title = @Translation("Empty display extender"),
 *   help = @Translation("Default settings for this view."),
 *   enabled = FALSE,
 *   no_ui = TRUE
 * )
 */
class DefaultDisplayExtender extends DisplayExtenderPluginBase {

}
