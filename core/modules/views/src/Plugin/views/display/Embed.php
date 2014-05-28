<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\display\Embed.
 */

namespace Drupal\views\Plugin\views\display;

/**
 * The plugin that handles an embed display.
 *
 * @ingroup views_display_plugins
 *
 * @todo: Wait until annotations/plugins support access mehtods.
 * no_ui => !\Drupal::config('views.settings')->get('ui.show.display_embed'),
 *
 * @ViewsDisplay(
 *   id = "embed",
 *   title = @Translation("Embed"),
 *   help = @Translation("Provide a display which can be embedded using the views api."),
 *   theme = "views_view",
 *   uses_hook_menu = FALSE
 * )
 */
class Embed extends DisplayPluginBase {

  // This display plugin does nothing apart from exist.

}
