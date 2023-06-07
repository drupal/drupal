<?php

namespace Drupal\Tests\block_content\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\system\Functional\Entity\EntityCacheTagsTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Content Block entity's cache tags.
 *
 * @group block_content
 */
class BlockContentCacheTagsTest extends EntityCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_content'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $block_content_type = BlockContentType::create([
      'id' => 'basic',
      'label' => 'basic',
      'revision' => FALSE,
    ]);
    $block_content_type->save();
    block_content_add_body_field($block_content_type->id());

    // Create a "Llama" content block.
    $block_content = BlockContent::create([
      'info' => 'Llama',
      'type' => 'basic',
      'body' => [
        'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
        'format' => 'plain_text',
      ],
    ]);
    $block_content->save();

    return $block_content;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\block_content\BlockContentAccessControlHandler::checkAccess()
   */
  protected function getAccessCacheContextsForEntity(EntityInterface $entity) {
    return [];
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
    $build = $this->container->get('entity_type.manager')->getViewBuilder('block')->view($block, 'block');

    // Render the block.
    // @todo The request stack manipulation won't be necessary once
    //   https://www.drupal.org/node/2367555 is fixed and the
    //   corresponding $request->isMethodCacheable() checks are removed from
    //   Drupal\Core\Render\Renderer.
    $request_stack = $this->container->get('request_stack');
    $request_stack->push(new Request());
    $this->container->get('renderer')->renderRoot($build);
    $request_stack->pop();

    // Expected keys, contexts, and tags for the block.
    // @see \Drupal\block\BlockViewBuilder::viewMultiple()
    $expected_block_cache_keys = ['entity_view', 'block', $block->id()];
    $expected_block_cache_tags = Cache::mergeTags(['block_view', 'rendered'], $block->getCacheTags());
    $expected_block_cache_tags = Cache::mergeTags($expected_block_cache_tags, $block->getPlugin()->getCacheTags());

    // Expected contexts and tags for the BlockContent entity.
    // @see \Drupal\Core\Entity\EntityViewBuilder::getBuildDefaults().
    $expected_entity_cache_tags = Cache::mergeTags(['block_content_view'], $this->entity->getCacheTags());
    $expected_entity_cache_tags = Cache::mergeTags($expected_entity_cache_tags, $this->getAdditionalCacheTagsForEntity($this->entity));

    // Verify that what was render cached matches the above expectations.
    $this->verifyRenderCache($expected_block_cache_keys, Cache::mergeTags($expected_block_cache_tags, $expected_entity_cache_tags), CacheableMetadata::createFromRenderArray($build));
  }

}
