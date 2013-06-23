<?php

/**
 * @file
 * Admin page callbacks for the custom block module.
 */

use Drupal\custom_block\Plugin\Core\Entity\CustomBlockType;

/**
 * Page callback: Presents the custom block type creation form.
 *
 * @return array
 *   A form array as expected by drupal_render().
 *
 * @see custom_block_menu()
 */
function custom_block_type_add() {
  $block_type = entity_create('custom_block_type', array());
  return Drupal::entityManager()->getForm($block_type);
}

/**
 * Page callback: Presents the custom block type edit form.
 *
 * @param \Drupal\custom_block\Plugin\Core\Entity\CustomBlockType $block_type
 *   The custom block type to edit.
 *
 * @return array
 *   A form array as expected by drupal_render().
 *
 * @see custom_block_menu()
 */
function custom_block_type_edit(CustomBlockType $block_type) {
  return Drupal::entityManager()->getForm($block_type);
}
