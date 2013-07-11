<?php

/**
 * @file
 * Admin page callbacks for the block module.
 */

use Drupal\block\Plugin\Core\Entity\Block;
use Drupal\Core\Template\Attribute;

/**
 * Page callback: Attaches CSS for the block region demo.
 *
 * @see block_menu()
 */
function block_admin_demo($theme = NULL) {
  return array(
    '#attached' => array(
      'css' => array(drupal_get_path('module', 'block') . '/css/block.admin.css'),
    ),
  );
}

