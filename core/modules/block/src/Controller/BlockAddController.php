<?php

namespace Drupal\block\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Routing\Attribute\Route;

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
  #[Route(
    path: '/admin/structure/block/add/{plugin_id}/{theme}',
    name: 'block.admin_add',
    requirements: ['_permission' => 'administer blocks'],
    defaults: [
      'theme' => NULL,
      '_title' => new TranslatableMarkup('Configure block'),
    ],
  )]
  public function blockAddConfigureForm($plugin_id, $theme) {
    // Create a block entity.
    $entity = $this->entityTypeManager()->getStorage('block')->create(['plugin' => $plugin_id, 'theme' => $theme]);

    return $this->entityFormBuilder()->getForm($entity);
  }

}
