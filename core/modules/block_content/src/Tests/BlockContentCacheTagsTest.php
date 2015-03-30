<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\BlockContentCacheTagsTest.
 */

namespace Drupal\block_content\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\system\Tests\Entity\EntityCacheTagsTestBase;
use Symfony\Component\HttpFoundation\Request;

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
    $block_content_type = entity_create('block_content_type', array(
      'id' => 'basic',
      'label' => 'basic',
      'revision' => FALSE
    ));
    $block_content_type->save();
    block_content_add_body_field($block_content_type->id());

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
    return ['config:filter.format.plain_text'];
  }

  /**
   * Tests that the block is cached with the correct contexts and tags.
   */
  public function testBlock() {
    $block = $this->drupalPlaceBlock('block_content:' . $this->entity->uuid());
    $build = $this->container->get('entity.manager')->getViewBuilder('block')->view($block, 'block');

    // Render the block.
    // @todo The request stack manipulation won't be necessary once
    //   https://www.drupal.org/node/2367555 is fixed and the
    //   corresponding $request->isMethodSafe() checks are removed from
    //   Drupal\Core\Render\Renderer.
    $request_stack = $this->container->get('request_stack');
    $request_stack->push(new Request());
    $this->container->get('renderer')->render($build);
    $request_stack->pop();

    // Expected keys, contexts, and tags for the block.
    // @see \Drupal\block\BlockViewBuilder::viewMultiple()
    $expected_block_cache_keys = ['entity_view', 'block', $block->id()];
    $expected_block_cache_contexts = ['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme'];
    $expected_block_cache_tags = Cache::mergeTags(['block_view', 'rendered'], $block->getCacheTags(), $block->getPlugin()->getCacheTags());

    // Expected contexts and tags for the BlockContent entity.
    // @see \Drupal\Core\Entity\EntityViewBuilder::getBuildDefaults().
    $expected_entity_cache_contexts = ['theme', 'user.roles'];
    $expected_entity_cache_tags = Cache::mergeTags(['block_content_view'], $this->entity->getCacheTags(), $this->getAdditionalCacheTagsForEntity($this->entity));

    // Verify that what was render cached matches the above expectations.
    $cid = $this->createCacheId($expected_block_cache_keys, $expected_block_cache_contexts);
    $redirected_cid = $this->createCacheId($expected_block_cache_keys, Cache::mergeContexts($expected_block_cache_contexts, $expected_entity_cache_contexts));
    $this->verifyRenderCache($cid, Cache::mergeTags($expected_block_cache_tags, $expected_entity_cache_tags), $redirected_cid);
  }
}
