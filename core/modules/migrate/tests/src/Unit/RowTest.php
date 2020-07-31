<?php

namespace Drupal\Tests\migrate\Unit;

use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate\Row
 * @group migrate
 */
class RowTest extends UnitTestCase {

  /**
   * The source IDs.
   *
   * @var array
   */
  protected $testSourceIds = [
    'nid' => 'Node ID',
  ];

  /**
   * The test values.
   *
   * @var array
   */
  protected $testValues = [
    'nid' => 1,
    'title' => 'node 1',
  ];

  /**
   * Test source properties for testing get and getMultiple.
   *
   * @var array
   */
  protected $testGetSourceProperties = [
    'source_key_1' => 'source_value_1',
    'source_key_2' => 'source_value_2',
    '@source_key_3' => 'source_value_3',
    'shared_key_1' => 'source_shared_value_1',
    '@shared_key_2' => 'source_shared_value_2',
    '@@@@shared_key_3' => 'source_shared_value_3',
  ];

  /**
   * Test source keys for testing get and getMultiple.
   *
   * @var array
   */
  protected $testGetSourceIds = [
    'source_key_1' => [],
  ];

  /**
   * Test destination properties for testing get and getMultiple.
   *
   * @var array
   */
  protected $testGetDestinationProperties = [
    'destination_key_1' => 'destination_value_1',
    'destination_key_2' => 'destination_value_2',
    '@destination_key_3' => 'destination_value_3',
    'shared_key_1' => 'destination_shared_value_1',
    '@shared_key_2' => 'destination_shared_value_2',
    '@@@@shared_key_3' => 'destination_shared_value_3',
  ];

  /**
   * The test hash.
   *
   * @var string
   */
  protected $testHash = '85795d4cde4a2425868b812cc88052ecd14fc912e7b9b4de45780f66750e8b1e';

  /**
   * The test hash after changing title value to 'new title'.
   *
   * @var string
   */
  protected $testHashMod = '9476aab0b62b3f47342cc6530441432e5612dcba7ca84115bbab5cceaca1ecb3';

  /**
   * Tests object creation: empty.
   */
  public function testRowWithoutData() {
    $row = new Row();
    $this->assertSame([], $row->getSource(), 'Empty row');
  }

  /**
   * Tests object creation: basic.
   */
  public function testRowWithBasicData() {
    $row = new Row($this->testValues, $this->testSourceIds);
    $this->assertSame($this->testValues, $row->getSource(), 'Row with data, simple id.');
  }

  /**
   * Tests object creation: multiple source IDs.
   */
  public function testRowWithMultipleSourceIds() {
    $multi_source_ids = $this->testSourceIds + ['vid' => 'Node revision'];
    $multi_source_ids_values = $this->testValues + ['vid' => 1];
    $row = new Row($multi_source_ids_values, $multi_source_ids);
    $this->assertSame($multi_source_ids_values, $row->getSource(), 'Row with data, multifield id.');
  }

  /**
   * Tests object creation: invalid values.
   */
  public function testRowWithInvalidData() {
    $invalid_values = [
      'title' => 'node X',
    ];
    $this->expectException(\Exception::class);
    $row = new Row($invalid_values, $this->testSourceIds);
  }

  /**
   * Tests source immutability after freeze.
   */
  public function testSourceFreeze() {
    $row = new Row($this->testValues, $this->testSourceIds);
    $row->rehash();
    $this->assertSame($this->testHash, $row->getHash(), 'Correct hash.');
    $row->setSourceProperty('title', 'new title');
    $row->rehash();
    $this->assertSame($this->testHashMod, $row->getHash(), 'Hash changed correctly.');
    $row->freezeSource();
    $this->expectException(\Exception::class);
    $row->setSourceProperty('title', 'new title');
  }

  /**
   * Tests setting on a frozen row.
   */
  public function testSetFrozenRow() {
    $row = new Row($this->testValues, $this->testSourceIds);
    $row->freezeSource();
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("The source is frozen and can't be changed any more");
    $row->setSourceProperty('title', 'new title');
  }

