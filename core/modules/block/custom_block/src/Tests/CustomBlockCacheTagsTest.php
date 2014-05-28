<?php

/**
 * @file
 * Contains \Drupal\custom_block\Tests\CustomBlockCacheTagsTest.
 */

namespace Drupal\custom_block\Tests;

use Drupal\Core\Entity\EntityInterface;
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
      'body' => array(
        'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
        'format' => 'plain_text',
      ),
    ));
    $custom_block->save();

    return $custom_block;
  }

  /**
   * {@inheritdoc}
   *
   * Each comment must have a comment body, which always has a text format.
   */
  protected function getAdditionalCacheTagsForEntity(EntityInterface $entity) {
    return array('filter_format:plain_text');
  }

}
