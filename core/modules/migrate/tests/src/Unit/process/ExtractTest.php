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
    $value = $this->plugin->transform(['foo' => 'bar'], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('bar', $value);
  }

  /**
   * Tests invalid input.
   */
  public function testExtractFromString() {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Input should be an array.');
    $this->plugin->transform('bar', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests unsuccessful extraction.
   */
  public function testExtractFail() {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Array index missing, extraction failed.');
    $this->plugin->transform(['bar' => 'foo'], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests unsuccessful extraction.
   */
  public function testExtractFailDefault() {
    $plugin = new Extract(['index' => ['foo'], 'default' => 'test'], 'map', []);
    $value = $plugin->transform(['bar' => 'foo'], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('test', $value, '');
  }

}
