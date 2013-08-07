<?php

/**
 * @file
 * Contains \Drupal\block\Controller\BlockListController.
 */

namespace Drupal\block\Controller;

use Drupal\Core\Entity\Controller\EntityListController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to list blocks.
 */
class BlockListController extends EntityListController {

  /**
   * Shows the block administration page.
   *
   * @param string|null $theme
   *   Theme key of block list.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing($theme = NULL) {
    $theme = $theme ?: $this->config('system.theme')->get('default');
    return $this->entityManager()->getListController('block')->render($theme);
  }

}
