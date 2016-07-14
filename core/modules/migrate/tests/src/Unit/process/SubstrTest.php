<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\Substr;

/**
 * Tests the substr plugin.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\Substr
 *
 * @group migrate
 */
class SubstrTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests Substr plugin based on providerTestSubstr() values.
   *
   * @dataProvider providerTestSubstr
   */
  public function testSubstr($start = NULL, $length = NULL, $expected = NULL) {
    $configuration['start'] = $start;
    $configuration['length'] = $length;
    $this->plugin = new Substr($configuration, 'map', []);
    $value = $this->plugin->transform('Captain Janeway', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($expected, $value);
  }

  /**
   * Data provider for testSubstr().
   */
  public function providerTestSubstr() {
    return [
      // Tests with valid start and length values.
      [0, 7, 'Captain'],
      // Tests with valid start > 0 and valid length.
      [6, 3, 'n J'],
      // Tests with valid start < 0 and valid length.
      [-7, 4, 'Jane'],
      // Tests without start value and valid length value.
      [NULL, 7, 'Captain'],
      // Tests with valid start value and no length value.
      [1, NULL, 'aptain Janeway'],
      // Tests without both start and length values.
      [NULL, NULL, 'Captain Janeway'],
    ];
  }

  /**
   * Tests invalid input type.
   *
   * @expectedException \Drupal\migrate\MigrateException
   * @expectedExceptionMessage The input value must be a string.
   */
  public function testSubstrFail() {
    $configuration = [];
    $this->plugin = new Substr($configuration, 'map', []);
    $this->plugin->transform(['Captain Janeway'], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests that the start parameter is an integer.
   *
   * @expectedException \Drupal\migrate\MigrateException
   * @expectedExceptionMessage The start position configuration value should be an integer. Omit this key to capture from the beginning of the string.
   */
  public function testStartIsString() {
    $configuration['start'] = '2';
    $this->plugin = new Substr($configuration, 'map', []);
    $this->plugin->transform(['foo'], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Tests that the length parameter is an integer.
   *
   * @expectedException \Drupal\migrate\MigrateException
   * @expectedExceptionMessage The character length configuration value should be an integer. Omit this key to capture from the start position to the end of the string.
   */
  public function testLengthIsString() {
    $configuration['length'] = '1';
    $this->plugin = new Substr($configuration, 'map', []);
    $this->plugin->transform(['foo'], $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
