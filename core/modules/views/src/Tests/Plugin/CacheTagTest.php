<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\CacheTagTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\Core\Cache\Cache;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests tag cache plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\cache\Tag
 */
class CacheTagTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_tag_cache');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * The node view builder.
   *
   * @var \Drupal\node\NodeViewBuilder
   */
  protected $nodeViewBuilder;

  /**
   * The user view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilder
   */
  protected $userViewBuilder;

  /**
   * An array of page nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $pages;

  /**
   * An article node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $article;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    $this->nodeStorage = $this->container->get('entity.manager')->getStorage('node');
    $this->nodeViewBuilder = $this->container->get('entity.manager')->getViewBuilder('node');
    $this->userViewBuilder = $this->container->get('entity.manager')->getViewBuilder('user');

    for ($i = 1; $i <= 5; $i++) {
      $this->pages[] = $this->drupalCreateNode(array('title' => "Test $i", 'type' => 'page'));
    }
    $this->article = $this->drupalCreateNode(array('title' => "Test article", 'type' => 'article'));
    $this->user = $this->drupalCreateUser();

    // Mark the current request safe, in order to make render cache working, see
    // \Drupal\Core\Render\RenderCache::get.
    \Drupal::request()->setMethod('GET');
  }

  /**
   * Gets the render cache for a given view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   *
   * @return array|FALSE
   *   The render cache result or FALSE if not existent.
   */
  protected function getRenderCache(ViewExecutable $view) {
    /** @var \Drupal\Core\Render\RenderCacheInterface $render_cache */
    $render_cache = \Drupal::service('render_cache');
    $view->element = ['#cache' => []];
    $build = $view->buildRenderable();
    $build['#cache']['contexts'] = Cache::mergeContexts($build['#cache']['contexts'], $this->container->getParameter('renderer.config')['required_cache_contexts']);

    return $render_cache->get($build);
  }

  /**
   * Tests the tag cache plugin.
   */
  public function testTagCaching() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $view = Views::getView('test_tag_cache');
    $build = $view->buildRenderable();
    $renderer->renderPlain($build);

    // Saving the view should invalidate the tags.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($this->getRenderCache($view), 'Output cache found.');

    $view->storage->save();

    $this->assertFalse($cache_plugin->cacheGet('results'), 'Results cache empty after the view is saved.');
    $this->assertFalse($this->getRenderCache($view), 'Output cache empty after the view is saved.');

    $view->destroy();
    $build = $view->buildRenderable();
    $renderer->renderPlain($build);

    // Test invalidating the nodes in this view invalidates the cache.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($this->getRenderCache($view), 'Output cache found.');

    $this->nodeViewBuilder->resetCache($this->pages);

    $this->assertFalse($cache_plugin->cacheGet('results'), 'Results cache empty after resetCache is called with pages.');
    $this->assertFalse($this->getRenderCache($view), 'Output cache empty after resetCache is called with pages.');

    $view->destroy();
    $build = $view->buildRenderable();
    $renderer->renderPlain($build);

    // Test saving a node in this view invalidates the cache.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($this->getRenderCache($view), 'Output cache found.');

    $node = reset($this->pages);
    $node->save();

    $this->assertFalse($cache_plugin->cacheGet('results'), 'Results cache empty after a page node is saved.');
    $this->assertFalse($this->getRenderCache($view), 'Output cache empty after a page node is saved.');

    $view->destroy();
    $build = $view->buildRenderable();
    $renderer->renderPlain($build);

    // Test saving a node not in this view invalidates the cache too.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($this->getRenderCache($view), 'Output cache found.');

    $this->article->save();

    $this->assertFalse($cache_plugin->cacheGet('results'), 'Results cache empty after an article node is saved.');
    $this->assertFalse($this->getRenderCache($view), 'Output cache empty after an article node is saved.');

    $view->destroy();
    $build = $view->buildRenderable();
    $renderer->renderPlain($build);

    // Test that invalidating a tag for a user, does not invalidate the cache,
    // as the user entity type will not be contained in the views cache tags.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($this->getRenderCache($view), 'Output cache found.');

    $this->userViewBuilder->resetCache(array($this->user));

    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found after a user is invalidated.');
    $this->assertTrue($this->getRenderCache($view), 'Output cache found after a user is invalidated.');

    $view->destroy();
    // Invalidate the views cache tags in order to invalidate the render
    // caching.
    \Drupal::service('cache_tags.invalidator')->invalidateTags($view->storage->getCacheTags());
    $build = $view->buildRenderable();
    $renderer->renderPlain($build);

    // Test the cacheFlush method invalidates the cache.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($this->getRenderCache($view), 'Output cache found.');

    $cache_plugin->cacheFlush();

    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertFalse($cache_plugin->cacheGet('results'), 'Results cache empty after the cacheFlush() method is called.');
    $this->assertFalse($this->getRenderCache($view), 'Output cache empty after the cacheFlush() method is called.');
  }

}
