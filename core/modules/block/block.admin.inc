<?php

/**
 * @file
 * Admin page callbacks for the block module.
 */

use Drupal\block\Entity\Block;
use Drupal\Core\Template\Attribute;

/**
 * Page callback: Attaches CSS for the block region demo.
 *
 * @see block_menu()
 *
 * @deprecated Use \Drupal\block\Controller\BlockController::demo()
 */
function block_admin_demo($theme = NULL) {
  return array(
    '#attached' => array(
      'library' => array(
        array('block', 'drupal.block.admin'),
      ),
    ),
  );
}

