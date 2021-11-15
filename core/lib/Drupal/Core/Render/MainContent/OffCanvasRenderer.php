<?php

namespace Drupal\Core\Render\MainContent;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Ajax\OpenOffCanvasDialogCommand;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default main content renderer for off-canvas dialog requests.
 *
 * @internal
 */
class OffCanvasRenderer extends DialogRenderer {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The position to render the off-canvas dialog.
   *
   * @var string
   */
  protected $position;

  /**
   * Constructs a new OffCanvasRenderer.
   *
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param string $position
   *   (optional) The position to render the off-canvas dialog.
   */
  public function __construct(TitleResolverInterface $title_resolver, RendererInterface $renderer, $position = 'side') {
    parent::__construct($title_resolver, $renderer);
    $this->renderer = $renderer;
    $this->position = $position;
  }

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $response = new AjaxResponse();

    // First render the main content, because it might provide a title.
    $content = $this->renderer->renderRoot($main_content);
    // Attach the library necessary for using the OpenOffCanvasDialogCommand and
    // set the attachments for this Ajax response.
    $main_content['#attached']['library'][] = 'core/drupal.dialog.off_canvas';
    $response->setAttachments($main_content['#attached']);

    // If the main content doesn't provide a title, use the title resolver.
    $title = $main_content['#title'] ?? $this->titleResolver->getTitle($request, $route_match->getRouteObject());

    // Determine the title: use the title provided by the main content if any,
    // otherwise get it from the routing information.
    $options = $request->request->get('dialogOptions', []);
    $response->addCommand(new OpenOffCanvasDialogCommand($title, $content, $options, NULL, $this->position));
    return $response;
  }

}
