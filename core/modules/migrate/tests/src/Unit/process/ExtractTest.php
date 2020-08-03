<?php

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
  public function testExtract() {
    $value = $this->plugin->transform(['foo' => 'bar'], $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('bar', $value);
  }

  /**
   * Tests invalid input.
   */
  public function testExtractFromString() {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Input should be an array.');
    $this->plugin->transform('bar', $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests unsuccessful extraction.
   */
  public function testExtractFail() {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Array index missing, extraction failed.');
    $this->plugin->transform(['bar' => 'foo'], $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests unsuccessful extraction.
   */
  public function testExtractFailDefault() {
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
  public function testExtractDefault(array $value, array $configuration, $expected) {
    $this->plugin = new Extract($configuration, 'map', []);

    $value = $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected, $value);
  }

  /**
   * Data provider for testExtractDefault.
   */
  public function providerExtractDefault() {
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

}
