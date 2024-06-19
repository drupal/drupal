<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Component\Utility\Variable;
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
  protected function setUp(): void {
    $configuration['map']['foo']['bar'] = 'baz';
    $this->plugin = new StaticMap($configuration, 'map', []);
    parent::setUp();
  }

  /**
   * Tests map when the source is a string.
   */
  public function testMapWithSourceString(): void {
    $value = $this->plugin->transform('foo', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame(['bar' => 'baz'], $value);
  }

  /**
   * Tests map when the source is a list.
   */
  public function testMapWithSourceList(): void {
    $value = $this->plugin->transform(['foo', 'bar'], $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('baz', $value);
  }

  /**
   * Tests when the source is empty.
   */
  public function testMapWithEmptySource(): void {
    $this->expectException(MigrateException::class);
    $this->plugin->transform([], $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests when the source is invalid.
   */
  public function testMapWithInvalidSource(): void {
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage(sprintf("No static mapping found for '%s' and no default value provided for destination '%s'.", Variable::export(['bar']), 'destination_property'));
    $this->plugin->transform(['bar'], $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests when the source is invalid but there's a default.
   */
  public function testMapWithInvalidSourceWithADefaultValue(): void {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['default_value'] = 'test';
    $this->plugin = new StaticMap($configuration, 'map', []);
    $value = $this->plugin->transform(['bar'], $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('test', $value);
  }

  /**
   * Tests when the source is invalid but there's a default value of NULL.
   */
  public function testMapWithInvalidSourceWithANullDefaultValue(): void {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['default_value'] = NULL;
    $this->plugin = new StaticMap($configuration, 'map', []);
    $value = $this->plugin->transform(['bar'], $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertNull($value);
  }

  /**
   * Tests when the source is invalid and bypass is enabled.
   */
  public function testMapWithInvalidSourceAndBypass(): void {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['default_value'] = 'test';
    $configuration['bypass'] = TRUE;
    $this->plugin = new StaticMap($configuration, 'map', []);
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Setting both default_value and bypass is invalid.');
    $this->plugin->transform(['bar'], $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests when the source is NULL.
   */
  public function testWithNullSourceNotInMap(): void {
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage("No static mapping found for 'NULL' and no default value provided for destination 'destination_property'");
    $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests when the source is invalid but there's a default.
   */
  public function testWithNullSource(): void {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['map'][NULL] = 'mapped NULL';
    $this->plugin = new StaticMap($configuration, 'map', []);
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('mapped NULL', $value);
  }

  /**
   * Tests when there is a dot in a map key.
   */
  public function testMapDotInKey(): void {
    $configuration['map']['foo.bar'] = 'baz';
    $this->plugin = new StaticMap($configuration, 'map', []);
    $value = $this->plugin->transform('foo.bar', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('baz', $value);
  }

}
