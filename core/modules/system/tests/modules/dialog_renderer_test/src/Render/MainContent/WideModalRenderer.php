<?php

namespace Drupal\dialog_renderer_test\Render\MainContent;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Render\MainContent\ModalRenderer;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default main content renderer for wide modal dialog requests.
 *
 * This test class is copied from \Drupal\Core\Render\MainContent\ModalRenderer
 * to demonstrate selecting a different render via 'data-dialog-renderer' link
 * attribute.
 */
class WideModalRenderer extends ModalRenderer {

  /**
   * The mode, either 'wide' or 'extra_wide'.
   *
   * @var string
   */
  protected $mode;

  /**
   * Constructs a new WideModalRenderer.
   *
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param string $mode
   *   The mode, either 'wide' or 'extra_wide'.
   */
  public function __construct(TitleResolverInterface $title_resolver, RendererInterface $renderer, $mode = 'wide') {
    parent::__construct($title_resolver, $renderer);
    $this->mode = $mode;
  }

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $response = new AjaxResponse();

    // First render the main content, because it might provide a title.
    $content = $this->renderer->renderRoot($main_content);

    // Attach the library necessary for using the OpenModalDialogCommand and set
    // the attachments for this Ajax response.
    $main_content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $response->setAttachments($main_content['#attached']);

    // If the main content doesn't provide a title, use the title resolver.
    $title = $main_content['#title'] ?? $this->titleResolver->getTitle($request, $route_match->getRouteObject());

    // Determine the title: use the title provided by the main content if any,
    // otherwise get it from the routing information.
    $options = $request->request->get('dialogOptions', []);
    // Override width option.
    switch ($this->mode) {
      case 'wide':
        $options['width'] = 700;
        break;

      case 'extra_wide':
        $options['width'] = 1000;
        break;
    }

    $response->addCommand(new OpenModalDialogCommand($title, $content, $options));
    return $response;
  }

}
