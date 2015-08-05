<?php
/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\process\ExtractTest.
 */

namespace Drupal\Tests\migrate\Unit\process;

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
    $configuration['index'] = array('foo');
    $this->plugin = new Extract($configuration, 'map', array());
    parent::setUp();
  }

  /**
   * Tests successful extraction.
   */
  public function testExtract() {
    $value = $this->plugin->transform(array('foo' => 'bar'), $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'bar');
  }

  /**
   * Tests invalid input.
   *
   * @expectedException \Drupal\migrate\MigrateException
   * @expectedExceptionMessage Input should be an array.
   */
  public function testExtractFromString() {
    $this->plugin->transform('bar', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests unsuccessful extraction.
   *
   * @expectedException \Drupal\migrate\MigrateException
   * @expectedExceptionMessage Array index missing, extraction failed.
   */
  public function testExtractFail() {
    $this->plugin->transform(array('bar' => 'foo'), $this->migrateExecutable, $this->row, 'destinationproperty');
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
