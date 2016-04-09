<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\Explode;
use Drupal\migrate\Plugin\migrate\process\Concat;

/**
 * Tests the Explode process plugin.
 *
 * @group migrate
 */
class ExplodeTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $configuration = [
      'delimiter' => ',',
    ];
    $this->plugin = new Explode($configuration, 'map', []);
    parent::setUp();
  }

  /**
   * Test explode transform process works.
   */
  public function testTransform() {
    $value = $this->plugin->transform('foo,bar,tik', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, ['foo', 'bar', 'tik']);
  }

  /**
   * Test explode transform process works with a limit.
   */
  public function testTransformLimit() {
    $plugin = new Explode(['delimiter' => '_', 'limit' => 2], 'map', []);
    $value = $plugin->transform('foo_bar_tik', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, ['foo', 'bar_tik']);
  }

  /**
   * Test if the explode process can be chained with a handles_multiple process.
   */
  public function testChainedTransform() {
    $exploded = $this->plugin->transform('foo,bar,tik', $this->migrateExecutable, $this->row, 'destinationproperty');

    $concat = new Concat([], 'map', []);
    $concatenated = $concat->transform($exploded, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($concatenated, 'foobartik');
  }

  /**
   * Test explode fails properly on non-strings.
   *
   * @expectedException \Drupal\migrate\MigrateException
   *
   * @expectedExceptionMessage is not a string
   */
  public function testExplodeWithNonString() {
    $this->plugin->transform(['foo'], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Test explode fails with empty delimiter.
   *
   * @expectedException \Drupal\migrate\MigrateException
   *
   * @expectedExceptionMessage delimiter is empty
   */
  public function testExplodeWithEmptyDelimiter() {
    $plugin = new Explode(['delimiter' => ''], 'map', []);
    $plugin->transform('foo,bar', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
