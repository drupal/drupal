<?php

namespace Drupal\Tests\language\Unit\process;

use Drupal\language\Plugin\migrate\process\LanguageNegotiation;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * @coversDefaultClass \Drupal\language\Plugin\migrate\process\LanguageNegotiation
 * @group language
 */
class LanguageNegotiationTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->plugin = new LanguageNegotiation([], 'map', []);
    parent::setUp();
  }

  /**
   * Tests successful transformation without weights.
   */
  public function testTransformWithWeights() {
    $source = [
      [
        'locale-url' => [],
        'language-default' => [],
      ],
      [
        'locale-url' => -10,
        'locale-session' => -9,
        'locale-user' => -8,
        'locale-browser' => -7,
        'language-default' => -6,
      ],
    ];
    $expected = [
      'enabled' => [
        'language-url' => -10,
        'language-selected' => -6,
      ],
      'method_weights' => [
        'language-url' => -10,
        'language-session' => -9,
        'language-user' => -8,
        'language-browser' => -7,
        'language-selected' => -6,
      ],
    ];
    $value = $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, $expected);
  }

  /**
   * Tests successful transformation without weights.
   */
  public function testTransformWithoutWeights() {
    $source = [
      [
        'locale-url' => [],
        'locale-url-fallback' => [],
      ],
    ];
    $expected = [
      'enabled' => [
        'language-url' => 0,
        'language-url-fallback' => 1,
      ],
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
    $this->plugin = new LanguageNegotiation([], 'map', []);
    $this->plugin->transform('foo', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
