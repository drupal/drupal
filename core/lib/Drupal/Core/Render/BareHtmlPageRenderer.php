<?php

/**
 * @file
 * Contains \Drupal\Core\Render\BareHtmlPageRenderer.
 */

namespace Drupal\Core\Render;

/**
 * Default bare HTML page renderer.
 */
class BareHtmlPageRenderer implements BareHtmlPageRendererInterface {

  /**
   * {@inheritdoc}
   */
  public function renderBarePage(array $content, $title, $page_theme_property, array $page_additions = []) {
    $attributes = [
      'class' => [
        str_replace('_', '-', $page_theme_property),
      ],
    ];
    $html = [
      '#type' => 'html',
      '#attributes' => $attributes,
      'page' => [
        '#type' => 'page',
        '#theme' => $page_theme_property,
        '#title' => $title,
        'content' => $content,
      ] + $page_additions,
    ];

    // We must first render the contents of the html.html.twig template, see
    // \Drupal\Core\Render\MainContent\HtmlRenderer::renderResponse() for more
    // information about this; the exact same pattern is used there and
    // explained in detail there.
    drupal_render_root($html['page']);

    // Add the bare minimum of attachments from the system module and the
    // current maintenance theme.
    system_page_attachments($html['page']);
    return drupal_render($html);
  }

}
