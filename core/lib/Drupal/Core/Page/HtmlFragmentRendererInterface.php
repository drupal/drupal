<?php

/**
 * @file
 * Contains \Drupal\Core\Page\HtmlFragmentRendererInterface
 */

namespace Drupal\Core\Page;

/**
 * Interface for HTML Fragment Renderers.
 *
 * An HTML Fragment Renderer is responsible for translating an HtmlFragment
 * object into an HtmlPage object.
 */
interface HtmlFragmentRendererInterface {

  /**
   * Renders an HtmlFragment into an HtmlPage.
   *
   * An HtmlFragment represents only a portion of an HTML page, along with
   * some attached information (assets, metatags, etc.) An HtmlPage represents
   * an entire page. This method will create an HtmlPage containing the
   * metadata from the fragment and using the body of the fragment as the main
   * content region of the page.
   *
   * @param \Drupal\Core\Page\HtmlFragmentInterface $fragment
   *   The HTML fragment object to convert up to a page.
   * @param int $status_code
   *   (optional) The status code of the page. May be any legal HTTP response
   *   code. Default is 200 OK.
   *
   * @return \Drupal\Core\Page\HtmlPage
   *   An HtmlPage object derived from the provided fragment.
   */
  public function render(HtmlFragmentInterface $fragment, $status_code = 200);

}
