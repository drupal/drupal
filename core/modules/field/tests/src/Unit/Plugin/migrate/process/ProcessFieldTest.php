<?php

namespace Drupal\Tests\field\Unit\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\field\Plugin\migrate\process\ProcessField;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\MigrateFieldInterface;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * Tests the ProcessField migrate process plugin.
 *
 * @coversDefaultClass \Drupal\field\Plugin\migrate\process\ProcessField
 * @group field
 */
class ProcessFieldTest extends MigrateTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->fieldManager = $this->prophesize(MigrateFieldPluginManagerInterface::class);
    $this->fieldPlugin = $this->prophesize(MigrateFieldInterface::class);
    $this->migrateExecutable = $this->prophesize(MigrateExecutable::class);
    $this->migration = $this->prophesize(MigrationInterface::class);
    $this->row = $this->prophesize(Row::class);

    $this->fieldManager->getPluginIdFromFieldType('foo', [], $this->migration->reveal())->willReturn('foo');
    $this->fieldManager->createInstance('foo', [], $this->migration->reveal())->willReturn($this->fieldPlugin);

    parent::setUp();
  }

  /**
   * Tests the transform method.
   *
   * @param string $method
   *   The method to call.
   * @param string $value
   *   The value to process.
   * @param mixed $expected_value
   *   The expected transformed value.
   * @param string $migrate_exception
   *   The MigrateException message to expect.
   * @param bool $plugin_not_found
   *   Whether the field plugin is not found.
   *
   * @covers ::transform
   * @dataProvider providerTestTransform
   */
  public function testTransform($method, $value, $expected_value, $migrate_exception = '', $plugin_not_found = FALSE) {
    if ($method) {
      $this->fieldPlugin->$method($this->row->reveal())->willReturn($expected_value);
    }
    $this->plugin = new ProcessField(['method' => $method], $value, [], $this->fieldManager->reveal(), $this->migration->reveal());

    if ($migrate_exception) {
      $this->expectException(MigrateException::class);
      $this->expectExceptionMessage($migrate_exception);
    }

    if ($plugin_not_found) {
      $exception = new PluginNotFoundException('foo');
      $this->fieldManager->getPluginIdFromFieldType()->willThrow($exception);
    }

    $transformed_value = $this->plugin->transform($value, $this->migrateExecutable->reveal(), $this->row->reveal(), 'foo');
    $this->assertSame($transformed_value, $expected_value);
  }

  /**
   * Provides data for the transform method test.
   *
   * @return array
   *   - The method to call.
   *   - The value to process.
   *   - The expected transformed value.
   *   - The MigrateException message to expect.
   *   - Whether the field plugin is not found.
   */
  public function providerTestTransform() {
    return [
      // Tests the getFieldType() method.
      [
        'method' => 'getFieldType',
        'value' => 'foo',
        'expected_value' => 'bar',
      ],
      // Tests the getFieldFormatterMap() method.
      [
        'method' => 'getFieldFormatterMap',
        'value' => 'foo',
        'expected_value' => ['foo' => 'bar'],
      ],
      // Tests the getFieldWidgetMap() method.
      [
        'method' => 'getFieldWidgetMap',
        'value' => 'foo',
        'expected_value' => ['foo' => 'bar'],
      ],
      // Tests that an exception is thrown if the value is not a string.
      [
        'method' => 'getFieldType',
        'value' => ['foo'],
        'expected_value' => '',
        'migrate_exception' => 'The input value must be a string.',
      ],
      // Tests that an exception is thrown if no method name is provided.
      [
        'method' => '',
        'value' => '',
        'expected_value' => '',
        'migrate_exception' => 'You need to specify the name of a method to be called on the Field plugin.',
      ],
      // Tests that NULL is returned if no field plugin is found.
      [
        'method' => 'getFieldType',
        'value' => 'foo',
        'expected_value' => NULL,
        'migrate_exception' => '',
        'plugin_not_found' => TRUE,
      ],
    ];
  }

}
