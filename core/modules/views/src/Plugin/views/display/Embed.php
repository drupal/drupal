<?php

namespace Drupal\views\Plugin\views\display;

/**
 * The plugin that handles an embed display.
 *
 * @ingroup views_display_plugins
 *
 * @todo: Wait until annotations/plugins support access methods.
 * no_ui => !\Drupal::config('views.settings')->get('ui.show.display_embed'),
 *
 * @ViewsDisplay(
 *   id = "embed",
 *   title = @Translation("Embed"),
 *   help = @Translation("Provide a display which can be embedded using the views api."),
 *   theme = "views_view",
 *   uses_menu_links = FALSE
 * )
 */
class Embed extends DisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildRenderable(array $args = [], $cache = TRUE) {
    $build = parent::buildRenderable($args, $cache);
    $build['#embed'] = TRUE;
    return $build;
  }

}
