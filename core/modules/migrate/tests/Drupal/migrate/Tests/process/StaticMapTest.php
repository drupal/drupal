<?php
/**
 * @file
 * Contains \Drupal\migrate\Tests\process\StaticMapTest.
 */

namespace Drupal\migrate\Tests\process;

use Drupal\migrate\Plugin\migrate\process\StaticMap;

/**
 * @group migrate
 * @group Drupal
 */
class StaticMapTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Map process plugin',
      'description' => 'Tests the map process plugin.',
      'group' => 'Migrate',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();
    $this->migrateExecutable = $this->getMockBuilder('Drupal\migrate\MigrateExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $configuration['map']['foo']['bar'] = 'baz';
    $this->plugin = new StaticMap($configuration, 'map', array());
    parent::setUp();
  }

  /**
   * Tests map when the source is a string.
   */
  public function testMapWithSourceString() {
    $value = $this->plugin->transform('foo', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, array('bar' => 'baz'));
  }

  /**
   * Tests map when the source is a list.
   */
  public function testMapWithSourceList() {
    $value = $this->plugin->transform(array('foo', 'bar'), $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'baz');
  }

  /**
   * Tests when the source is empty.
   *
   * @expectedException \Drupal\migrate\MigrateException
   */
  public function testMapwithEmptySource() {
    $this->plugin->transform(array(), $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests when the source is invalid.
   *
   * @expectedException \Drupal\migrate\MigrateSkipRowException
   */
  public function testMapwithInvalidSource() {
    $this->plugin->transform(array('bar'), $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests when the source is invalid but there's a default.
   */
  public function testMapWithInvalidSourceWithADefaultValue() {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['default_value'] = 'test';
    $this->plugin = new StaticMap($configuration, 'map', array());
    $value = $this->plugin->transform(array('bar'), $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'test');
  }

  /**
   * Tests when the source is invalid and bypass is enabled.
   *
   * @expectedException \Drupal\migrate\MigrateException
   * @expectedExceptionMessage Setting both default_value and bypass is invalid.
   */
  public function testMapWithInvalidSourceAndBypass() {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['default_value'] = 'test';
    $configuration['bypass'] = TRUE;
    $this->plugin = new StaticMap($configuration, 'map', array());
    $this->plugin->transform(array('bar'), $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
