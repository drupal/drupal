<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Unit\process;

use Drupal\language\Plugin\migrate\process\LanguageTypes;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;
use Drupal\migrate\MigrateException;

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
    $value = $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'destination_property');
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
    $value = $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($value, $expected);
  }

  /**
   * Tests string input.
   */
  public function testStringInput() {
    $this->plugin = new LanguageTypes([], 'map', []);
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('The input should be an array');
    $this->plugin->transform('foo', $this->migrateExecutable, $this->row, 'destination_property');
  }

}
