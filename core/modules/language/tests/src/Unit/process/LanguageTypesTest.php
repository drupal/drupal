<?php

namespace Drupal\Tests\language\Unit\process;

use Drupal\language\Plugin\migrate\process\LanguageTypes;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * @coversDefaultClass \Drupal\language\Plugin\migrate\process\LanguageTypes
 * @group language
 */
class LanguageTypesTest extends MigrateProcessTestCase {

  /**
   * Tests successful transformation of all language types.
   */
  public function testTransformAll() {
    $this->plugin = new LanguageTypes([], 'map', []);
    $source = [
      'language' => TRUE,
      'language_url' => FALSE,
      'language_content' => FALSE,
    ];
    $expected = [
      0 => 'language_url',
      1 => 'language_content',
      2 => 'language_interface',
    ];
    $value = $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, $expected);
  }

  /**
   * Tests successful transformation of configurable language types.
   */
  public function testTransformConfigurable() {
    $this->plugin = new LanguageTypes(['filter_configurable' => TRUE], 'map', []);
    $source = [
      'language' => TRUE,
      'language_url' => FALSE,
      'language_content' => FALSE,
    ];
    $expected = [
      0 => 'language_interface',
    ];
    $value = $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, $expected);
  }

  /**
   * Tests string input.
   *
   * @expectedException \Drupal\migrate\MigrateException
   * @expectedExceptionMessage The input should be an array
   */
  public function testStringInput() {
    $this->plugin = new LanguageTypes([], 'map', []);
    $this->plugin->transform('foo', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