  /**
   * Tests hashing.
   */
  public function testHashing() {
    $row = new Row($this->testValues, $this->testSourceIds);
    $this->assertSame('', $row->getHash(), 'No hash at creation');
    $row->rehash();
    $this->assertSame($this->testHash, $row->getHash(), 'Correct hash.');
    $row->rehash();
    $this->assertSame($this->testHash, $row->getHash(), 'Correct hash even doing it twice.');

    // Set the map to needs update.
    $test_id_map = [
      'original_hash' => '',
      'hash' => '',
      'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    ];
    $row->setIdMap($test_id_map);
    $this->assertTrue($row->needsUpdate());

    $row->rehash();
    $this->assertSame($this->testHash, $row->getHash(), 'Correct hash even if id_mpa have changed.');
    $row->setSourceProperty('title', 'new title');
    $row->rehash();
    $this->assertSame($this->testHashMod, $row->getHash(), 'Hash changed correctly.');
    // Check hash calculation algorithm.
    $hash = hash('sha256', serialize($row->getSource()));
    $this->assertSame($hash, $row->getHash());
    // Check length of generated hash used for mapping schema.
    $this->assertSame(64, strlen($row->getHash()));

    // Set the map to successfully imported.
    $test_id_map = [
      'original_hash' => '',
      'hash' => '',
      'source_row_status' => MigrateIdMapInterface::STATUS_IMPORTED,
    ];
    $row->setIdMap($test_id_map);
    $this->assertFalse($row->needsUpdate());

    // Set the same hash value and ensure it was not changed.
    $random = $this->randomMachineName();
    $test_id_map = [
      'original_hash' => $random,
      'hash' => $random,
      'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    ];
    $row->setIdMap($test_id_map);
    $this->assertFalse($row->changed());

    // Set different has values to ensure it is marked as changed.
    $test_id_map = [
      'original_hash' => $this->randomMachineName(),
      'hash' => $this->randomMachineName(),
      'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    ];
    $row->setIdMap($test_id_map);
    $this->assertTrue($row->changed());
  }

  /**
   * Tests getting/setting the ID Map.
   *
   * @covers ::setIdMap
   * @covers ::getIdMap
   */
  public function testGetSetIdMap() {
    $row = new Row($this->testValues, $this->testSourceIds);
    $test_id_map = [
      'original_hash' => '',
      'hash' => '',
      'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    ];
    $row->setIdMap($test_id_map);
    $this->assertEquals($test_id_map, $row->getIdMap());
  }

  /**
   * Tests the source ID.
   */
  public function testSourceIdValues() {
    $row = new Row($this->testValues, $this->testSourceIds);
    $this->assertSame(['nid' => $this->testValues['nid']], $row->getSourceIdValues());
  }

  /**
   * Tests the multiple source IDs.
   */
  public function testMultipleSourceIdValues() {
    // Set values in same order as ids.
    $multi_source_ids = $this->testSourceIds + [
        'vid' => 'Node revision',
        'type' => 'Node type',
        'langcode' => 'Node language',
      ];
    $multi_source_ids_values = $this->testValues + [
        'vid' => 1,
        'type' => 'page',
        'langcode' => 'en',
      ];
    $row = new Row($multi_source_ids_values, $multi_source_ids);
    $this->assertSame(array_keys($multi_source_ids), array_keys($row->getSourceIdValues()));

    // Set values in different order.
    $multi_source_ids = $this->testSourceIds + [
        'vid' => 'Node revision',
        'type' => 'Node type',
        'langcode' => 'Node language',
      ];
    $multi_source_ids_values = $this->testValues + [
        'langcode' => 'en',
        'type' => 'page',
        'vid' => 1,
      ];
    $row = new Row($multi_source_ids_values, $multi_source_ids);
    $this->assertSame(array_keys($multi_source_ids), array_keys($row->getSourceIdValues()));
  }

  /**
   * Tests getting the source property.
   *
   * @covers ::getSourceProperty
   */
  public function testGetSourceProperty() {
    $row = new Row($this->testValues, $this->testSourceIds);
    $this->assertSame($this->testValues['nid'], $row->getSourceProperty('nid'));
    $this->assertSame($this->testValues['title'], $row->getSourceProperty('title'));
    $this->assertNull($row->getSourceProperty('non_existing'));
  }

  /**
   * Tests setting and getting the destination.
   */
  public function testDestination() {
    $row = new Row($this->testValues, $this->testSourceIds);
    $this->assertEmpty($row->getDestination());
    $this->assertFalse($row->hasDestinationProperty('nid'));

    // Set a destination.
    $row->setDestinationProperty('nid', 2);
    $this->assertTrue($row->hasDestinationProperty('nid'));
    $this->assertEquals(['nid' => 2], $row->getDestination());
  }

  /**
   * Tests setting/getting multiple destination IDs.
   */
  public function testMultipleDestination() {
    $row = new Row($this->testValues, $this->testSourceIds);
    // Set some deep nested values.
    $row->setDestinationProperty('image/alt', 'alt text');
    $row->setDestinationProperty('image/fid', 3);

    $this->assertTrue($row->hasDestinationProperty('image'));
    $this->assertFalse($row->hasDestinationProperty('alt'));
    $this->assertFalse($row->hasDestinationProperty('fid'));

    $destination = $row->getDestination();
    $this->assertEquals('alt text', $destination['image']['alt']);
    $this->assertEquals(3, $destination['image']['fid']);
    $this->assertEquals('alt text', $row->getDestinationProperty('image/alt'));
    $this->assertEquals(3, $row->getDestinationProperty('image/fid'));
  }

  /**
   * Test getting source and destination properties.
   *
   * @param string $key
   *   The key to look up.
   * @param string $expected_value
   *   The expected value.
   *
   * @dataProvider getDataProvider
   * @covers ::get
   */
  public function testGet($key, $expected_value) {
    $row = $this->createRowWithDestinationProperties($this->testGetSourceProperties, $this->testGetSourceIds, $this->testGetDestinationProperties);
    $this->assertSame($expected_value, $row->get($key));
  }

  /**
   * Data Provider for testGet.
   *
   * @return array
   *   The keys and expected values.
   */
  public function getDataProvider() {
    return [
      ['source_key_1', 'source_value_1'],
      ['source_key_2', 'source_value_2'],
      ['@@source_key_3', 'source_value_3'],
      ['shared_key_1', 'source_shared_value_1'],
      ['@@shared_key_2', 'source_shared_value_2'],
      ['@@@@@@@@shared_key_3', 'source_shared_value_3'],
      ['@destination_key_1', 'destination_value_1'],
      ['@destination_key_2', 'destination_value_2'],
      ['@@@destination_key_3', 'destination_value_3'],
      ['@shared_key_1', 'destination_shared_value_1'],
      ['@@@shared_key_2', 'destination_shared_value_2'],
      ['@@@@@@@@@shared_key_3', 'destination_shared_value_3'],
      ['destination_key_1', NULL],
      ['@shared_key_2', NULL],
      ['@source_key_1', NULL],
      ['random_source_key', NULL],
      ['@random_destination_key', NULL],
    ];
  }

  /**
   * Test getting multiple source and destination properties.
   *
   * @param array $keys
   *   An array of keys to look up.
   * @param array $expected_values
   *   An array of expected values.
   *
   * @covers::getMultiple
   * @dataProvider getMultipleDataProvider
   */
  public function testGetMultiple(array $keys, array $expected_values) {
    $row = $this->createRowWithDestinationProperties($this->testGetSourceProperties, $this->testGetSourceIds, $this->testGetDestinationProperties);
    $this->assertArrayEquals(array_combine($keys, $expected_values), $row->getMultiple($keys));
  }

  /**
   * Data Provider for testGetMultiple.
   *
   * @return array
   *   The keys and expected values.
   */
  public function getMultipleDataProvider() {
    return [
      'Single Key' => [
        'keys' => ['source_key_1'],
        'values' => ['source_value_1'],
      ],
      'All Source Keys' => [
        'keys' => [
          'source_key_1',
          'source_key_2',
          '@@source_key_3',
        ],
        'values' => [
          'source_value_1',
          'source_value_2',
          'source_value_3',
        ],
      ],
      'All Destination Keys' => [
        'keys' => [
          '@destination_key_1',
          '@destination_key_2',
          '@@@destination_key_3',
        ],
        'values' => [
          'destination_value_1',
          'destination_value_2',
          'destination_value_3',
        ],
      ],
      'Mix of keys including non-existent' => [
        'keys' => [
          'shared_key_1',
          '@shared_key_1',
          '@@shared_key_2',
          '@@@shared_key_2',
          '@@@@@@@@@shared_key_3',
          'non_existent_source_key',
          '@non_existent_destination_key',
        ],
        'values' => [
          'source_shared_value_1',
          'destination_shared_value_1',
          'source_shared_value_2',
          'destination_shared_value_2',
          'destination_shared_value_3',
          NULL,
          NULL,
        ],
      ],
    ];
  }

  /**
   * Create a row and load it with destination properties.
   *
   * @param array $source_properties
   *   The source property array.
   * @param array $source_ids
   *   The source ids array.
   * @param array $destination_properties
   *   The destination properties to load.
   * @param bool $is_stub
   *   Whether this row is a stub row, defaults to FALSE.
   *
   * @return \Drupal\migrate\Row
   *   The row, populated with destination properties.
   */
  protected function createRowWithDestinationProperties(array $source_properties, array $source_ids, array $destination_properties, $is_stub = FALSE) {
    $row = new Row($source_properties, $source_ids, $is_stub);
    foreach ($destination_properties as $key => $property) {
      $row->setDestinationProperty($key, $property);
    }
    return $row;
  }

}
