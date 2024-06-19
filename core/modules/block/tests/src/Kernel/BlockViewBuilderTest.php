<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\block\Entity\Block;

/**
 * Tests the block view builder.
 *
 * @group block
 */
class BlockViewBuilderTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'block_test', 'system', 'user'];

  /**
   * The block being tested.
   *
   * @var \Drupal\block\BlockInterface
   */
  protected $block;

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $controller;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('theme_installer')->install(['stark']);
    $this->controller = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');

    \Drupal::state()->set('block_test.content', 'Llamas &gt; unicorns!');

    // Create a block with only required values.
    $this->block = $this->controller->create([
      'id' => 'test_block',
      'theme' => 'stark',
      'plugin' => 'test_cache',
    ]);
    $this->block->save();

    $this->container->get('cache.render')->deleteAll();

    $this->renderer = $this->container->get('renderer');
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testBasicRendering(): void {
    \Drupal::state()->set('block_test.content', '');

    $entity = $this->controller->create([
      'id' => 'test_block1',
      'theme' => 'stark',
      'plugin' => 'test_html',
    ]);
    $entity->save();

    // Test the rendering of a block.
    $entity = Block::load('test_block1');
    $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $output = $builder->view($entity, 'block');
    $expected = [];
    $expected[] = '<div id="block-test-block1">';
    $expected[] = '  ';
    $expected[] = '    ';
    $expected[] = '      ';
    $expected[] = '  </div>';
    $expected[] = '';
    $expected_output = implode("\n", $expected);
    $this->assertSame($expected_output, (string) $this->renderer->renderRoot($output));

    // Reset the HTML IDs so that the next render is not affected.
    Html::resetSeenIds();

    // Test the rendering of a block with a given title.
    $entity = $this->controller->create([
      'id' => 'test_block2',
      'theme' => 'stark',
      'plugin' => 'test_html',
      'settings' => [
        'label' => 'Powered by Bananas',
      ],
    ]);
    $entity->save();
    $output = $builder->view($entity, 'block');
    $expected = [];
    $expected[] = '<div id="block-test-block2">';
    $expected[] = '  ';
    $expected[] = '      <h2>Powered by Bananas</h2>';
    $expected[] = '    ';
    $expected[] = '      ';
    $expected[] = '  </div>';
    $expected[] = '';
    $expected_output = implode("\n", $expected);
    $this->assertSame($expected_output, (string) $this->renderer->renderRoot($output));
  }

  /**
   * Tests block render cache handling.
   */
  public function testBlockViewBuilderCache(): void {
    // Verify cache handling for a non-empty block.
    $this->verifyRenderCacheHandling();

    // Create an empty block.
    $this->block = $this->controller->create([
      'id' => 'test_block',
      'theme' => 'stark',
      'plugin' => 'test_cache',
    ]);
    $this->block->save();
    \Drupal::state()->set('block_test.content', NULL);

    // Verify cache handling for an empty block.
    $this->verifyRenderCacheHandling();
  }

  /**
   * Verifies render cache handling of the block being tested.
   *
   * @see ::testBlockViewBuilderCache()
   */
  protected function verifyRenderCacheHandling() {
    /** @var \Drupal\Core\Cache\VariationCacheFactoryInterface $variation_cache_factory */
    $variation_cache_factory = $this->container->get('variation_cache_factory');
    $cache_bin = $variation_cache_factory->get('render');

    // Force a request via GET so we can test the render cache.
    $request = \Drupal::request();
    $request_method = $request->server->get('REQUEST_METHOD');
    $request->setMethod('GET');

    // Test that a cache entry is created.
    $build = $this->getBlockRenderArray();
    $cache_keys = ['entity_view', 'block', 'test_block'];
    $this->renderer->renderRoot($build);
    $this->assertNotEmpty($cache_bin->get($cache_keys, CacheableMetadata::createFromRenderArray($build)), 'The block render element has been cached.');

    // Re-save the block and check that the cache entry has been deleted.
    $this->block->save();
    $this->assertFalse($cache_bin->get($cache_keys, CacheableMetadata::createFromRenderArray($build)), 'The block render cache entry has been cleared when the block was saved.');

    // Rebuild the render array (creating a new cache entry in the process) and
    // delete the block to check the cache entry is deleted.
    unset($build['#printed']);
    // Re-add the block because \Drupal\block\BlockViewBuilder::buildBlock()
    // removes it.
    $build['#block'] = $this->block;

    $this->renderer->renderRoot($build);
    $this->assertNotEmpty($cache_bin->get($cache_keys, CacheableMetadata::createFromRenderArray($build)), 'The block render element has been cached.');
    $this->block->delete();
    $this->assertFalse($cache_bin->get($cache_keys, CacheableMetadata::createFromRenderArray($build)), 'The block render cache entry has been cleared when the block was deleted.');

    // Restore the previous request method.
    $request->setMethod($request_method);
  }

  /**
   * Tests block view altering.
   *
   * @see hook_block_view_alter()
   * @see hook_block_view_BASE_BLOCK_ID_alter()
   */
  public function testBlockViewBuilderViewAlter(): void {
    // Establish baseline.
    $build = $this->getBlockRenderArray();
    $this->setRawContent((string) $this->renderer->renderRoot($build));
    $this->assertSame('Llamas > unicorns!', trim((string) $this->cssSelect('div')[0]));

    // Enable the block view alter hook that adds a foo=bar attribute.
    \Drupal::state()->set('block_test_view_alter_suffix', TRUE);
    Cache::invalidateTags($this->block->getCacheTagsToInvalidate());
    $build = $this->getBlockRenderArray();
    $this->setRawContent((string) $this->renderer->renderRoot($build));
    $this->assertSame('Llamas > unicorns!', trim((string) $this->cssSelect('[foo=bar]')[0]));
    \Drupal::state()->set('block_test_view_alter_suffix', FALSE);

    \Drupal::state()->set('block_test.content', NULL);
    Cache::invalidateTags($this->block->getCacheTagsToInvalidate());

    // Advanced: cached block, but an alter hook adds a #pre_render callback to
    // alter the eventual content.
    \Drupal::state()->set('block_test_view_alter_append_pre_render_prefix', TRUE);
    $build = $this->getBlockRenderArray();
    $this->assertFalse(isset($build['#prefix']), 'The appended #pre_render callback has not yet run before rendering.');
    $this->assertSame('Hiya!<br>', (string) $this->renderer->renderRoot($build));
    // Check that a cached block without content is altered.
    $this->assertArrayHasKey('#prefix', $build);
    $this->assertSame('Hiya!<br>', $build['#prefix']);
  }

  /**
   * Tests block build altering.
   *
   * @see hook_block_build_alter()
   * @see hook_block_build_BASE_BLOCK_ID_alter()
   */
  public function testBlockViewBuilderBuildAlter(): void {
    // Force a request via GET so we can test the render cache.
    $request = \Drupal::request();
    $request_method = $request->server->get('REQUEST_METHOD');
    $request->setMethod('GET');

    $default_keys = ['entity_view', 'block', 'test_block'];
    $default_contexts = [];
    $default_tags = ['block_view', 'config:block.block.test_block'];
    $default_max_age = Cache::PERMANENT;

    // hook_block_build_alter() adds an additional cache key.
    $alter_add_key = $this->randomMachineName();
    \Drupal::state()->set('block_test_block_alter_cache_key', $alter_add_key);
    $this->assertBlockRenderedWithExpectedCacheability(array_merge($default_keys, [$alter_add_key]), $default_contexts, $default_tags, $default_max_age);
    \Drupal::state()->set('block_test_block_alter_cache_key', NULL);

    // hook_block_build_alter() adds an additional cache context.
    $alter_add_context = 'url.query_args:' . $this->randomMachineName();
    \Drupal::state()->set('block_test_block_alter_cache_context', $alter_add_context);
    $this->assertBlockRenderedWithExpectedCacheability($default_keys, Cache::mergeContexts($default_contexts, [$alter_add_context]), $default_tags, $default_max_age);
    \Drupal::state()->set('block_test_block_alter_cache_context', NULL);

    // hook_block_build_alter() adds an additional cache tag.
    $alter_add_tag = $this->randomMachineName();
    \Drupal::state()->set('block_test_block_alter_cache_tag', $alter_add_tag);
    $this->assertBlockRenderedWithExpectedCacheability($default_keys, $default_contexts, Cache::mergeTags($default_tags, [$alter_add_tag]), $default_max_age);
    \Drupal::state()->set('block_test_block_alter_cache_tag', NULL);

    // hook_block_build_alter() alters the max-age.
    $alter_max_age = 300;
    \Drupal::state()->set('block_test_block_alter_cache_max_age', $alter_max_age);
    $this->assertBlockRenderedWithExpectedCacheability($default_keys, $default_contexts, $default_tags, $alter_max_age);
    \Drupal::state()->set('block_test_block_alter_cache_max_age', NULL);

    // hook_block_build_alter() alters cache keys, contexts, tags and max-age.
    \Drupal::state()->set('block_test_block_alter_cache_key', $alter_add_key);
    \Drupal::state()->set('block_test_block_alter_cache_context', $alter_add_context);
    \Drupal::state()->set('block_test_block_alter_cache_tag', $alter_add_tag);
    \Drupal::state()->set('block_test_block_alter_cache_max_age', $alter_max_age);
    $this->assertBlockRenderedWithExpectedCacheability(array_merge($default_keys, [$alter_add_key]), Cache::mergeContexts($default_contexts, [$alter_add_context]), Cache::mergeTags($default_tags, [$alter_add_tag]), $alter_max_age);
    \Drupal::state()->set('block_test_block_alter_cache_key', NULL);
    \Drupal::state()->set('block_test_block_alter_cache_context', NULL);
    \Drupal::state()->set('block_test_block_alter_cache_tag', NULL);
    \Drupal::state()->set('block_test_block_alter_cache_max_age', NULL);

    // hook_block_build_alter() sets #create_placeholder.
    foreach ([TRUE, FALSE] as $value) {
      \Drupal::state()->set('block_test_block_alter_create_placeholder', $value);
      $build = $this->getBlockRenderArray();
      $this->assertTrue(isset($build['#create_placeholder']));
      $this->assertSame($value, $build['#create_placeholder']);
    }
    \Drupal::state()->set('block_test_block_alter_create_placeholder', NULL);

    // Restore the previous request method.
    $request->setMethod($request_method);
  }

  /**
   * Asserts that a block is built/rendered/cached with expected cacheability.
   *
   * @param string[] $expected_keys
   *   The expected cache keys.
   * @param string[] $expected_contexts
   *   The expected cache contexts.
   * @param string[] $expected_tags
   *   The expected cache tags.
   * @param int $expected_max_age
   *   The expected max-age.
   *
   * @internal
   */
  protected function assertBlockRenderedWithExpectedCacheability(array $expected_keys, array $expected_contexts, array $expected_tags, int $expected_max_age): void {
    /** @var \Drupal\Core\Cache\VariationCacheFactoryInterface $variation_cache_factory */
    $variation_cache_factory = $this->container->get('variation_cache_factory');
    $cache_bin = $variation_cache_factory->get('render');

    $required_cache_contexts = ['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'user.permissions'];

    // Check that the expected cacheability metadata is present in:
    // - the built render array;
    $build = $this->getBlockRenderArray();
    $this->assertSame($expected_keys, $build['#cache']['keys']);
    $this->assertEqualsCanonicalizing($expected_contexts, $build['#cache']['contexts']);
    $this->assertEqualsCanonicalizing($expected_tags, $build['#cache']['tags']);
    $this->assertSame($expected_max_age, $build['#cache']['max-age']);
    $this->assertFalse(isset($build['#create_placeholder']));
    // - the rendered render array;
    $this->renderer->renderRoot($build);
    // - the render cache item.
    $final_cache_contexts = Cache::mergeContexts($expected_contexts, $required_cache_contexts);
    $cache_item = $cache_bin->get($expected_keys, CacheableMetadata::createFromRenderArray($build));
    $this->assertNotEmpty($cache_item, 'The block render element has been cached with the expected cache keys.');
    $this->assertEqualsCanonicalizing(Cache::mergeTags($expected_tags, ['rendered']), $cache_item->tags);
    $this->assertEqualsCanonicalizing($final_cache_contexts, $cache_item->data['#cache']['contexts']);
    $this->assertEqualsCanonicalizing($expected_tags, $cache_item->data['#cache']['tags']);
    $this->assertSame($expected_max_age, $cache_item->data['#cache']['max-age']);

    $cache_bin->delete($expected_keys, CacheableMetadata::createFromRenderArray($build));
  }

  /**
   * Get a fully built render array for a block.
   *
   * @return array
   *   The render array.
   */
  protected function getBlockRenderArray() {
    return $this->container->get('entity_type.manager')->getViewBuilder('block')->view($this->block, 'block');
  }

}
