<?php

namespace Drupal\contextual;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Returns responses for Contextual module routes.
 */
class ContextualController implements ContainerInjectionInterface {

  /**
   * The renderer.
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $render;

  /**
   * Constructors a new ContextualController
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Returns the requested rendered contextual links.
   *
   * Given a list of contextual links IDs, render them. Hence this must be
   * robust to handle arbitrary input.
   *
   * @see contextual_preprocess()
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function render(Request $request) {
    $ids = $request->request->get('ids');
    if (!isset($ids)) {
      throw new BadRequestHttpException(t('No contextual ids specified.'));
    }

    $rendered = [];
    foreach ($ids as $id) {
      $element = [
        '#type' => 'contextual_links',
        '#contextual_links' => _contextual_id_to_links($id),
      ];
      $rendered[$id] = $this->renderer->renderRoot($element);
    }

    return new JsonResponse($rendered);
  }

}
