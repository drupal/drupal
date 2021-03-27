<?php

namespace Drupal\Tests\Theme;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the Olivero theme's hook_preprocess_field_multiple_value_form
 *
 * @group olivero
 */
final class OliveroPreprocesFieldMultipleValueForm extends UnitTestCase {

  /**
   * Tests the search block form has a theme suggestions.
   */
  public function testMakeDisabledAvailable() {
    require_once __DIR__ . '/../../../../themes/olivero/olivero.theme';
    $variables = [
      'element' => [
        '#disabled' => TRUE,
      ],
    ];
    olivero_preprocess_field_multiple_value_form($variables);
    $this->assertEquals(TRUE, $variables['disabled']);
  }

}
