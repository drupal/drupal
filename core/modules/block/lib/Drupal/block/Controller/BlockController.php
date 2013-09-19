<?php

/**
 * @file
 * Contains \Drupal\block\Controller\BlockController.
 */

namespace Drupal\block\Controller;

/**
 * Controller routines for block routes.
 */
class BlockController {

  /**
   * @todo Remove block_admin_demo().
   */
  public function demo($theme) {
    module_load_include('admin.inc', 'block');
    return block_admin_demo($theme);
  }

}
