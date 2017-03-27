<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;
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
    $this->plugin = new StaticMap($configuration, 'map', []);
    parent::setUp();
  }

  /**
   * Tests map when the source is a string.
   */
  public function testMapWithSourceString() {
    $value = $this->plugin->transform('foo', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, ['bar' => 'baz']);
  }

  /**
   * Tests map when the source is a list.
   */
  public function testMapWithSourceList() {
    $value = $this->plugin->transform(['foo', 'bar'], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'baz');
  }

  /**
   * Tests when the source is empty.
   */
  public function testMapwithEmptySource() {
    $this->setExpectedException(MigrateException::class);
    $this->plugin->transform([], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests when the source is invalid.
   */
  public function testMapwithInvalidSource() {
    $this->setExpectedException(MigrateSkipRowException::class);
    $this->plugin->transform(['bar'], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests when the source is invalid but there's a default.
   */
  public function testMapWithInvalidSourceWithADefaultValue() {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['default_value'] = 'test';
    $this->plugin = new StaticMap($configuration, 'map', []);
    $value = $this->plugin->transform(['bar'], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'test');
  }

  /**
   * Tests when the source is invalid but there's a default value of NULL.
   */
  public function testMapWithInvalidSourceWithANullDefaultValue() {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['default_value'] = NULL;
    $this->plugin = new StaticMap($configuration, 'map', []);
    $value = $this->plugin->transform(['bar'], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertNull($value);
  }

  /**
   * Tests when the source is invalid and bypass is enabled.
   */
  public function testMapWithInvalidSourceAndBypass() {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['default_value'] = 'test';
    $configuration['bypass'] = TRUE;
    $this->plugin = new StaticMap($configuration, 'map', []);
    $this->setExpectedException(MigrateException::class, 'Setting both default_value and bypass is invalid.');
    $this->plugin->transform(['bar'], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
