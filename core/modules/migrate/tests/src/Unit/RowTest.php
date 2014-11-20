<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\RowTest.
 */

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
  protected $testSourceIds = array(
    'nid' => 'Node ID',
  );

  /**
   * The test values.
   *
   * @var array
   */
  protected $testValues = array(
    'nid' => 1,
    'title' => 'node 1',
  );

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
    $row = new Row(array(), array());
    $this->assertSame(array(), $row->getSource(), 'Empty row');
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
    $multi_source_ids = $this->testSourceIds + array('vid' => 'Node revision');
    $multi_source_ids_values = $this->testValues + array('vid' => 1);
    $row = new Row($multi_source_ids_values, $multi_source_ids);
    $this->assertSame($multi_source_ids_values, $row->getSource(), 'Row with data, multifield id.');
  }

  /**
   * Tests object creation: invalid values.
   *
   * @expectedException \Exception
   */
  public function testRowWithInvalidData() {
    $invalid_values = array(
      'title' => 'node X',
    );
    $row = new Row($invalid_values, $this->testSourceIds);
  }

  /**
   * Tests source immutability after freeze.
   *
   * @expectedException \Exception
   */
  public function testSourceFreeze() {
    $row = new Row($this->testValues, $this->testSourceIds);
    $row->rehash();
    $this->assertSame($this->testHash, $row->getHash(), 'Correct hash.');
    $row->setSourceProperty('title', 'new title');
    $row->rehash();
    $this->assertSame($this->testHashMod, $row->getHash(), 'Hash changed correctly.');
    $row->freezeSource();
    $row->setSourceProperty('title', 'new title');
  }

  /**
   * Tests setting on a frozen row.
   *
   * @expectedException \Exception
   * @expectedExceptionMessage The source is frozen and can't be changed any more
   */
  public function testSetFrozenRow() {
    $row = new Row($this->testValues, $this->testSourceIds);
    $row->freezeSource();
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
    $test_id_map = array(
      'original_hash' => '',
      'hash' => '',
      'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    );
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
    $test_id_map = array(
      'original_hash' => '',
      'hash' => '',
      'source_row_status' => MigrateIdMapInterface::STATUS_IMPORTED,
    );
    $row->setIdMap($test_id_map);
    $this->assertFalse($row->needsUpdate());

    // Set the same hash value and ensure it was not changed.
    $random = $this->randomMachineName();
    $test_id_map = array(
      'original_hash' => $random,
      'hash' => $random,
      'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    );
    $row->setIdMap($test_id_map);
    $this->assertFalse($row->changed());

    // Set different has values to ensure it is marked as changed.
    $test_id_map = array(
      'original_hash' => $this->randomMachineName(),
      'hash' => $this->randomMachineName(),
      'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    );
    $row->setIdMap($test_id_map);
    $this->assertTrue($row->changed());
  }

  /**
   * Tests getting/setting the ID Map.
   *
   * @covers \Drupal\migrate\Row::setIdMap
   * @covers \Drupal\migrate\Row::getIdMap
   */
  public function testGetSetIdMap() {
    $row = new Row($this->testValues, $this->testSourceIds);
    $test_id_map = array(
      'original_hash' => '',
      'hash' => '',
      'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    );
    $row->setIdMap($test_id_map);
    $this->assertEquals($test_id_map, $row->getIdMap());
  }

  /**
   * Tests the source ID.
   */
  public function testSourceIdValues() {
    $row = new Row($this->testValues, $this->testSourceIds);
    $this->assertSame(array('nid' => $this->testValues['nid']), $row->getSourceIdValues());
  }

  /**
   * Tests getting the source property.
   *
   * @covers \Drupal\migrate\Row::getSourceProperty
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
    $this->assertEquals(array('nid' => 2), $row->getDestination());
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

}
