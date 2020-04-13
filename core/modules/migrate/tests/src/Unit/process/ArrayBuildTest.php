<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\process\ArrayBuild;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\ArrayBuild
 * @group migrate
 */
class ArrayBuildTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $configuration = [
      'key' => 'foo',
      'value' => 'bar',
    ];
    $this->plugin = new ArrayBuild($configuration, 'map', []);
    parent::setUp();
  }

  /**
   * Tests successful transformation.
   */
  public function testTransform() {
    $source = [
      ['foo' => 'Foo', 'bar' => 'Bar'],
      ['foo' => 'foo bar', 'bar' => 'bar foo'],
    ];
    $expected = [
      'Foo' => 'Bar',
      'foo bar' => 'bar foo',
    ];
    $value = $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, $expected);
  }

  /**
   * Tests non-existent key for the key configuration.
   */
  public function testNonExistentKey() {
    $source = [
      ['bar' => 'foo'],
    ];
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage("The key 'foo' does not exist");
    $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests non-existent key for the value configuration.
   */
  public function testNonExistentValue() {
    $source = [
      ['foo' => 'bar'],
    ];
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage("The key 'bar' does not exist");
    $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests one-dimensional array input.
   */
  public function testOneDimensionalArrayInput() {
    $source = ['foo' => 'bar'];
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('The input should be an array of arrays');
    $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests string input.
   */
  public function testStringInput() {
    $source = 'foo';
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('The input should be an array of arrays');
    $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
