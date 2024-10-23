<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheRedirect;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Cache\Context\ContextCacheKeys;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Cache\VariationCache;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Cache\VariationCache
 * @group Cache
 */
class VariationCacheTest extends UnitTestCase {

  /**
   * The prophesized request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $requestStack;

  /**
   * The backend used by the variation cache.
   *
   * @var \Drupal\Core\Cache\MemoryBackend
   */
  protected $memoryBackend;

  /**
   * The prophesized cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cacheContextsManager;

  /**
   * The variation cache instance.
   *
   * @var \Drupal\Core\Cache\VariationCacheInterface
   */
  protected $variationCache;

  /**
   * The cache keys this test will store things under.
   *
   * @var string[]
   */
  protected $cacheKeys = ['your', 'housing', 'situation'];

  /**
   * The cache ID for the cache keys, without taking contexts into account.
   *
   * @var string
   */
  protected $cacheIdBase = 'your:housing:situation';

  /**
   * The simulated current user's housing type.
   *
   * For use in tests with cache contexts.
   *
   * @var string
   */
  protected $housingType;

  /**
   * The cacheability for something that only varies per housing type.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $housingTypeCacheability;

  /**
   * The simulated current user's garden type.
   *
   * For use in tests with cache contexts.
   *
   * @var string
   */
  protected $gardenType;

  /**
   * The cacheability for something that varies per housing and garden type.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $gardenTypeCacheability;

  /**
   * The simulated current user's house's orientation.
   *
   * For use in tests with cache contexts.
   *
   * @var string
   */
  protected $houseOrientation;

  /**
   * The cacheability for varying per housing, garden and orientation.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $houseOrientationCacheability;

  /**
   * The simulated current user's solar panel type.
   *
   * For use in tests with cache contexts.
   *
   * @var string
   */
  protected $solarType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->requestStack = $this->prophesize(RequestStack::class);
    $this->memoryBackend = new MemoryBackend(new Time());
    $this->cacheContextsManager = $this->prophesize(CacheContextsManager::class);

    $housing_type = &$this->housingType;
    $garden_type = &$this->gardenType;
    $house_orientation = &$this->houseOrientation;
    $solar_type = &$this->solarType;
    $this->cacheContextsManager->convertTokensToKeys(Argument::any())
      ->will(function ($args) use (&$housing_type, &$garden_type, &$house_orientation, &$solar_type) {
        $keys = [];
        foreach ($args[0] as $context_id) {
          switch ($context_id) {
            case 'house.type':
              $keys[] = "ht.$housing_type";
              break;

            case 'garden.type':
              $keys[] = "gt.$garden_type";
              break;

            case 'house.orientation':
              $keys[] = "ho.$house_orientation";
              break;

            case 'solar.type':
              $keys[] = "st.$solar_type";
              break;

            default:
              $keys[] = $context_id;
          }
        }
        return new ContextCacheKeys($keys);
      });

    $this->variationCache = new VariationCache(
      $this->requestStack->reveal(),
      $this->memoryBackend,
      $this->cacheContextsManager->reveal()
    );

