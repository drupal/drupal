<?php

namespace Drupal\contextual;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Returns responses for Contextual module routes.
 */
class ContextualController implements ContainerInjectionInterface {

  use AutowireTrait;

  public function __construct(
    protected RendererInterface $renderer,
    protected ?ContextualLinksSerializer $serializer = NULL,
  ) {
    if (!$serializer) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $serializer argument is deprecated in drupal:11.4.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3568088', E_USER_DEPRECATED);
      $this->serializer = \Drupal::service(ContextualLinksSerializer::class);
    }
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
   * @internal
   *
   * @see contextual_preprocess()
   */
  public function render(Request $request) {
    if (!$request->request->has('ids')) {
      throw new BadRequestHttpException('No contextual ids specified.');
    }
    $ids = $request->request->all('ids');

    if (!$request->request->has('tokens')) {
      throw new BadRequestHttpException('No contextual ID tokens specified.');
    }
    $tokens = $request->request->all('tokens');

    $rendered = [];
    foreach ($ids as $key => $id) {
      if (!isset($tokens[$key]) || !hash_equals($tokens[$key], Crypt::hmacBase64($id, Settings::getHashSalt() . \Drupal::service('private_key')->get()))) {
        throw new BadRequestHttpException('Invalid contextual ID specified.');
      }
      $element = [
        '#type' => 'contextual_links',
        '#contextual_links' => $this->serializer->idToLinks($id),
      ];
      $rendered[$id] = $this->renderer->renderRoot($element);
    }

    return new JsonResponse($rendered);
  }

}
