<?php

namespace Drupal\views\Plugin\views\display_extender;

/**
 * Default display extender plugin; does nothing.
 *
 * @ingroup views_display_extender_plugins
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
