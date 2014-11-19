<?php

/**
 * @file
 * Contains \Drupal\Core\Render\BareHtmlPageRenderer.
 */

namespace Drupal\Core\Render;

/**
 * Default bare HTML page renderer
 */
class BareHtmlPageRenderer implements BareHtmlPageRendererInterface {

  /**
   * {@inheritdoc}
   */
  public function renderMaintenancePage($content, $title, array $page_additions = []) {
    if (!is_array($content)) {
      $content = ['#markup' => $content];
    }
    $attributes = [
      'class' => [
        'maintenance-page',
      ],
    ];
    return $this->renderBarePage($content, $title, $page_additions, $attributes, 'maintenance_page');
  }

  /**
   * {@inheritdoc}
   */
  public function renderInstallPage($content, $title, array $page_additions = []) {
    $attributes = [
      'class' => [
        'install-page',
      ],
    ];
    return $this->renderBarePage($content, $title, $page_additions, $attributes, 'install_page');
  }

  /**
   * Renders a bare page.
   *
   * @param string|array $content
   *   The main content to render in the 'content' region.
   * @param string $title
   *   The title for this maintenance page.
   * @param array $page_additions
   *   Additional regions to add to the page. May also be used to pass the
   *   #show_messages property for #type 'page'.
   * @param array $attributes
   *   Attributes to set on #type 'html'.
   * @param string $page_theme_property
   *   The #theme property to set on #type 'page'.
   *
   * @return string
   *   The rendered HTML page.
   */
  protected function renderBarePage(array $content, $title, array $page_additions, array $attributes, $page_theme_property) {
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
