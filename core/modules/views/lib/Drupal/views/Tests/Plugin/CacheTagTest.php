<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\CacheTagTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Views;

/**
 * Tests the Tag class.
 *
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
   * The node storage controller.
   *
   * @var \Drupal\node\NodeStorageController
   */
  protected $nodeStorageController;

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

  public static function getInfo() {
    return array(
      'name' => 'Cache tag',
      'description' => 'Tests tag cache plugin.',
      'group' => 'Views Plugins'
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    $this->nodeStorageController = $this->container->get('entity.manager')->getStorageController('node');
    $this->nodeViewBuilder = $this->container->get('entity.manager')->getViewBuilder('node');
    $this->userViewBuilder = $this->container->get('entity.manager')->getViewBuilder('user');

    for ($i = 1; $i <= 5; $i++) {
      $this->pages[] = $this->drupalCreateNode(array('title' => "Test $i", 'type' => 'page'));
    }
    $this->article = $this->drupalCreateNode(array('title' => "Test article", 'type' => 'article'));
    $this->user = $this->drupalCreateUser();
  }

  /**
   * Tests the tag cache plugin.
   */
  public function testTagCaching() {
    $view = Views::getView('test_tag_cache');
    $view->render();

    // Saving the view should invalidate the tags.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($cache_plugin->cacheGet('output'), 'Output cache found.');

    $view->storage->save();

    $this->assertFalse($cache_plugin->cacheGet('results'), 'Results cache empty after the view is saved.');
    $this->assertFalse($cache_plugin->cacheGet('output'), 'Output cache empty after the view is saved.');

    $view->destroy();
    $view->render();

    // Test invalidating the nodes in this view invalidates the cache.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($cache_plugin->cacheGet('output'), 'Output cache found.');

    $this->nodeViewBuilder->resetCache($this->pages);

    $this->assertFalse($cache_plugin->cacheGet('results'), 'Results cache empty after resetCache is called with pages.');
    $this->assertFalse($cache_plugin->cacheGet('output'), 'Output cache empty after resetCache is called with pages.');

    $view->destroy();
    $view->render();

    // Test saving a node in this view invalidates the cache.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($cache_plugin->cacheGet('output'), 'Output cache found.');

    $node = reset($this->pages);
    $node->save();

    $this->assertFalse($cache_plugin->cacheGet('results'), 'Results cache empty after a page node is saved.');
    $this->assertFalse($cache_plugin->cacheGet('output'), 'Output cache empty after a page node is saved.');

    $view->destroy();
    $view->render();

    // Test that invalidating a tag for a different node type, does not
    // invalidate the cache.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($cache_plugin->cacheGet('output'), 'Output cache found.');

    $this->nodeViewBuilder->resetCache(array($this->article));

    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found after an article node is invalidated.');
    $this->assertTrue($cache_plugin->cacheGet('output'), 'Output cache found after an article node is invalidated.');

    $view->destroy();
    $view->render();

    // Test that saving a node for a different node type, does not invalidate
    // the cache.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($cache_plugin->cacheGet('output'), 'Output cache found.');

    $this->article->save();

    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found after an article node is saved.');
    $this->assertTrue($cache_plugin->cacheGet('output'), 'Output cache found after an article node is saved.');

    $view->destroy();
    $view->render();

    // Test that invalidating a tag for a user, does not invalidate the cache.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($cache_plugin->cacheGet('output'), 'Output cache found.');

    $this->userViewBuilder->resetCache(array($this->user));

    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found after a user is invalidated.');
    $this->assertTrue($cache_plugin->cacheGet('output'), 'Output cache found after a user is invalidated.');

    $view->destroy();
    $view->render();

    // Test the cacheFlush method invalidates the cache.
    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertTrue($cache_plugin->cacheGet('results'), 'Results cache found.');
    $this->assertTrue($cache_plugin->cacheGet('output'), 'Output cache found.');

    $cache_plugin->cacheFlush();

    $cache_plugin = $view->display_handler->getPlugin('cache');
    $this->assertFalse($cache_plugin->cacheGet('results'), 'Results cache empty after the cacheFlush() method is called.');
    $this->assertFalse($cache_plugin->cacheGet('output'), 'Output cache empty after the cacheFlush() method is called.');
  }

}
