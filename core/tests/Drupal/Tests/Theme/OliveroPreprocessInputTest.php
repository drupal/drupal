<?php

namespace Drupal\Tests\Theme;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the Olivero theme's olivero_preprocess_input.
 *
 * @group olivero
 * @covers olivero_preprocess_input
 */
final class OliveroPreprocessInputTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../../themes/olivero/olivero.theme';
  }

  /**
   * Tests the olivero_preprocess_input adjustments.
   */
  public function testPreprocessInputForm() {

    // @todo: This was a first draft, but this might be be the correct approach
    // but pushing anyway.
    
    $variables = [
      'element' => [
        '#title_display' => '',
        'title' => 'Elem Title',
      ],
    ];

    olivero_preprocess_input($variables);
    $this->assertEquals($variables['attributes']['title'], $variables['element']['#title']);

  }

}
