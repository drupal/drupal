<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Component\Utility\Variable;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\process\StaticMap;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests the static map process plugin.
 */
#[Group('migrate')]
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
    $this->expectExceptionMessage("No static mapping possible for NULL and no default value provided for destination 'destination_property'");
    $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests when the source is invalid but there's a mapping via an empty string.
   */
  #[IgnoreDeprecations]
  public function testWithNullSourceWithEmptyStringMapping(): void {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['map'][''] = 'mapped NULL';
    $this->plugin = new StaticMap($configuration, 'map', []);
    $this->expectDeprecation('Relying on mapping NULL values via an empty string map key in Drupal\migrate\Plugin\migrate\process\StaticMap::transform() is deprecated in drupal:11.3.0 and will trigger a Drupal\migrate\MigrateSkipRowException from drupal:12.0.0. Set the empty string map value as the "default_value" in the plugin configuration. See https://www.drupal.org/node/3557003');
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('mapped NULL', $value);
  }

  /**
   * Tests when the source is invalid but bypass is set to TRUE.
   */
  public function testWithNullSourceBypass(): void {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['bypass'] = TRUE;
    $this->plugin = new StaticMap($configuration, 'map', []);
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertNull($value);
  }

  /**
   * Tests when the source is invalid but default_value is set to TRUE.
   */
  public function testWithNullSourceDefaultValue(): void {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['default_value'] = FALSE;
    $this->plugin = new StaticMap($configuration, 'map', []);
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertFalse($value);
  }

  /**
   * Tests when the source returns an empty string and it is mapped to a value.
   */
  public function testWithEmptyStringMap(): void {
    $configuration['map']['foo']['bar'] = 'baz';
    $configuration['map'][''] = 'mapped empty string';
    $this->plugin = new StaticMap($configuration, 'map', []);
    $value = $this->plugin->transform('', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('mapped empty string', $value);
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
