<?php

/**
 * @file
 * Contains \Drupal\simpletest\BlockCreationTrait.
 */

namespace Drupal\simpletest;

use Drupal\block\Entity\Block;

/**
 * Provides methods to create and place block with default settings.
 *
 * This trait is meant to be used only by test classes.
 */
trait BlockCreationTrait {

  /**
   * Creates a block instance based on default settings.
   *
   * @param string $plugin_id
   *   The plugin ID of the block type for this block instance.
   * @param array $settings
   *   (optional) An associative array of settings for the block entity.
   *   Override the defaults by specifying the key and value in the array, for
   *   example:
   *   @code
   *     $this->drupalPlaceBlock('system_powered_by_block', array(
   *       'label' => t('Hello, world!'),
   *     ));
   *   @endcode
   *   The following defaults are provided:
   *   - label: Random string.
   *   - ID: Random string.
   *   - region: 'sidebar_first'.
   *   - theme: The default theme.
   *   - visibility: Empty array.
   *
   * @return \Drupal\block\Entity\Block
   *   The block entity.
   *
   * @todo
   *   Add support for creating custom block instances.
   */
  protected function placeBlock($plugin_id, array $settings = array()) {
    $config = \Drupal::configFactory();
    $settings += array(
      'plugin' => $plugin_id,
      'region' => 'sidebar_first',
      'id' => strtolower($this->randomMachineName(8)),
      'theme' => $config->get('system.theme')->get('default'),
      'label' => $this->randomMachineName(8),
      'visibility' => array(),
      'weight' => 0,
    );
    $values = [];
    foreach (array('region', 'id', 'theme', 'plugin', 'weight', 'visibility') as $key) {
      $values[$key] = $settings[$key];
      // Remove extra values that do not belong in the settings array.
      unset($settings[$key]);
    }
    foreach ($values['visibility'] as $id => $visibility) {
      $values['visibility'][$id]['id'] = $id;
    }
    $values['settings'] = $settings;
    $block = Block::create($values);
    $block->save();
    return $block;
  }

}
