<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Plugin\CacheDecoratorTest.
 */

namespace Drupal\system\Tests\Plugin;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\system\Tests\Plugin\Discovery\DiscoveryTestBase;
use Drupal\Component\Plugin\Discovery\StaticDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * Tests \Drupal\Core\Plugin\Discovery\CacheDecorator behavior.
 */
class CacheDecoratorTest extends DiscoveryTestBase {

  /**
   * The cache bin.
   *
   * @var string
   */
  protected $cacheBin = 'test_cacheDecorator';

  /**
   * The cache key.
   *
   * @var string
   */
  protected $cacheKey = 'test_cacheDecorator';

  public static function getInfo() {
    return array(
      'name' => 'CacheDecorator',
      'description' => 'Tests the CacheDecorator.',
      'group' => 'Plugin API',
    );
  }

  public function setUp() {

    parent::setUp();

    // Use a non-db cache backend, so that we can use DiscoveryTestBase (which
    // extends UnitTestBase).
    // @todo switch to injecting the MemoryBackend http://drupal.org/node/1903346
    \Drupal::getContainer()->set("cache.$this->cacheBin", new MemoryBackend($this->cacheBin));

    // Create discovery objects to test.
    $this->emptyDiscovery = new StaticDiscovery();
    $this->emptyDiscovery = new CacheDecorator($this->emptyDiscovery, $this->cacheKey . '_empty', $this->cacheBin);

    $this->discovery = new StaticDiscovery();
    $this->discovery = new CacheDecorator($this->discovery, $this->cacheKey, $this->cacheBin);

    // Populate sample definitions.
    $this->expectedDefinitions = array(
      'apple' => array(
        'label' => 'Apple',
        'color' => 'green',
      ),
      'cherry' => array(
        'label' => 'Cherry',
        'color' => 'red',
      ),
      'orange' => array(
        'label' => 'Orange',
        'color' => 'orange',
      ),
    );
    foreach ($this->expectedDefinitions as $plugin_id => $definition) {
      $this->discovery->setDefinition($plugin_id, $definition);
    }
  }

  /**
   * Tests that discovered definitions are properly cached.
   *
   * This comes in addition to DiscoveryTestBase::testDiscoveryInterface(),
   * that test the basic discovery behavior.
   */
  public function testCachedDefinitions() {
    $cache = \Drupal::cache($this->cacheBin);

    // Check that nothing is cached initially.
    $cached = $cache->get($this->cacheKey);
    $this->assertIdentical($cached, FALSE, 'Cache is empty.');

    // Get the definitions once, and check that they are present in the cache.
    $definitions = $this->discovery->getDefinitions();
    $this->assertIdentical($definitions, $this->expectedDefinitions, 'Definitions are correctly retrieved.');
    $cached = $cache->get($this->cacheKey);
    $this->assertIdentical($cached->data, $this->expectedDefinitions, 'Definitions are cached.');

    // Check that the definitions are also cached in memory. Since the
    // CacheDecorator::definitions property is protected, this is tested "from
    // the outside" by wiping the cache entry, getting the definitions, and
    // checking that the cache entry was not regenerated (thus showing that
    // defintions were not fetched from the decorated discovery).
    $cache->delete($this->cacheKey);
    $definitions = $this->discovery->getDefinitions();
    $cached = $cache->get($this->cacheKey);
    $this->assertIdentical($cached, FALSE, 'Cache is empty.');
    $this->assertIdentical($definitions, $this->expectedDefinitions, 'Definitions are cached in memory.');
  }

  /**
   * Tests CacheDecorator::clearCachedDefinitions().
   */
  public function testClearCachedDefinitions() {
    $cache = \Drupal::cache($this->cacheBin);

    // Populate the caches by collecting definitions once.
    $this->discovery->getDefinitions();

    // Add a new definition.
    $this->expectedDefinitions['banana'] = array(
      'label' => 'Banana',
      'color' => 'yellow',
    );
    $this->discovery->setDefinition('banana', $this->expectedDefinitions['banana']);

    // Check that the new definition is not found.
    $definition = $this->discovery->getDefinition('banana', FALSE);
    $this->assertNull($definition, 'Newly added definition is not found.');

    // Clear cached definitions, and check that the new definition is found.
    $this->discovery->clearCachedDefinitions();
    $cached = $cache->get($this->cacheKey);
    $this->assertIdentical($cached, FALSE, 'Cache is empty.');
    $definitions = $this->discovery->getDefinitions();
    $this->assertIdentical($definitions, $this->expectedDefinitions, 'Newly added definition is found.');
  }

}
