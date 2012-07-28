<?php

namespace Drupal\views\Plugins\views\display_extender;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * @Plugin(
 *   plugin_id = "default",
 *   title = @Translation("Empty display extender"),
 *   help = @Translation("Default settings for this view."),
 *   enabled = FALSE,
 *   no_ui = TRUE
 * )
 */
class DefaultDisplayExtender extends DisplayExtenderPluginBase {
}
