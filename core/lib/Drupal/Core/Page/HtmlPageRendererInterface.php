<?php

/**
 * @file
 * Contains \Drupal\Core\Page\HtmlPageRendererInterface
 */

namespace Drupal\Core\Page;

/**
 * Interface for HTML Page Renderers.
 *
 * An HTML Page Renderer is responsible for translating an HtmlPage object
 * into a string.
 */
interface HtmlPageRendererInterface {

  /**
   * Renders an HtmlPage object to an HTML string.
   *
   * @param \Drupal\Core\Page\HtmlPage $page
   *   The page to render.
   *
   * @return string
   *   A complete HTML page as a string.
   */
  public function render(HtmlPage $page);

}
