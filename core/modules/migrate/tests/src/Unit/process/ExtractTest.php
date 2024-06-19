<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\process\Extract;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\Extract
 * @group migrate
 */
class ExtractTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $configuration['index'] = ['foo'];
    $this->plugin = new Extract($configuration, 'map', []);
    parent::setUp();
  }

  /**
   * Tests successful extraction.
   */
  public function testExtract(): void {
    $value = $this->plugin->transform(['foo' => 'bar'], $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('bar', $value);
  }

  /**
   * Tests invalid input.
   *
   * @dataProvider providerTestExtractInvalid
   */
  public function testExtractInvalid($value): void {
    $this->expectException(MigrateException::class);
    $type = gettype($value);
    $this->expectExceptionMessage(sprintf("Input should be an array, instead it was of type '%s'", $type));
    $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests unsuccessful extraction.
   */
  public function testExtractFail(): void {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage("Array index missing, extraction failed for 'array(\n  'bar' => 'foo',\n)'. Consider adding a `default` key to the configuration.");
    $this->plugin->transform(['bar' => 'foo'], $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests unsuccessful extraction.
   */
  public function testExtractFailDefault(): void {
    $plugin = new Extract(['index' => ['foo'], 'default' => 'test'], 'map', []);
    $value = $plugin->transform(['bar' => 'foo'], $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('test', $value, '');
  }

  /**
   * Test the extract plugin with default values.
   *
   * @param array $value
   *   The process plugin input value.
   * @param array $configuration
   *   The plugin configuration.
   * @param string|null $expected
   *   The expected transformed value.
   *
   * @throws \Drupal\migrate\MigrateException
   *
   * @dataProvider providerExtractDefault
   */
  public function testExtractDefault(array $value, array $configuration, $expected): void {
    $this->plugin = new Extract($configuration, 'map', []);

    $value = $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected, $value);
  }

  /**
   * Data provider for testExtractDefault.
   */
  public static function providerExtractDefault() {
    return [
      [
        ['foo' => 'bar'],
        [
          'index' => ['foo'],
          'default' => 'one',
        ],
        'bar',
      ],
      [
        ['foo' => 'bar'],
        [
          'index' => ['not_key'],
          'default' => 'two',
        ],
        'two',
      ],
      [
        ['foo' => 'bar'],
        [
          'index' => ['not_key'],
          'default' => NULL,
        ],
        NULL,
      ],
      [
        ['foo' => 'bar'],
        [
          'index' => ['not_key'],
          'default' => TRUE,
        ],
        TRUE,
      ],
      [
        ['foo' => 'bar'],
        [
          'index' => ['not_key'],
          'default' => FALSE,
        ],
        FALSE,
      ],
      [
        ['foo' => ''],
        [
          'index' => ['foo'],
          'default' => NULL,
        ],
        '',
      ],
    ];
  }

  /**
   * Provides data for the testExtractInvalid.
   */
  public static function providerTestExtractInvalid() {
    $xml_str = <<<XML
    <xml version='1.0'?>
      <authors>
        <name>Test Extract Invalid</name>
      </authors>
    XML;
    $object = (object) [
      'one' => 'test1',
      'two' => 'test2',
      'three' => 'test3',
    ];
    return [
      'empty string' => [
        '',
      ],
      'string' => [
        'Extract Test',
      ],
      'integer' => [
        1,
      ],
      'float' => [
        1.0,
      ],
      'NULL' => [
        NULL,
      ],
      'boolean' => [
        TRUE,
      ],
      'xml' => [
        $xml_str,
      ],
      'object' => [
        $object,
      ],
    ];
  }

}
