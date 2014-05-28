<?php

/**
 * @file
 * Contains \Drupal\contextual\ContextualController.
 */

namespace Drupal\contextual;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Core\Entity\EntityInterface;

/**
 * Returns responses for Contextual module routes.
 */
class ContextualController implements ContainerAwareInterface {

  use ContainerAwareTrait;

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

    $rendered = array();
    foreach ($ids as $id) {
      $element = array(
        '#type' => 'contextual_links',
        '#contextual_links' => _contextual_id_to_links($id),
      );
      $rendered[$id] = drupal_render($element);
    }

    return new JsonResponse($rendered);
  }

}
