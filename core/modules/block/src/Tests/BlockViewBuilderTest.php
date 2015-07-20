<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockViewBuilderTest.
 */

namespace Drupal\block\Tests;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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
  public static $modules = array('block', 'block_test', 'system', 'user');

  /**
   * The block being tested.
   *
   * @var \Drupal\block\Entity\BlockInterface
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
  protected function setUp() {
    parent::setUp();

    $this->controller = $this->container
      ->get('entity.manager')
      ->getStorage('block');

    \Drupal::state()->set('block_test.content', 'Llamas &gt; unicorns!');

    // Create a block with only required values.
    $this->block = $this->controller->create(array(
      'id' => 'test_block',
      'theme' => 'stark',
      'plugin' => 'test_cache',
    ));
    $this->block->save();

    $this->container->get('cache.render')->deleteAll();

    $this->renderer = $this->container->get('renderer');
  }

  /**
   * Tests the rendering of blocks.
   */
  public function testBasicRendering() {
    \Drupal::state()->set('block_test.content', '');

    $entity = $this->controller->create(array(
      'id' => 'test_block1',
      'theme' => 'stark',
      'plugin' => 'test_html',
    ));
    $entity->save();

    // Test the rendering of a block.
    $entity = Block::load('test_block1');
    $output = entity_view($entity, 'block');
    $expected = array();
    $expected[] = '<div id="block-test-block1">';
    $expected[] = '  ';
    $expected[] = '    ';
    $expected[] = '      ';
    $expected[] = '  </div>';
    $expected[] = '';
    $expected_output = implode("\n", $expected);
    $this->assertEqual($this->renderer->renderRoot($output), $expected_output);

    // Reset the HTML IDs so that the next render is not affected.
    Html::resetSeenIds();

    // Test the rendering of a block with a given title.
    $entity = $this->controller->create(array(
      'id' => 'test_block2',
      'theme' => 'stark',
      'plugin' => 'test_html',
      'settings' => array(
        'label' => 'Powered by Bananas',
      ),
    ));
    $entity->save();
    $output = entity_view($entity, 'block');
    $expected = array();
    $expected[] = '<div id="block-test-block2">';
    $expected[] = '  ';
    $expected[] = '      <h2>Powered by Bananas</h2>';
    $expected[] = '    ';
    $expected[] = '      ';
    $expected[] = '  </div>';
    $expected[] = '';
    $expected_output = implode("\n", $expected);
    $this->assertEqual($this->renderer->renderRoot($output), $expected_output);
  }

  /**
   * Tests block render cache handling.
   */
  public function testBlockViewBuilderCache() {
    // Verify cache handling for a non-empty block.
    $this->verifyRenderCacheHandling();

    // Create an empty block.
    $this->block = $this->controller->create(array(
      'id' => 'test_block',
      'theme' => 'stark',
      'plugin' => 'test_cache',
    ));
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
    // Force a request via GET so we can test the render cache.
    $request = \Drupal::request();
    $request_method = $request->server->get('REQUEST_METHOD');
    $request->setMethod('GET');

    // Test that a cache entry is created.
    $build = $this->getBlockRenderArray();
    $cid = 'entity_view:block:test_block:' . implode(':', \Drupal::service('cache_contexts_manager')->convertTokensToKeys(['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'user.permissions'])->getKeys());
    $this->renderer->renderRoot($build);
    $this->assertTrue($this->container->get('cache.render')->get($cid), 'The block render element has been cached.');

    // Re-save the block and check that the cache entry has been deleted.
    $this->block->save();
    $this->assertFalse($this->container->get('cache.render')->get($cid), 'The block render cache entry has been cleared when the block was saved.');

    // Rebuild the render array (creating a new cache entry in the process) and
    // delete the block to check the cache entry is deleted.
    unset($build['#printed']);
    // Re-add the block because \Drupal\block\BlockViewBuilder::buildBlock()
    // removes it.
    $build['#block'] = $this->block;

    $this->renderer->renderRoot($build);
    $this->assertTrue($this->container->get('cache.render')->get($cid), 'The block render element has been cached.');
    $this->block->delete();
    $this->assertFalse($this->container->get('cache.render')->get($cid), 'The block render cache entry has been cleared when the block was deleted.');

    // Restore the previous request method.
    $request->setMethod($request_method);
  }

  /**
   * Tests block view altering.
   */
  public function testBlockViewBuilderAlter() {
    // Establish baseline.
    $build = $this->getBlockRenderArray();
    $this->assertIdentical((string) $this->renderer->renderRoot($build), 'Llamas &gt; unicorns!');

    // Enable the block view alter hook that adds a suffix, for basic testing.
    \Drupal::state()->set('block_test_view_alter_suffix', TRUE);
    Cache::invalidateTags($this->block->getCacheTagsToInvalidate());
    $build = $this->getBlockRenderArray();
    $this->assertTrue(isset($build['#suffix']) && $build['#suffix'] === '<br>Goodbye!', 'A block with content is altered.');
    $this->assertIdentical((string) $this->renderer->renderRoot($build), 'Llamas &gt; unicorns!<br>Goodbye!');
    \Drupal::state()->set('block_test_view_alter_suffix', FALSE);

    // Force a request via GET so we can test the render cache.
    $request = \Drupal::request();
    $request_method = $request->server->get('REQUEST_METHOD');
    $request->setMethod('GET');

    \Drupal::state()->set('block_test.content', NULL);
    Cache::invalidateTags($this->block->getCacheTagsToInvalidate());

    $default_keys = array('entity_view', 'block', 'test_block');
    $default_tags = array('block_view', 'config:block.block.test_block');

    // Advanced: cached block, but an alter hook adds an additional cache key.
    $alter_add_key = $this->randomMachineName();
    \Drupal::state()->set('block_test_view_alter_cache_key', $alter_add_key);
    $cid = 'entity_view:block:test_block:' . $alter_add_key . ':' . implode(':', \Drupal::service('cache_contexts_manager')->convertTokensToKeys(['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'user.permissions'])->getKeys());
    $expected_keys = array_merge($default_keys, array($alter_add_key));
    $build = $this->getBlockRenderArray();
    $this->assertIdentical($expected_keys, $build['#cache']['keys'], 'An altered cacheable block has the expected cache keys.');
    $this->assertIdentical((string) $this->renderer->renderRoot($build), '');
    $cache_entry = $this->container->get('cache.render')->get($cid);
    $this->assertTrue($cache_entry, 'The block render element has been cached with the expected cache ID.');
    $expected_tags = array_merge($default_tags, ['rendered']);
    sort($expected_tags);
    $this->assertIdentical($cache_entry->tags, $expected_tags, 'The block render element has been cached with the expected cache tags.');
    $this->container->get('cache.render')->delete($cid);

    // Advanced: cached block, but an alter hook adds an additional cache tag.
    $alter_add_tag = $this->randomMachineName();
    \Drupal::state()->set('block_test_view_alter_cache_tag', $alter_add_tag);
    $expected_tags = Cache::mergeTags($default_tags, array($alter_add_tag));
    $build = $this->getBlockRenderArray();
    sort($build['#cache']['tags']);
    $this->assertIdentical($expected_tags, $build['#cache']['tags'], 'An altered cacheable block has the expected cache tags.');
    $this->assertIdentical((string) $this->renderer->renderRoot($build), '');
    $cache_entry = $this->container->get('cache.render')->get($cid);
    $this->assertTrue($cache_entry, 'The block render element has been cached with the expected cache ID.');
    $expected_tags = array_merge($default_tags, [$alter_add_tag, 'rendered']);
    sort($expected_tags);
    $this->assertIdentical($cache_entry->tags, $expected_tags, 'The block render element has been cached with the expected cache tags.');
    $this->container->get('cache.render')->delete($cid);

    // Advanced: cached block, but an alter hook adds a #pre_render callback to
    // alter the eventual content.
    \Drupal::state()->set('block_test_view_alter_append_pre_render_prefix', TRUE);
    $build = $this->getBlockRenderArray();
    $this->assertFalse(isset($build['#prefix']), 'The appended #pre_render callback has not yet run before rendering.');
    $this->assertIdentical((string) $this->renderer->renderRoot($build), 'Hiya!<br>');
    $this->assertTrue(isset($build['#prefix']) && $build['#prefix'] === 'Hiya!<br>', 'A cached block without content is altered.');

    // Restore the previous request method.
    $request->setMethod($request_method);
  }

  /**
   * Get a fully built render array for a block.
   *
   * @return array
   *   The render array.
   */
  protected function getBlockRenderArray() {
    $build = $this->container->get('entity.manager')->getViewBuilder('block')->view($this->block, 'block');

    // Mock the build array to not require the theme registry.
    unset($build['#theme']);

    return $build;
  }

}
