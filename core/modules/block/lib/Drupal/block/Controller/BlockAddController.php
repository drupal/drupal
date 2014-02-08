<?php

/**
 * @file
 * Contains \Drupal\block\Controller\BlockAddController.
 */

namespace Drupal\block\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for building the block instance add form.
 */
class BlockAddController extends ControllerBase {

  /**
   * Build the block instance add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the block instance.
   * @param string $theme
   *   The name of the theme for the block instance.
   *
   * @return array
   *   The block instance edit form.
   */
  public function blockAddConfigureForm($plugin_id, $theme) {
    // Create a block entity.
    $entity = $this->entityManager()->getStorageController('block')->create(array('plugin' => $plugin_id, 'theme' => $theme));

    return $this->entityFormBuilder()->getForm($entity);
  }

}
