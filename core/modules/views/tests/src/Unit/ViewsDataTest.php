<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit;

use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\ViewsData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests Drupal\views\ViewsData.
 */
#[CoversClass(ViewsData::class)]
#[Group('views')]
class ViewsDataTest extends UnitTestCase {

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheBackend;

  /**
   * The mocked cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The tested views data class.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cacheTagsInvalidator = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');
    $this->cacheBackend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->getContainerWithCacheTagsInvalidator($this->cacheTagsInvalidator);

    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $this->viewsData = new ViewsData($this->cacheBackend, $this->moduleHandler, $this->languageManager);
  }

  /**
   * Returns the views data definition.
   */
  protected function viewsData() {
    $data = ViewTestData::viewsData();

    // Tweak the views data to have a base for testing.
    unset($data['views_test_data']['id']['field']);
    unset($data['views_test_data']['name']['argument']);
    unset($data['views_test_data']['age']['filter']);
    unset($data['views_test_data']['job']['sort']);
    $data['views_test_data']['created']['area']['id'] = 'text';
    $data['views_test_data']['age']['area']['id'] = 'text';
    $data['views_test_data']['age']['area']['sub_type'] = 'header';
    $data['views_test_data']['job']['area']['id'] = 'text';
    $data['views_test_data']['job']['area']['sub_type'] = ['header', 'footer'];

    // Duplicate the example views test data for different weight, different
    // title and matching data.
    $data['views_test_data_2'] = $data['views_test_data'];
    $data['views_test_data_2']['table']['base']['weight'] = 50;

    $data['views_test_data_3'] = $data['views_test_data'];
    $data['views_test_data_3']['table']['base']['weight'] = -50;

    $data['views_test_data_4'] = $data['views_test_data'];
    $data['views_test_data_4']['table']['base']['title'] = 'A different title';

    $data['views_test_data_5'] = $data['views_test_data'];
    $data['views_test_data_5']['table']['base']['title'] = 'Z different title';

    $data['views_test_data_6'] = $data['views_test_data'];

    return $data;
  }

  /**
   * Returns the views data definition with the provider key.
   *
   * @return array
   *   The views data definition.
   *
   * @see static::viewsData()
   */
  protected function viewsDataWithProvider() {
    $views_data = static::viewsData();
    foreach (array_keys($views_data) as $table) {
      $views_data[$table]['table']['provider'] = 'views_test_data';
    }
    return $views_data;
  }

  /**
   * Mocks the basic module handler used for the test.
   */
  protected function setupMockedModuleHandler(): void {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('invokeAllWith')
      ->with('views_data')
      ->willReturnCallback(function (string $hook, callable $callback) {
        $callback(\Closure::fromCallable([$this, 'viewsData']), 'views_test_data');
      });
  }

  /**
   * Tests the fetchBaseTables() method.
   */
  public function testFetchBaseTables(): void {
    $this->setupMockedModuleHandler();
    $data = $this->viewsData->getAll();

    $base_tables = $this->viewsData->fetchBaseTables();

    // Ensure that 'provider' is set for each base table.
    foreach (array_keys($base_tables) as $base_table) {
      $this->assertEquals('views_test_data', $data[$base_table]['table']['provider']);
    }

    // Test the number of tables returned and their order.
    $this->assertCount(6, $base_tables, 'The correct amount of base tables were returned.');
    $base_tables_keys = array_keys($base_tables);
    for ($i = 1; $i < count($base_tables); ++$i) {
      $prev = $base_tables[$base_tables_keys[$i - 1]];
      $current = $base_tables[$base_tables_keys[$i]];
      $this->assertGreaterThanOrEqual($prev['weight'], $current['weight']);
    }

    // Test the values returned for each base table.
    $defaults = [
      'title' => '',
      'help' => '',
      'weight' => 0,
    ];
    foreach ($base_tables as $base_table => $info) {
      // Merge in default values as in fetchBaseTables().
      $expected = $data[$base_table]['table']['base'] += $defaults;
      foreach ($defaults as $key => $default) {
        $this->assertSame($info[$key], $expected[$key]);
      }
    }
  }

