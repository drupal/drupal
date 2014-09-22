<?php

/**
 * @file
 * Contains \Drupal\Core\Page\RenderHtmlRendererInterface.
 */

namespace Drupal\Core\Page;

/**
 * Interface for a render array to HTML fragment renderer.
 *
 * An HTML Fragment Renderer is responsible for translating a Drupal render
 * array into an HtmlFragmentInterface object.
 */
interface RenderHtmlRendererInterface {

  /**
   * Converts a render array into a corresponding HtmlFragment object.
   *
   * @param array $render_array
   *   The render array to convert.
   *
   * @return \Drupal\Core\Page\HtmlFragment
   *   The equivalent HtmlFragment object.
   *
   * @todo Change this documentation once https://www.drupal.org/node/2339475 lands.
   */
  public function render(array $render_array);

}
