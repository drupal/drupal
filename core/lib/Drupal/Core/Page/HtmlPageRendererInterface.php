<?php

/**
 * @file
 * Contains \Drupal\Core\Page\HtmlPageRendererInterface
 */

namespace Drupal\Core\Page;

/**
 * Interface for HTML Page Renderers.
 *
 * An HTML Page Renderer is responsible for translating an HtmlFragment object
 * into an HtmlPage object, and from a page object into a string.
 */
interface HtmlPageRendererInterface {

  /**
   * Renders an HtmlFragment into an HtmlPage.
   *
   * An HtmlFragment represents only a portion of an HTML page, along with
   * some attached information (assets, metatags, etc.) An HtmlPage represents
   * an entire page. This method will create an HtmlPage containing the
   * metadata from the fragment and using the body of the fragment as the main
   * content region of the page.
   *
   * @param \Drupal\Core\Page\HtmlFragment $fragment
   *   The HTML fragment object to convert up to a page.
   * @param int $status_code
   *   (optional) The status code of the page. May be any legal HTTP response
   *   code. Default is 200 OK.
   *
   * @return \Drupal\Core\Page\HtmlPage
   *   An HtmlPage object derived from the provided fragment.
   */
  public function render(HtmlFragment $fragment, $status_code = 200);

  /**
   * Renders an HtmlPage object to an HTML string.
   *
   * @param \Drupal\Core\Page\HtmlPage $page
   *   The page to render.
   *
   * @return string
   *   A complete HTML page as a string.
   */
  public function renderPage(HtmlPage $page);

}