  /**
   * Tests fetching all the views data without a static cache.
   */
  public function testGetOnFirstCall(): void {
    // Ensure that the hooks are just invoked once.
    $this->setupMockedModuleHandler();

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_data', $this->viewsDataWithProvider());

    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with("views_data:en")
      ->willReturn(FALSE);

    $expected_views_data = $this->viewsDataWithProvider();
    $views_data = $this->viewsData->getAll();
    $this->assertSame($expected_views_data, $views_data);
  }

  /**
   * Tests the cache of the full and single table data.
   */
  public function testFullAndTableGetCache(): void {
    $expected_views_data = $this->viewsDataWithProvider();
    $table_name = 'views_test_data';
    $table_name_2 = 'views_test_data_2';
    $random_table_name = $this->randomMachineName();

    // Views data should be invoked twice due to the clear call.
    $this->moduleHandler->expects($this->exactly(2))
      ->method('invokeAllWith')
      ->with('views_data')
      ->willReturnCallback(function ($hook, $callback) {
        $callback(\Closure::fromCallable([$this, 'viewsData']), 'views_test_data');
      });
    $this->moduleHandler->expects($this->exactly(2))
      ->method('alter')
      ->with('views_data', $expected_views_data);

    // The cache should only be called once (before the clear() call) as get
    // will get all table data in the first get().
    $gets = [
      'views_data:en',
      "views_data:$random_table_name:en",
      'views_data:en',
      "views_data:$random_table_name:en",
    ];
    $this->cacheBackend->expects($this->exactly(count($gets)))
      ->method('get')
      ->with($this->callback(function (string $key) use (&$gets): bool {
        return $key === array_shift($gets);
      }))
      ->willReturn(FALSE);

    $sets = [
      'views_data:en', $expected_views_data,
      "views_data:$random_table_name:en", [],
      'views_data:en', $expected_views_data,
      "views_data:$random_table_name:en", [],
    ];
    $this->cacheBackend->expects($this->exactly(count($sets) / 2))
      ->method('set')
      ->with($this->callback(function (string $key) use (&$sets): bool {
        return $key === array_shift($sets);
      }), $this->callback(function (array $data) use (&$sets): bool {
        return $data === array_shift($sets);
      }));

    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['views_data']);

    $views_data = $this->viewsData->getAll();
    $this->assertSame($expected_views_data, $views_data);

    // Request a specific table should be static cached.
    $views_data = $this->viewsData->get($table_name);
    $this->assertSame($expected_views_data[$table_name], $views_data);

    // Another table being requested should also come from the static cache.
    $views_data = $this->viewsData->get($table_name_2);
    $this->assertSame($expected_views_data[$table_name_2], $views_data);

    $views_data = $this->viewsData->get($random_table_name);
    $this->assertSame([], $views_data);

    $this->viewsData->clear();