    $this->housingTypeCacheability = (new CacheableMetadata())
      ->setCacheTags(['foo'])
      ->setCacheContexts(['house.type']);
    $this->gardenTypeCacheability = (new CacheableMetadata())
      ->setCacheTags(['bar'])
      ->setCacheContexts(['house.type', 'garden.type']);
    $this->houseOrientationCacheability = (new CacheableMetadata())
      ->setCacheTags(['baz'])
      ->setCacheContexts(['house.type', 'garden.type', 'house.orientation']);
  }

  /**
   * Tests a cache item that has no variations.
   *
   * @covers ::get
   * @covers ::set
   */
  public function testNoVariations(): void {
    $data = 'You have a nice house!';
    $cacheability = (new CacheableMetadata())->setCacheTags(['bar', 'foo']);
    $initial_cacheability = (new CacheableMetadata())->setCacheTags(['foo']);
    $this->setVariationCacheItem($data, $cacheability, $initial_cacheability);
    $this->assertVariationCacheItem($data, $cacheability, $initial_cacheability);
  }

  /**
   * Tests a cache item that only ever varies by one context.
   *
   * @covers ::get
   * @covers ::set
   */
  public function testSingleVariation(): void {
    $cacheability = $this->housingTypeCacheability;

    $house_data = [
      'apartment' => 'You have a nice apartment',
      'house' => 'You have a nice house',
    ];

    foreach ($house_data as $housing_type => $data) {
      $this->housingType = $housing_type;
      $this->assertVariationCacheMiss($cacheability);
      $this->setVariationCacheItem($data, $cacheability, $cacheability);
      $this->assertVariationCacheItem($data, $cacheability, $cacheability);
      $this->assertCacheBackendItem("$this->cacheIdBase:ht.$housing_type", $data, $cacheability);
    }
  }

  /**
   * Tests a cache item that has nested variations.
   *
   * @covers ::get
   * @covers ::set
   */
  public function testNestedVariations(): void {
    // We are running this scenario in the best possible outcome: The redirects
    // are stored in expanding order, meaning the simplest one is stored first
    // and the nested ones are stored in subsequent ::set() calls. This means no
    // self-healing takes place where overly specific redirects are overwritten
    // with simpler ones.
    $possible_outcomes = [
      'apartment' => 'You have a nice apartment!',
      'house|no-garden' => 'You have a nice house!',
      'house|garden|east' => 'You have a nice house with an east-facing garden!',
      'house|garden|south' => 'You have a nice house with a south-facing garden!',
      'house|garden|west' => 'You have a nice house with a west-facing garden!',
      'house|garden|north' => 'You have a nice house with a north-facing garden!',
    ];

    foreach ($possible_outcomes as $cache_context_values => $data) {
      [$this->housingType, $this->gardenType, $this->houseOrientation] = explode('|', $cache_context_values . '||');

      $cacheability = $this->housingTypeCacheability;
      if (!empty($this->houseOrientation)) {
        $cacheability = $this->houseOrientationCacheability;
      }
      elseif (!empty($this->gardenType)) {
        $cacheability = $this->gardenTypeCacheability;
      }

      $this->assertVariationCacheMiss($this->housingTypeCacheability);
      $this->setVariationCacheItem($data, $cacheability, $this->housingTypeCacheability);
      $this->assertVariationCacheItem($data, $cacheability, $this->housingTypeCacheability);

      $cache_id_parts = ["ht.$this->housingType"];
      if (!empty($this->gardenType)) {
        $this->assertCacheBackendItem($this->getSortedCacheId($cache_id_parts), new CacheRedirect($this->gardenTypeCacheability));
        $cache_id_parts[] = "gt.$this->gardenType";
      }
      if (!empty($this->houseOrientation)) {
        $this->assertCacheBackendItem($this->getSortedCacheId($cache_id_parts), new CacheRedirect($this->houseOrientationCacheability));
        $cache_id_parts[] = "ho.$this->houseOrientation";
      }

      $this->assertCacheBackendItem($this->getSortedCacheId($cache_id_parts), $data, $cacheability);
    }
  }

  /**
   * Tests a cache item that has nested variations that trigger self-healing.
   *
   * @covers ::get
   * @covers ::set
   *
   * @depends testNestedVariations
   */
  public function testNestedVariationsSelfHealing(): void {
    // This is the worst possible scenario: A very specific item was stored
    // first, followed by a less specific one. This means an overly specific
    // cache redirect was stored that needs to be dumbed down. After this
    // process, the first ::get() for the more specific item will fail as we
    // have effectively destroyed the path to said item. Setting an item of the
    // same specificity will restore the path for all items of said specificity.
    $cache_id_parts = ['ht.house'];
    $possible_outcomes = [
      'house|garden|east' => 'You have a nice house with an east-facing garden!',
      'house|garden|south' => 'You have a nice house with a south-facing garden!',
      'house|garden|west' => 'You have a nice house with a west-facing garden!',
      'house|garden|north' => 'You have a nice house with a north-facing garden!',
    ];

    foreach ($possible_outcomes as $cache_context_values => $data) {
      [$this->housingType, $this->gardenType, $this->houseOrientation] = explode('|', $cache_context_values . '||');
      $this->setVariationCacheItem($data, $this->houseOrientationCacheability, $this->housingTypeCacheability);
    }

    // Verify that the overly specific redirect is stored at the first possible
    // redirect location, i.e.: The base cache ID.
    $this->assertCacheBackendItem($this->getSortedCacheId($cache_id_parts), new CacheRedirect($this->houseOrientationCacheability));

    // Store a simpler variation and verify that the first cache redirect is now
    // the one redirecting to the simplest known outcome.
    [$this->housingType, $this->gardenType, $this->houseOrientation] = ['house', 'no-garden', NULL];
    $this->setVariationCacheItem('You have a nice house', $this->gardenTypeCacheability, $this->housingTypeCacheability);
    $this->assertCacheBackendItem($this->getSortedCacheId($cache_id_parts), new CacheRedirect($this->gardenTypeCacheability));

    // Verify that the previously set outcomes are all inaccessible now.
    foreach ($possible_outcomes as $cache_context_values => $data) {
      [$this->housingType, $this->gardenType, $this->houseOrientation] = explode('|', $cache_context_values . '||');
      $this->assertVariationCacheMiss($this->housingTypeCacheability);
    }

    // Set at least one more specific item in the cache again.
    $this->setVariationCacheItem($data, $this->houseOrientationCacheability, $this->housingTypeCacheability);

    // Verify that the previously set outcomes are all accessible again.
    foreach ($possible_outcomes as $cache_context_values => $data) {
      [$this->housingType, $this->gardenType, $this->houseOrientation] = explode('|', $cache_context_values . '||');
      $this->assertVariationCacheItem($data, $this->houseOrientationCacheability, $this->housingTypeCacheability);
    }

    // Verify that the more specific cache redirect is now stored one step after
    // the less specific one.
    $cache_id_parts[] = 'gt.garden';
    $this->assertCacheBackendItem($this->getSortedCacheId($cache_id_parts), new CacheRedirect($this->houseOrientationCacheability));
  }

  /**
   * Tests self-healing for a cache item that has split variations.
   *
   * @covers ::get
   * @covers ::set
   */
  public function testSplitVariationsSelfHealing(): void {
    // This is an edge case. Something varies by AB where some values of B
    // trigger the whole to vary by either C, D or nothing extra. But due to an
    // unfortunate series of requests, only ABC and ABD variations were cached.
    //
    // In this case, the cache should be smart enough to generate a redirect for
    // AB, followed by redirects for ABC and ABD.
    //
    // For the sake of this test, we'll vary by housing and orientation, but:
    // - Only vary by garden type for south-facing houses.
    // - Only vary by solar panel type for north-facing houses.
    $this->housingType = 'house';
    $this->gardenType = 'garden';
    $this->solarType = 'solar';

    $initial_cacheability = (new CacheableMetadata())
      ->setCacheTags(['foo'])
      ->setCacheContexts(['house.type']);

    $south_cacheability = (new CacheableMetadata())
      ->setCacheTags(['foo'])
      ->setCacheContexts(['house.type', 'house.orientation', 'garden.type']);

    $north_cacheability = (new CacheableMetadata())
      ->setCacheTags(['foo'])
      ->setCacheContexts(['house.type', 'house.orientation', 'solar.type']);

    $common_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type', 'house.orientation']);

    // Calculate the cache IDs once beforehand for readability.
    $cache_id = $this->getSortedCacheId(['ht.house']);
    $cache_id_north = $this->getSortedCacheId(['ht.house', 'ho.north']);
    $cache_id_south = $this->getSortedCacheId(['ht.house', 'ho.south']);

    // Set the first scenario.
    $this->houseOrientation = 'south';
    $this->setVariationCacheItem('You have a south-facing house with a garden!', $south_cacheability, $initial_cacheability);

    // Verify that the overly specific redirect is stored at the first possible
    // redirect location, i.e.: The base cache ID.
    $this->assertCacheBackendItem($cache_id, new CacheRedirect($south_cacheability));

    // Store a split variation, and verify that the common contexts are now used
    // for the first cache redirect and the actual contexts for the next step of
    // the redirect chain.
    $this->houseOrientation = 'north';
    $this->setVariationCacheItem('You have a north-facing house with solar panels!', $north_cacheability, $initial_cacheability);
    $this->assertCacheBackendItem($cache_id, new CacheRedirect($common_cacheability));
    $this->assertCacheBackendItem($cache_id_north, new CacheRedirect($north_cacheability));

    // Verify that the initially set scenario is inaccessible now.
    $this->houseOrientation = 'south';
    $this->assertVariationCacheMiss($initial_cacheability);

    // Reset the initial scenario and verify that its redirects are accessible.
    $this->setVariationCacheItem('You have a south-facing house with a garden!', $south_cacheability, $initial_cacheability);
    $this->assertCacheBackendItem($cache_id, new CacheRedirect($common_cacheability));
    $this->assertCacheBackendItem($cache_id_south, new CacheRedirect($south_cacheability));

    // Double-check that the split scenario redirects are left untouched.
    $this->houseOrientation = 'north';
    $this->assertCacheBackendItem($cache_id, new CacheRedirect($common_cacheability));
    $this->assertCacheBackendItem($cache_id_north, new CacheRedirect($north_cacheability));
  }

  /**
   * Tests exception for a cache item that has incomplete variations.
   *
   * @covers ::get
   * @covers ::set
   */
  public function testIncompleteVariationsException(): void {
    // This should never happen. When someone first stores something in the
    // cache using context A and then tries to store something using context B,
    // something is wrong. There should always be at least one shared context at
    // the top level or else the cache cannot do its job.
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage("The complete set of cache contexts for a variation cache item must contain all of the initial cache contexts, missing: garden.type.");

    $this->housingType = 'house';
    $house_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type']);

    $this->gardenType = 'garden';
    $garden_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['garden.type']);

    $this->setVariationCacheItem('You have a nice garden!', $garden_cacheability, $garden_cacheability);
    $this->setVariationCacheItem('You have a nice house!', $house_cacheability, $garden_cacheability);
  }

  /**
   * Tests exception for a cache item that has an incomplete redirect.
   *
   * @covers ::get
   * @covers ::set
   */
  public function testIncompleteRedirectException(): void {
    // @todo Remove in Drupal 12.0.0. For more information, see:
    //   https://www.drupal.org/project/drupal/issues/3468921
    set_error_handler(static function (int $errno, string $errstr): never {
      throw new \LogicException($errstr, $errno);
    }, E_USER_WARNING);

    // This should never happen. When we have a cache redirect at address A,
    // pointing to 'A,B:foo' and then someone tries to store a cache redirect at
    // A pointing to 'A,B', something is wrong. The cache contexts leading up to
    // a cache redirect should always be present on the redirect itself. In this
    // example, the final cache redirect should be for 'A,B:foo,B'.
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Trying to overwrite a cache redirect with one that has nothing in common, old one at address "house.type" was pointing to "garden.type:zen", new one points to "garden.type".');

    $this->housingType = 'house';
    $house_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type']);

    $this->gardenType = '1';
    $calculated_garden_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type', 'garden.type:zen']);

    $this->setVariationCacheItem('You have a house with zen garden!', $calculated_garden_cacheability, $house_cacheability);

    $this->gardenType = 'baroque garden';
    $garden_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type', 'garden.type']);

    try {
      $this->setVariationCacheItem('You have a house with a baroque garden!', $garden_cacheability, $house_cacheability);
    }
    finally {
      restore_error_handler();
    }
  }

  /**
   * Tests exception for a cache item that has incompatible cache redirects.
   *
   * @covers ::get
   * @covers ::set
   */
  public function testIncompatibleRedirectsException(): void {
    // @todo Remove in Drupal 12.0.0. For more information, see:
    //   https://www.drupal.org/project/drupal/issues/3468921
    set_error_handler(static function (int $errno, string $errstr): never {
      throw new \LogicException($errstr, $errno);
    }, E_USER_WARNING);

    // This should never happen. When someone first triggers the storing of a
    // redirect using context A and then tries to store another redirect in the
    // same spot using context B, something is wrong. The cache contexts of all
    // previous redirects should always be present on the next redirect or item
    // you're trying to store.
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Trying to overwrite a cache redirect with one that has nothing in common, old one at address "house.type" was pointing to "garden.type", new one points to "house.orientation".');

    $this->housingType = 'house';
    $house_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type']);

    $this->gardenType = 'garden';
    $garden_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type', 'garden.type']);

    $this->houseOrientation = 'north';
    $orientation_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type', 'house.orientation']);

    $this->setVariationCacheItem('You have a nice house with a garden!', $garden_cacheability, $house_cacheability);
    try {
      $this->setVariationCacheItem('You have a nice north-facing house!', $orientation_cacheability, $house_cacheability);
    }
    finally {
      restore_error_handler();
    }
  }

  /**
   * Tests the same as above, but with more redirects.
   *
   * @covers ::get
   * @covers ::set
   *
   * @depends testIncompatibleRedirectsException
   */
  public function testIncompatibleChainedRedirectsException(): void {
    // @todo Remove in Drupal 12.0.0. For more information, see:
    //   https://www.drupal.org/project/drupal/issues/3468921
    set_error_handler(static function (int $errno, string $errstr): never {
      throw new \LogicException($errstr, $errno);
    }, E_USER_WARNING);

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Trying to overwrite a cache redirect with one that has nothing in common, old one at address "house.type, garden.type" was pointing to "house.orientation", new one points to "solar.type".');

    $this->housingType = 'house';
    $house_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type']);

    $this->gardenType = 'no-garden';
    $garden_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type', 'garden.type']);

    // This should set a redirect at ht.house specifying garden.type. So the
    // redirects below should find this redirect to be fine before getting to
    // the problematic one.
    $this->setVariationCacheItem('You have a nice house with no garden!', $garden_cacheability, $house_cacheability);
    $this->gardenType = 'garden';

    $this->houseOrientation = 'north';
    $orientation_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type', 'garden.type', 'house.orientation']);

    $this->solarType = 'solar';
    $solar_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type', 'garden.type', 'solar.type']);

    $this->setVariationCacheItem('You have a nice north-facing house with a garden!', $orientation_cacheability, $house_cacheability);
    try {
      $this->setVariationCacheItem('You have a nice house with solar panels and a garden!', $solar_cacheability, $house_cacheability);
    }
    finally {
      restore_error_handler();
    }
  }

  /**
   * Tests the same as above, but even more complex.
   *
   * @covers ::get
   * @covers ::set
   *
   * @depends testIncompatibleChainedRedirectsException
   */
  public function testIncompatibleChainedRedirectsComplexException(): void {
    // @todo Remove in Drupal 12.0.0. For more information, see:
    //   https://www.drupal.org/project/drupal/issues/3468921
    set_error_handler(static function (int $errno, string $errstr): never {
      throw new \LogicException($errstr, $errno);
    }, E_USER_WARNING);

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Trying to overwrite a cache redirect with one that has nothing in common, old one at address "house.type, garden.type" was pointing to "house.orientation", new one points to "solar.type".');

    $this->housingType = 'house';
    $house_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type']);

    $this->gardenType = 'garden';
    $this->houseOrientation = 'north';
    $orientation_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type', 'garden.type', 'house.orientation']);

    $this->solarType = 'solar';
    $solar_cacheability = (new CacheableMetadata())
      ->setCacheContexts(['house.type', 'garden.type', 'solar.type']);

    // This time, nothing primes the redirects so the first set will create a
    // redirect at ht.house, pointing to house.type, garden.type and solar.type.
    $this->setVariationCacheItem('You have a nice house with solar panels and a garden!', $solar_cacheability, $house_cacheability);

    // The second set will try to store a redirect at ht.house, pointing to
    // house.type, garden.type and house.orientation. This will trigger the
    // creation of a common redirect at ht.house, pointing to garden.type.
    $this->setVariationCacheItem('You have a nice north-facing house with a garden!', $orientation_cacheability, $house_cacheability);

    // Now we arrive at the same scenario as the test above. We have a redirect
    // chain at house.type of garden.type and finally house.orientation, but are
    // trying to set solar.type at that last address.
    try {
      $this->setVariationCacheItem('You have a nice house with solar panels and a garden!', $solar_cacheability, $house_cacheability);
    }
    finally {
      restore_error_handler();
    }
  }

  /**
   * Creates the sorted cache ID from cache ID parts.
   *
   * When core optimizes cache contexts it returns the keys alphabetically. To
   * make testing easier, we replicate said sorting here.
   *
   * @param string[] $cache_id_parts
   *   The parts to add to the base cache ID, will be sorted.
   *
   * @return string
   *   The correct cache ID.
   */
  protected function getSortedCacheId($cache_id_parts): string {
    sort($cache_id_parts);
    array_unshift($cache_id_parts, $this->cacheIdBase);
    return implode(':', $cache_id_parts);
  }

  /**
   * Stores an item in the variation cache.
   *
   * @param mixed $data
   *   The data that should be stored.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   The cacheability that should be used.
   * @param \Drupal\Core\Cache\CacheableMetadata $initial_cacheability
   *   The initial cacheability that should be used.
   */
  protected function setVariationCacheItem($data, CacheableMetadata $cacheability, CacheableMetadata $initial_cacheability): void {
    $this->variationCache->set($this->cacheKeys, $data, $cacheability, $initial_cacheability);
  }

  /**
   * Asserts that an item was properly stored in the variation cache.
   *
   * @param mixed $data
   *   The data that should have been stored.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   The cacheability that should have been used.
   * @param \Drupal\Core\Cache\CacheableMetadata $initial_cacheability
   *   The initial cacheability that should be used.
   */
  protected function assertVariationCacheItem($data, CacheableMetadata $cacheability, CacheableMetadata $initial_cacheability): void {
    $cache_item = $this->variationCache->get($this->cacheKeys, $initial_cacheability);
    $this->assertNotFalse($cache_item, 'Variable data was stored and retrieved successfully.');
    $this->assertEquals($data, $cache_item->data, 'Variable cache item contains the right data.');
    $this->assertSame($cacheability->getCacheTags(), $cache_item->tags, 'Variable cache item uses the right cache tags.');
  }

  /**
   * Asserts that an item could not be retrieved from the variation cache.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata $initial_cacheability
   *   The initial cacheability that should be used.
   */
  protected function assertVariationCacheMiss(CacheableMetadata $initial_cacheability): void {
    $this->assertFalse($this->variationCache->get($this->cacheKeys, $initial_cacheability), 'Nothing could be retrieved for the active cache contexts.');
  }

  /**
   * Asserts that an item was properly stored in the cache backend.
   *
   * @param string $cid
   *   The cache ID that should have been used.
   * @param mixed $data
   *   The data that should have been stored.
   * @param \Drupal\Core\Cache\CacheableMetadata|null $cacheability
   *   (optional) The cacheability that should have been used. Does not apply
   *   when checking for cache redirects.
   */
  protected function assertCacheBackendItem(string $cid, $data, ?CacheableMetadata $cacheability = NULL): void {
    $cache_backend_item = $this->memoryBackend->get($cid);
    $this->assertNotFalse($cache_backend_item, 'The data was stored and retrieved successfully.');
    $this->assertEquals($data, $cache_backend_item->data, 'Cache item contains the right data.');

    if ($data instanceof CacheRedirect) {
      $this->assertSame([], $cache_backend_item->tags, 'A cache redirect does not use cache tags.');
      $this->assertSame(-1, $cache_backend_item->expire, 'A cache redirect is stored indefinitely.');
    }
    else {
      $this->assertSame($cacheability->getCacheTags(), $cache_backend_item->tags, 'Cache item uses the right cache tags.');
    }
  }

}
