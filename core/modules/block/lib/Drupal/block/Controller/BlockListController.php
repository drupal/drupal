<?php

/**
 * @file
 * Contains \Drupal\block\Controller\BlockListController.
 */

namespace Drupal\block\Controller;

use Drupal\Core\Entity\Controller\EntityListController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller to list blocks.
 */
class BlockListController extends EntityListController {

  /**
   * Shows the block administration page.
   *
   * @param string|null $theme
   *   Theme key of block list.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing($theme = NULL, Request $request = NULL) {
    $theme = $theme ?: $this->config('system.theme')->get('default');
    return $this->entityManager()->getListController('block')->render($theme, $request);
  }

}
