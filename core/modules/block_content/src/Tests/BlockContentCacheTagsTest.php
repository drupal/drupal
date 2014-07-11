<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\BlockContentCacheTagsTest.
 */

namespace Drupal\block_content\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\system\Tests\Entity\EntityCacheTagsTestBase;

/**
 * Tests the Custom Block entity's cache tags.
 *
 * @group block_content
 */
class BlockContentCacheTagsTest extends EntityCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('block_content');

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Llama" custom block.
    $block_content = entity_create('block_content', array(
      'info' => 'Llama',
      'type' => 'basic',
      'body' => array(
        'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
        'format' => 'plain_text',
      ),
    ));
    $block_content->save();

    return $block_content;
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
