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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new BareHtmlPageRenderer.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

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

    // For backwards compatibility.
    // @todo In Drupal 9, add a $show_messages function parameter.
    if (!isset($page_additions['#show_messages']) || $page_additions['#show_messages'] === TRUE) {
      $html['page']['highlighted'] = ['#type' => 'status_messages'];
    }

    // We must first render the contents of the html.html.twig template, see
    // \Drupal\Core\Render\MainContent\HtmlRenderer::renderResponse() for more
    // information about this; the exact same pattern is used there and
    // explained in detail there.
    $this->renderer->render($html['page'], TRUE);

    // Add the bare minimum of attachments from the system module and the
    // current maintenance theme.
    system_page_attachments($html['page']);
    return $this->renderer->render($html);
  }

}
