<?php
/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\process\StaticMapTest.
 */

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\StaticMap;

/**
 * Tests the static map process plugin.
 *
 * @group migrate
 */
class StaticMapTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
