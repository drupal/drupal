<?php

namespace Drupal\Tests\views\Unit;

use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewsData;
use Drupal\views\Tests\ViewTestData;

/**
 * @coversDefaultClass \Drupal\views\ViewsData
 * @group views
 */
class ViewsDataTest extends UnitTestCase {

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheBackend;

  /**
   * The mocked cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
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
  protected function setUp() {
    $this->cacheTagsInvalidator = $this->getMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');
    $this->cacheBackend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->getContainerWithCacheTagsInvalidator($this->cacheTagsInvalidator);

    $configs = [];
    $configs['views.settings']['skip_cache'] = FALSE;
    $this->configFactory = $this->getConfigFactoryStub($configs);
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue(new Language(['id' => 'en'])));

    $this->viewsData = new ViewsData($this->cacheBackend, $this->configFactory, $this->moduleHandler, $this->languageManager);
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

    // Duplicate the example views test data for different weight, different title,
    // and matching data.
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
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function setupMockedModuleHandler() {
    $views_data = $this->viewsData();
    $this->moduleHandler->expects($this->at(0))
      ->method('getImplementations')
      ->with('views_data')
      ->willReturn(['views_test_data']);
    $this->moduleHandler->expects($this->at(1))
      ->method('invoke')
      ->with('views_test_data', 'views_data')
      ->willReturn($views_data);
  }

  /**
   * Tests the fetchBaseTables() method.
   */
  public function testFetchBaseTables() {
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
      $this->assertTrue($prev['weight'] <= $current['weight'] && $prev['title'] <= $prev['title'], 'The tables are sorted as expected.');
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
  public function testGetOnFirstCall() {
    // Ensure that the hooks are just invoked once.
    $this->setupMockedModuleHandler();

    $this->moduleHandler->expects($this->at(2))
      ->method('alter')
      ->with('views_data', $this->viewsDataWithProvider());

    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with("views_data:en")
      ->will($this->returnValue(FALSE));

    $expected_views_data = $this->viewsDataWithProvider();
    $views_data = $this->viewsData->getAll();
    $this->assertSame($expected_views_data, $views_data);
  }

  /**
   * Tests the cache of the full and single table data.
   */
  public function testFullAndTableGetCache() {
    $expected_views_data = $this->viewsDataWithProvider();
    $table_name = 'views_test_data';
    $table_name_2 = 'views_test_data_2';
    $random_table_name = $this->randomMachineName();

    // Views data should be invoked twice due to the clear call.
    $this->moduleHandler->expects($this->at(0))
      ->method('getImplementations')
      ->with('views_data')
      ->willReturn(['views_test_data']);
    $this->moduleHandler->expects($this->at(1))
      ->method('invoke')
      ->with('views_test_data', 'views_data')
      ->willReturn($this->viewsData());
    $this->moduleHandler->expects($this->at(2))
      ->method('alter')
      ->with('views_data', $expected_views_data);

    $this->moduleHandler->expects($this->at(3))
      ->method('getImplementations')
      ->with('views_data')
      ->willReturn(['views_test_data']);
    $this->moduleHandler->expects($this->at(4))
      ->method('invoke')
      ->with('views_test_data', 'views_data')
      ->willReturn($this->viewsData());
    $this->moduleHandler->expects($this->at(5))
      ->method('alter')
      ->with('views_data', $expected_views_data);

    // The cache should only be called once (before the clear() call) as get
    // will get all table data in the first get().
    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with("views_data:en")
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(1))
      ->method('set')
      ->with("views_data:en", $expected_views_data);
    $this->cacheBackend->expects($this->at(2))
      ->method('get')
      ->with("views_data:$random_table_name:en")
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(3))
      ->method('set')
      ->with("views_data:$random_table_name:en", []);
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['views_data']);
    $this->cacheBackend->expects($this->at(4))
      ->method('get')
      ->with("views_data:en")
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(5))
      ->method('set')
      ->with("views_data:en", $expected_views_data);
    $this->cacheBackend->expects($this->at(6))
      ->method('get')
      ->with("views_data:$random_table_name:en")
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(7))
      ->method('set')
      ->with("views_data:$random_table_name:en", []);

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
  public function testFullGetCache() {
    $expected_views_data = $this->viewsDataWithProvider();

    // Views data should be invoked once.
    $this->setupMockedModuleHandler();

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_data', $expected_views_data);

    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with("views_data:en")
      ->will($this->returnValue(FALSE));

    $views_data = $this->viewsData->getAll();
    $this->assertSame($expected_views_data, $views_data);

    $views_data = $this->viewsData->getAll();
    $this->assertSame($expected_views_data, $views_data);
  }

  /**
   * Tests the caching of the views data for a specific table.
   */
  public function testSingleTableGetCache() {
    $table_name = 'views_test_data';
    $expected_views_data = $this->viewsDataWithProvider();

    // Views data should be invoked once.
    $this->setupMockedModuleHandler();

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_data', $this->viewsDataWithProvider());

    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with("views_data:$table_name:en")
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with("views_data:en")
      ->will($this->returnValue(FALSE));

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
  public function testNonExistingTableGetCache() {
    $random_table_name = $this->randomMachineName();

    // Views data should be invoked once.
    $this->setupMockedModuleHandler();

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_data', $this->viewsDataWithProvider());

    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with("views_data:$random_table_name:en")
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with("views_data:en")
      ->will($this->returnValue(FALSE));

    // All views data should be requested on the first try.
    $views_data = $this->viewsData->get($random_table_name);
    $this->assertSame([], $views_data, 'Make sure fetching views data for an invalid table returns an empty array.');

    // Test no data is rebuilt when requesting an invalid table again.
    $views_data = $this->viewsData->get($random_table_name);
    $this->assertSame([], $views_data, 'Make sure fetching views data for an invalid table returns an empty array.');
  }

  /**
   * Tests the cache backend behavior with requesting the same table multiple
   */
  public function testCacheCallsWithSameTableMultipleTimes() {
    $expected_views_data = $this->viewsDataWithProvider();

    $this->setupMockedModuleHandler();

    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with('views_data:views_test_data:en');
    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with('views_data:en');
    $this->cacheBackend->expects($this->at(2))
      ->method('set')
      ->with('views_data:en', $expected_views_data);
    $this->cacheBackend->expects($this->at(3))
      ->method('set')
      ->with('views_data:views_test_data:en', $expected_views_data['views_test_data']);

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
   * Tests the cache calls for a single table and warm cache for:
   *   - all tables
   *   - views_test_data
   */
  public function testCacheCallsWithSameTableMultipleTimesAndWarmCache() {
    $expected_views_data = $this->viewsDataWithProvider();
    $this->moduleHandler->expects($this->never())
      ->method('getImplementations');

    // Setup a warm cache backend for a single table.
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with('views_data:views_test_data:en')
      ->will($this->returnValue((object) ['data' => $expected_views_data['views_test_data']]));
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
   * Tests the cache calls for a different table than the one in cache:
   *
   * Warm cache:
   *   - all tables
   *   - views_test_data
   * Not warm cache:
   *   - views_test_data_2
   */
  public function testCacheCallsWithWarmCacheAndDifferentTable() {
    $expected_views_data = $this->viewsDataWithProvider();
    $this->moduleHandler->expects($this->never())
      ->method('getImplementations');

    // Setup a warm cache backend for a single table.
    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with('views_data:views_test_data_2:en');
    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with('views_data:en')
      ->will($this->returnValue((object) ['data' => $expected_views_data]));
    $this->cacheBackend->expects($this->at(2))
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
   * Tests the cache calls for an not existing table:
   *
   * Warm cache:
   *   - all tables
   *   - views_test_data
   * Not warm cache:
   *   - $non_existing_table
   */
  public function testCacheCallsWithWarmCacheAndInvalidTable() {
    $expected_views_data = $this->viewsDataWithProvider();
    $non_existing_table = $this->randomMachineName();
    $this->moduleHandler->expects($this->never())
      ->method('getImplementations');

    // Setup a warm cache backend for a single table.
    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with("views_data:$non_existing_table:en");
    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with('views_data:en')
      ->will($this->returnValue((object) ['data' => $expected_views_data]));
    $this->cacheBackend->expects($this->at(2))
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
   * Tests the cache calls for an not existing table:
   *
   * Warm cache:
   *   - all tables
   *   - views_test_data
   *   - $non_existing_table
   */
  public function testCacheCallsWithWarmCacheForInvalidTable() {
    $non_existing_table = $this->randomMachineName();
    $this->moduleHandler->expects($this->never())
      ->method('getImplementations');

    // Setup a warm cache backend for a single table.
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with("views_data:$non_existing_table:en")
      ->will($this->returnValue((object) ['data' => []]));
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
  public function testCacheCallsWithoutWarmCacheAndGetAllTables() {
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
   *   - all tables
   */
  public function testCacheCallsWithWarmCacheAndGetAllTables() {
    $expected_views_data = $this->viewsDataWithProvider();
    $this->moduleHandler->expects($this->never())
      ->method('getImplementations');

    // Setup a warm cache backend for a single table.
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with("views_data:en")
      ->will($this->returnValue((object) ['data' => $expected_views_data]));
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
   * @covers ::get
   */
  public function testCacheCallsWithoutWarmCacheAndGetMultipleTables() {
    $expected_views_data = $this->viewsDataWithProvider();
    $table_name = 'views_test_data';
    $table_name_2 = 'views_test_data_2';

    // Setup a warm cache backend for all table data, but not single tables.
    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with("views_data:$table_name:en")
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with('views_data:en')
      ->will($this->returnValue((object) ['data' => $expected_views_data]));
    $this->cacheBackend->expects($this->at(2))
      ->method('set')
      ->with("views_data:$table_name:en", $expected_views_data[$table_name]);
    $this->cacheBackend->expects($this->at(3))
      ->method('get')
      ->with("views_data:$table_name_2:en")
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(4))
      ->method('set')
      ->with("views_data:$table_name_2:en", $expected_views_data[$table_name_2]);

    $this->assertSame($expected_views_data[$table_name], $this->viewsData->get($table_name));
    $this->assertSame($expected_views_data[$table_name_2], $this->viewsData->get($table_name_2));

    // Should only be invoked the first time.
    $this->assertSame($expected_views_data[$table_name], $this->viewsData->get($table_name));
    $this->assertSame($expected_views_data[$table_name_2], $this->viewsData->get($table_name_2));
  }

  /**
   * Tests that getting all data has same results as getting data with NULL
   * logic.
   *
   * @covers ::getAll
   */
  public function testGetAllEqualsToGetNull() {
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
      $this->assertSame($expected_views_data, $this->viewsData->getAll());
      $this->assertSame($expected_views_data, $this->viewsData->get());
    }
  }

}
