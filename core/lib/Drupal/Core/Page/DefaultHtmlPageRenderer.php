<?php

/**
 * @file
 * Contains \Drupal\Core\Page\DefaultHtmlPageRenderer
 */

namespace Drupal\Core\Page;

/**
 * Default page rendering engine.
 */
class DefaultHtmlPageRenderer implements HtmlPageRendererInterface {

  /**
   * {@inheritdoc}
   */
  public function render(HtmlPage $page) {
    $render = array(
      '#type' => 'html',
      '#page_object' => $page,
    );
    return drupal_render($render);
  }

}
