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
  protected function setUp() {
    $configuration['index'] = ['foo'];
    $this->plugin = new Extract($configuration, 'map', []);
    parent::setUp();
  }

  /**
   * Tests successful extraction.
   */
  public function testExtract() {
    $value = $this->plugin->transform(['foo' => 'bar'], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'bar');
  }

  /**
   * Tests invalid input.
   */
  public function testExtractFromString() {
    $this->setExpectedException(MigrateException::class, 'Input should be an array.');
    $this->plugin->transform('bar', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests unsuccessful extraction.
   */
  public function testExtractFail() {
    $this->setExpectedException(MigrateException::class, 'Array index missing, extraction failed.');
    $this->plugin->transform(['bar' => 'foo'], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests unsuccessful extraction.
   */
  public function testExtractFailDefault() {
    $plugin = new Extract(['index' => ['foo'], 'default' => 'test'], 'map', []);
    $value = $plugin->transform(['bar' => 'foo'], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'test', '');
  }

}