    // Get the views data again.
    $this->viewsData->getAll();
    $this->viewsData->get($table_name);
    $this->viewsData->get($table_name_2);
    $this->viewsData->get($random_table_name);
  }

  /**
   * Tests the caching of the full views data.
   */
  public function testFullGetCache(): void {
    $expected_views_data = $this->viewsDataWithProvider();

    // Views data should be invoked once.
    $this->setupMockedModuleHandler();

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_data', $expected_views_data);

    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with("views_data:en")
      ->willReturn(FALSE);

    $views_data = $this->viewsData->getAll();
    $this->assertSame($expected_views_data, $views_data);

    $views_data = $this->viewsData->getAll();
    $this->assertSame($expected_views_data, $views_data);
  }

  /**
   * Tests the caching of the views data for a specific table.
   */
  public function testSingleTableGetCache(): void {
    $table_name = 'views_test_data';
    $expected_views_data = $this->viewsDataWithProvider();

    // Views data should be invoked once.
    $this->setupMockedModuleHandler();

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_data', $this->viewsDataWithProvider());

    $gets = ["views_data:$table_name:en", 'views_data:en'];
    $this->cacheBackend->expects($this->exactly(count($gets)))
      ->method('get')
      ->with($this->callback(function (string $key) use (&$gets): bool {
        return $key === array_shift($gets);
      }))
      ->willReturn(FALSE);

    $views_data = $this->viewsData->get($table_name);
    $this->assertSame($expected_views_data[$table_name], $views_data, 'Make sure fetching views data by table works as expected.');

    $views_data = $this->viewsData->get($table_name);
    $this->assertSame($expected_views_data[$table_name], $views_data, 'Make sure fetching cached views data by table works as expected.');

    // Test that this data is present if all views data is returned.
    $views_data = $this->viewsData->getAll();

    $this->assertArrayHasKey($table_name, $views_data, 'Make sure the views_test_data info appears in the total views data.');
    $this->assertSame($expected_views_data[$table_name], $views_data[$table_name], 'Make sure the views_test_data has the expected values.');
  }

  /**
   * Tests building the views data with a non existing table.
   */
  public function testNonExistingTableGetCache(): void {
    $random_table_name = $this->randomMachineName();

    // Views data should be invoked once.
    $this->setupMockedModuleHandler();

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_data', $this->viewsDataWithProvider());

    $gets = ["views_data:$random_table_name:en", 'views_data:en'];
    $this->cacheBackend->expects($this->exactly(count($gets)))
      ->method('get')
      ->with($this->callback(function (string $key) use (&$gets): bool {
        return $key === array_shift($gets);
      }))
      ->willReturn(FALSE);

    // All views data should be requested on the first try.
    $views_data = $this->viewsData->get($random_table_name);
    $this->assertSame([], $views_data, 'Make sure fetching views data for an invalid table returns an empty array.');

    // Test no data is rebuilt when requesting an invalid table again.
    $views_data = $this->viewsData->get($random_table_name);
    $this->assertSame([], $views_data, 'Make sure fetching views data for an invalid table returns an empty array.');
  }

  /**
   * Tests the cache backend behavior with requesting the same table multiple.
   */
  public function testCacheCallsWithSameTableMultipleTimes(): void {
    $expected_views_data = $this->viewsDataWithProvider();

    $this->setupMockedModuleHandler();

    $gets = ['views_data:views_test_data:en', 'views_data:en'];
    $this->cacheBackend->expects($this->exactly(count($gets)))
      ->method('get')
      ->with($this->callback(function (string $key) use (&$gets): bool {
        return $key === array_shift($gets);
      }));

    $sets = [
      'views_data:en', $expected_views_data,
      'views_data:views_test_data:en', $expected_views_data['views_test_data'],
    ];
    $this->cacheBackend->expects($this->exactly(count($sets) / 2))
      ->method('set')
      ->with($this->callback(function (string $key) use (&$sets): bool {
        return $key === array_shift($sets);
      }), $this->callback(function (array $data) use (&$sets): bool {
        return $data === array_shift($sets);
      }));

    // Request the same table 5 times. The caches are empty at this point, so
    // what will happen is that it will first check for a cache entry for the
    // given table, get a cache miss, then try the cache entry for all tables,
    // which does not exist yet either. As a result, it rebuilds the information
    // and writes a cache entry for all tables and the requested table.
    $table_name = 'views_test_data';
    for ($i = 0; $i < 5; $i++) {
      $views_data = $this->viewsData->get($table_name);
      $this->assertSame($expected_views_data['views_test_data'], $views_data);
    }
  }

  /**
   * Tests the cache calls for a single table and warm cache.
   *
   * Warm cache:
   *   - all tables
   *   - views_test_data.
   */
  public function testCacheCallsWithSameTableMultipleTimesAndWarmCache(): void {
    $expected_views_data = $this->viewsDataWithProvider();
    $this->moduleHandler->expects($this->never())
      ->method('invokeAllWith');

    // Setup a warm cache backend for a single table.
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with('views_data:views_test_data:en')
      ->willReturn((object) ['data' => $expected_views_data['views_test_data']]);
    $this->cacheBackend->expects($this->never())
      ->method('set');

    // We have a warm cache now, so this will only request the tables-specific
    // cache entry and return that.
    for ($i = 0; $i < 5; $i++) {
      $views_data = $this->viewsData->get('views_test_data');
      $this->assertSame($expected_views_data['views_test_data'], $views_data);
    }
  }

  /**
   * Tests the cache calls for a different table than the one in cache.
   *
   * Warm cache:
   *   - all tables
   *   - views_test_data
   * Not warm cache:
   *   - views_test_data_2
   */
  public function testCacheCallsWithWarmCacheAndDifferentTable(): void {
    $expected_views_data = $this->viewsDataWithProvider();
    $this->moduleHandler->expects($this->never())
      ->method('invokeAllWith');

    // Setup a warm cache backend for a single table.
    $gets = ['views_data:views_test_data_2:en', 'views_data:en'];
    $this->cacheBackend->expects($this->exactly(count($gets)))
      ->method('get')
      ->with($this->callback(function (string $key) use (&$gets): bool {
        return $key === array_shift($gets);
      }))
      ->willReturnOnConsecutiveCalls(
        FALSE,
        (object) ['data' => $expected_views_data],
      );
    $this->cacheBackend->expects($this->once())
      ->method('set')
      ->with('views_data:views_test_data_2:en', $expected_views_data['views_test_data_2']);

    // Requests a different table as the cache contains. This will fail to get a
    // table specific cache entry, load the cache entry for all tables and save
    // a cache entry for this table but not all.
    for ($i = 0; $i < 5; $i++) {
      $views_data = $this->viewsData->get('views_test_data_2');
      $this->assertSame($expected_views_data['views_test_data_2'], $views_data);
    }
  }

  /**
   * Tests the cache calls for a non-existent table.
   *
   * Warm cache:
   *   - all tables
   *   - views_test_data
   * Not warm cache:
   *   - $non_existing_table.
   */
  public function testCacheCallsWithWarmCacheAndInvalidTable(): void {
    $expected_views_data = $this->viewsDataWithProvider();
    $non_existing_table = $this->randomMachineName();
    $this->moduleHandler->expects($this->never())
      ->method('invokeAllWith');

    // Setup a warm cache backend for a single table.
    $gets = ["views_data:$non_existing_table:en", 'views_data:en'];
    $this->cacheBackend->expects($this->exactly(count($gets)))
      ->method('get')
      ->with($this->callback(function (string $key) use (&$gets): bool {
        return $key === array_shift($gets);
      }))
      ->willReturnOnConsecutiveCalls(
        FALSE,
        (object) ['data' => $expected_views_data],
      );
    $this->cacheBackend->expects($this->once())
      ->method('set')
      ->with("views_data:$non_existing_table:en", []);

    // Initialize the views data cache and request a non-existing table. This
    // will result in the same cache requests as we explicitly write an empty
    // cache entry for non-existing tables to avoid unnecessary requests in
    // those situations. We do have to load the cache entry for all tables to
    // check if the table does exist or not.
    for ($i = 0; $i < 5; $i++) {
      $views_data = $this->viewsData->get($non_existing_table);
      $this->assertSame([], $views_data);
    }
  }

  /**
   * Tests the cache calls for a non-existent table.
   *
   * Warm cache:
   *   - all tables
   *   - views_test_data
   *   - $non_existing_table.
   */
  public function testCacheCallsWithWarmCacheForInvalidTable(): void {
    $non_existing_table = $this->randomMachineName();
    $this->moduleHandler->expects($this->never())
      ->method('invokeAllWith');

    // Setup a warm cache backend for a single table.
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with("views_data:$non_existing_table:en")
      ->willReturn((object) ['data' => []]);
    $this->cacheBackend->expects($this->never())
      ->method('set');

    // Initialize the views data cache and request a non-existing table. This
    // will result in the same cache requests as we explicitly write an empty
    // cache entry for non-existing tables to avoid unnecessary requests in
    // those situations. We do have to load the cache entry for all tables to
    // check if the table does exist or not.
    for ($i = 0; $i < 5; $i++) {
      $views_data = $this->viewsData->get($non_existing_table);
      $this->assertSame([], $views_data);
    }
  }

  /**
   * Tests the cache calls for all views data without a warm cache.
   */
  public function testCacheCallsWithoutWarmCacheAndGetAllTables(): void {
    $expected_views_data = $this->viewsDataWithProvider();
    $this->setupMockedModuleHandler();

    // Setup a warm cache backend for a single table.
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with("views_data:en");
    $this->cacheBackend->expects($this->once())
      ->method('set')
      ->with('views_data:en', $expected_views_data);

    // Initialize the views data cache and repeat with no specified table. This
    // should only load the cache entry for all tables.
    for ($i = 0; $i < 5; $i++) {
      $views_data = $this->viewsData->getAll();
      $this->assertSame($expected_views_data, $views_data);
    }
  }

  /**
   * Tests the cache calls for all views data.
   *
   * Warm cache:
   *   - all tables.
   */
  public function testCacheCallsWithWarmCacheAndGetAllTables(): void {
    $expected_views_data = $this->viewsDataWithProvider();
    $this->moduleHandler->expects($this->never())
      ->method('invokeAllWith');

    // Setup a warm cache backend for a single table.
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with("views_data:en")
      ->willReturn((object) ['data' => $expected_views_data]);
    $this->cacheBackend->expects($this->never())
      ->method('set');

    // Initialize the views data cache and repeat with no specified table. This
    // should only load the cache entry for all tables.
    for ($i = 0; $i < 5; $i++) {
      $views_data = $this->viewsData->getAll();
      $this->assertSame($expected_views_data, $views_data);
    }
  }

  /**
   * Tests the cache calls for multiple tables without warm caches.
   *
   * @legacy-covers ::get
   */
  public function testCacheCallsWithoutWarmCacheAndGetMultipleTables(): void {
    $expected_views_data = $this->viewsDataWithProvider();
    $table_name = 'views_test_data';
    $table_name_2 = 'views_test_data_2';

    // Setup a warm cache backend for all table data, but not single tables.
    $gets = ["views_data:$table_name:en", 'views_data:en', "views_data:$table_name_2:en"];
    $this->cacheBackend->expects($this->exactly(count($gets)))
      ->method('get')
      ->with($this->callback(function (string $key) use (&$gets): bool {
        return $key === array_shift($gets);
      }))
      ->willReturnOnConsecutiveCalls(
        FALSE,
        (object) ['data' => $expected_views_data],
        FALSE,
      );

    $sets = [
      "views_data:$table_name:en", $expected_views_data[$table_name],
      "views_data:$table_name_2:en", $expected_views_data[$table_name_2],
    ];
    $this->cacheBackend->expects($this->exactly(count($sets) / 2))
      ->method('set')
      ->with($this->callback(function (string $key) use (&$sets): bool {
        return $key === array_shift($sets);
      }), $this->callback(function (array $data) use (&$sets): bool {
        return $data === array_shift($sets);
      }));

    $this->assertSame($expected_views_data[$table_name], $this->viewsData->get($table_name));
    $this->assertSame($expected_views_data[$table_name_2], $this->viewsData->get($table_name_2));

    // Should only be invoked the first time.
    $this->assertSame($expected_views_data[$table_name], $this->viewsData->get($table_name));
    $this->assertSame($expected_views_data[$table_name_2], $this->viewsData->get($table_name_2));
  }

  /**
   * Tests that getting data with an empty key throws an exception.
   */
  #[DataProvider('providerTestGetEmptyKey')]
  public function testGetEmptyKey($key): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('A valid cache entry key is required. Use getAll() to get all table data.');

    $this->viewsData->get($key);
  }

  /**
   * Provides data to testGetEmptyKey().
   */
  public static function providerTestGetEmptyKey() {
    return [
      [NULL],
      [''],
      [0],
    ];
  }

  /**
   * Tests that fullyLoaded is only set to TRUE after data is completely loaded.
   *
   * This test ensures that the fullyLoaded property is not set prematurely.
   */
  public function testFullyLoadedPropertySetAfterDataLoad(): void {
    $expected_views_data = $this->viewsDataWithProvider();

    // Use reflection to access the protected fullyLoaded property.
    $reflection = new \ReflectionClass($this->viewsData);
    $fullyLoaded_property = $reflection->getProperty('fullyLoaded');

    // Verify fullyLoaded starts as FALSE.
    $this->assertFalse($fullyLoaded_property->getValue($this->viewsData));

    $this->setupMockedModuleHandler();

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_data', $expected_views_data);

    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with("views_data:en")
      ->willReturn(FALSE);

    // Request all views data.
    $views_data = $this->viewsData->getAll();

    // After getAll() completes, fullyLoaded should be TRUE.
    $this->assertTrue($fullyLoaded_property->getValue($this->viewsData));
    $this->assertSame($expected_views_data, $views_data);
  }

  /**
   * Tests that fullyLoaded is TRUE when data is loaded from cache.
   *
   * When views data is retrieved from cache, fullyLoaded should still be
   * set to TRUE after the data retrieval is complete.
   */
  public function testFullyLoadedPropertySetAfterCacheLoad(): void {
    $expected_views_data = $this->viewsDataWithProvider();

    // Use reflection to access the protected fullyLoaded property.
    $reflection = new \ReflectionClass($this->viewsData);
    $fullyLoaded_property = $reflection->getProperty('fullyLoaded');

    // Verify fullyLoaded starts as FALSE.
    $this->assertFalse($fullyLoaded_property->getValue($this->viewsData));

    // Mock that data is already cached.
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with("views_data:en")
      ->willReturn((object) ['data' => $expected_views_data]);

    // No module hooks should be invoked since data comes from cache.
    $this->moduleHandler->expects($this->never())
      ->method('invokeAllWith');

    // Request all views data from cache.
    $views_data = $this->viewsData->getAll();

    // After getAll() completes with cached data, fullyLoaded should be TRUE.
    $this->assertTrue($fullyLoaded_property->getValue($this->viewsData));
    $this->assertSame($expected_views_data, $views_data);
  }

  /**
   * Tests that fullyLoaded prevents redundant data loading.
   *
   * Once fullyLoaded is TRUE, the subsequent calls to getAll() should not
   * trigger data rebuilding or cache lookups.
   */
  public function testFullyLoadedPreventsRedundantLoading(): void {
    $expected_views_data = $this->viewsDataWithProvider();

    $this->setupMockedModuleHandler();

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_data', $expected_views_data);

    // Cache should only be checked once.
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with("views_data:en")
      ->willReturn(FALSE);

    // First call loads the data.
    $views_data = $this->viewsData->getAll();
    $this->assertSame($expected_views_data, $views_data);

    // Use reflection to verify fullyLoaded is now TRUE.
    $reflection = new \ReflectionClass($this->viewsData);
    $fullyLoaded_property = $reflection->getProperty('fullyLoaded');
    $this->assertTrue($fullyLoaded_property->getValue($this->viewsData));

    // The Second call should use stored data without cache checks.
    $views_data = $this->viewsData->getAll();
    $this->assertSame($expected_views_data, $views_data);

    // Third call to verify consistency.
    $views_data = $this->viewsData->getAll();
    $this->assertSame($expected_views_data, $views_data);
  }

  /**
   * Tests that clear() resets the fullyLoaded property.
   *
   * After calling clear(), fullyLoaded should be reset to FALSE so that
   * data can be reloaded on the next request.
   */
  public function testClearResetsFullyLoaded(): void {
    $expected_views_data = $this->viewsDataWithProvider();

    $this->setupMockedModuleHandler();

    $this->moduleHandler->expects($this->exactly(2))
      ->method('alter')
      ->with('views_data', $expected_views_data);

    // Cache will be checked twice (before and after clear).
    $this->cacheBackend->expects($this->exactly(2))
      ->method('get')
      ->with("views_data:en")
      ->willReturn(FALSE);

    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['views_data']);

    // Use reflection to access the protected fullyLoaded property.
    $reflection = new \ReflectionClass($this->viewsData);
    $fullyLoaded_property = $reflection->getProperty('fullyLoaded');

    // Load data initially.
    $this->viewsData->getAll();
    $this->assertTrue($fullyLoaded_property->getValue($this->viewsData));

    // Clear the data.
    $this->viewsData->clear();

    // After clear, fullyLoaded should be FALSE again.
    $this->assertFalse($fullyLoaded_property->getValue($this->viewsData));

    // Load data again to verify it works after clear.
    $views_data = $this->viewsData->getAll();
    $this->assertSame($expected_views_data, $views_data);
    $this->assertTrue($fullyLoaded_property->getValue($this->viewsData));
  }

  /**
   * Tests fullyLoaded with get() method triggering full data load.
   *
   * When get() is called for a specific table and no cache exists,
   * it triggers getData() which should set fullyLoaded to TRUE.
   */
  public function testFullyLoadedSetByGetMethod(): void {
    $table_name = 'views_test_data';
    $expected_views_data = $this->viewsDataWithProvider();

    // Use reflection to access the protected fullyLoaded property.
    $reflection = new \ReflectionClass($this->viewsData);
    $fullyLoaded_property = $reflection->getProperty('fullyLoaded');

    $this->assertFalse($fullyLoaded_property->getValue($this->viewsData));

    $this->setupMockedModuleHandler();

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_data', $expected_views_data);

    // No table-specific or full cache exists.
    $gets = ["views_data:$table_name:en", 'views_data:en'];
    $this->cacheBackend->expects($this->exactly(count($gets)))
      ->method('get')
      ->with($this->callback(function (string $key) use (&$gets): bool {
        return $key === array_shift($gets);
      }))
      ->willReturn(FALSE);

    // Get specific table data, which triggers full data load.
    $views_data = $this->viewsData->get($table_name);

    // After get() triggers getData(), fullyLoaded should be TRUE.
    $this->assertTrue($fullyLoaded_property->getValue($this->viewsData));
    $this->assertSame($expected_views_data[$table_name], $views_data);
  }

  /**
   * Tests that concurrent fibers retrieving views data cache entries correctly.
   *
   * This tests the fix for the fiber race condition where:
   * 1. Fiber A calls get($table) or getAll(), which triggers getData()
   * 2. getData() invokes module hooks which may suspend the fiber
   * 3. Fiber B calls get($table) or getAll(), sees fullyLoaded is FALSE
   * 4. Fiber B correctly calls getData() again (not skipping it)
   * 5. Both fibers get correct data, no empty cache entries written
   *
   * The fix ensures fullyLoaded is set to TRUE only AFTER data is obtained,
   * not at the start of getData().
   *
   * This test also covers combinations of get() and getAll() in the two
   * fibers.
   */
  #[TestWith(['get', 'get', 2, 2])]
  #[TestWith(['getAll', 'getAll', 1, 1])]
  #[TestWith(['get', 'getAll', 2, 2])]
  #[TestWith(['getAll', 'get', 1, 1])]
  public function testConcurrentFiberAccess(
    string $first_fiber_method,
    string $second_fiber_method,
    int $expected_cache_get_count,
    int $expected_cache_set_count,
  ): void {
    $expected_views_data = $this->viewsDataWithProvider();
    $table_name = 'views_test_data';

    // In getData(), the module handler will suspend the fiber during hook
    // invocation. This should happen only once, because the 'loading' property
    // being TRUE will suspend the second fiber before it can enter getData().
    $cache_sets = [];
    $this->moduleHandler->expects($this->once())
      ->method('invokeAllWith')
      ->with('views_data')
      ->willReturnCallback(function ($hook, $callback) {
        // Suspend the fiber to simulate async operation during hook.
        if (\Fiber::getCurrent() !== NULL) {
          \Fiber::suspend();
        }
        $callback(\Closure::fromCallable([$this, 'viewsData']), 'views_test_data');
      });

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_data', $this->anything());

    // Cache operation counts for fiber method combinations (first, second):
    // (get, get): First fiber calls cacheGet() once in get(), once in
    // getData(), and cacheSet() once in get(), once in getData().
    // (getAll, getAll): First fiber calls cacheGet() once in getData() and
    // cacheSet() once in getData().
    // (get, getAll): First fiber calls cacheGet once in get(), once in
    // getData(), and cacheSet() once in get(), once in getData().
    // (getAll, get): First fiber calls cacheGet() once in getData() and
    // cacheSet once in getData().
    // For all combinations, the second fiber does not make any cache operation
    // calls, because views data has been loaded into the allStorage and storage
    // properties.
    $this->cacheBackend->expects($this->exactly($expected_cache_get_count))
      ->method('get')
      ->willReturnCallback(function (string $cid) use (&$cache_sets) {
        return $cache_sets[$cid] ?? NULL;
      });

    $this->cacheBackend->expects($this->exactly($expected_cache_set_count))
      ->method('set')
      ->willReturnCallback(function ($cid, $data) use (&$cache_sets) {
        $cache_sets[$cid] = (object) ['data' => $data];
      });

    // Create two fibers simulating concurrent requests to get views data.
    $first_fiber = new \Fiber(fn () => $this->viewsData->$first_fiber_method($table_name));
    $second_fiber = new \Fiber(fn () => $this->viewsData->$second_fiber_method($table_name));

    $fibers = [$first_fiber, $second_fiber];
    $suspended = FALSE;

    // Process fibers until all complete.
    do {
      foreach ($fibers as $key => $fiber) {
        if (!$fiber->isStarted()) {
          $fiber->start();
        }
        elseif ($fiber->isSuspended()) {
          $suspended = TRUE;
          $fiber->resume();
        }
        elseif ($fiber->isTerminated()) {
          unset($fibers[$key]);
        }
      }
    } while (!empty($fibers));

    // Ensure fibers were actually suspended to validate the test scenario.
    $this->assertTrue($suspended);

    // Both fibers should return the correct data. If get() is running in the
    // fiber, the expected data is for one table. If getAll() is running in
    // the fiber, the expected data is for all tables.
    foreach ([$first_fiber_method, $second_fiber_method] as $method) {
      $expected_results[] = $method === 'get' ? $expected_views_data[$table_name] : $expected_views_data;
    }

    $this->assertSame($expected_results[0], $first_fiber->getReturn());
    $this->assertSame($expected_results[1], $second_fiber->getReturn());

    // Verify no empty cache entries were written.
    foreach ($cache_sets as $cid => $data) {
      if (str_contains($cid, $table_name)) {
        $this->assertNotEmpty($data);
      }
    }
  }

}
