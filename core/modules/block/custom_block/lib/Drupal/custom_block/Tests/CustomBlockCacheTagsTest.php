<?php

/**
 * @file
 * Contains \Drupal\custom_block\Tests\CustomBlockCacheTagsTest.
 */

namespace Drupal\custom_block\Tests;

use Drupal\system\Tests\Entity\EntityCacheTagsTestBase;

/**
 * Tests the Custom Block entity's cache tags.
 */
class CustomBlockCacheTagsTest extends EntityCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('custom_block');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return parent::generateStandardizedInfo('Custom Block', 'Custom Block');
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Llama" custom block.
    $custom_block = entity_create('custom_block', array(
      'info' => 'Llama',
      'type' => 'basic',
      'body' => 'The name "llama" was adopted by European settlers from native Peruvians.',
    ));
    $custom_block->save();

    return $custom_block;
  }

}
