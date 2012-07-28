<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\views\style\DefaultStyle.
 */

namespace Drupal\views\Plugins\views\style;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Unformatted style plugin to render rows one after another with no
 * decorations.
 *
 * @ingroup views_style_plugins
 */

/**
 * @Plugin(
 *   plugin_id = "default",
 *   title = @Translation("Unformatted list"),
 *   help = @Translation("Displays rows one after another."),
 *   theme = "views_view_unformatted",
 *   uses_row_plugin = TRUE,
 *   uses_row_class = TRUE,
 *   uses_grouping = TRUE,
 *   uses_options = TRUE,
 *   type = "normal",
 *   help_topic = "style-unformatted"
 * )
 */
class DefaultStyle extends StylePluginBase {
  /**
   * Set default options
   */
  function options(&$options) {
    parent::options($options);
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
  }
}
