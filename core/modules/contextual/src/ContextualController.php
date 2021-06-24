<?php

namespace Drupal\contextual;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Site\Settings;
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
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructors a new ContextualController.
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
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Symfony request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when the request contains no ids.
   *
   * @see contextual_preprocess()
   */
  public function render(Request $request) {
    $ids = $request->request->get('ids');
    if (!isset($ids)) {
      throw new BadRequestHttpException('No contextual ids specified.');
    }

    $tokens = $request->request->get('tokens');
    if (!isset($tokens)) {
      throw new BadRequestHttpException('No contextual ID tokens specified.');
    }

    $rendered = [];
    foreach ($ids as $key => $id) {
      if (!isset($tokens[$key]) || !hash_equals($tokens[$key], Crypt::hmacBase64($id, Settings::getHashSalt() . \Drupal::service('private_key')->get()))) {
        throw new BadRequestHttpException('Invalid contextual ID specified.');
      }
      $element = [
        '#type' => 'contextual_links',
        '#contextual_links' => _contextual_id_to_links($id),
      ];
      $rendered[$id] = $this->renderer->renderRoot($element);
    }

    return new JsonResponse($rendered);
  }

}
