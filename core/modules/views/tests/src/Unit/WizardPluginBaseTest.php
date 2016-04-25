<?php

namespace Drupal\Tests\views\Unit;

use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\wizard\WizardPluginBase
 *
 * @group views
 */
class WizardPluginBaseTest extends UnitTestCase {

  /**
   * @covers ::getSelected
   *
   * @dataProvider providerTestGetSelected
   */
  public function testGetSelected($expected, $element = [], $parents = [], $user_input = [], $not_rebuilding_expected = NULL) {
    $not_rebuilding_expected = $not_rebuilding_expected ?: $expected;
    $form_state = new FormState();
    $form_state->setUserInput($user_input);

    $actual = WizardPluginBase::getSelected($form_state, $parents, 'the_default_value', $element);
    $this->assertSame($not_rebuilding_expected, $actual);
    $this->assertSame($user_input, $form_state->getUserInput());

    $form_state->setRebuild();
    $actual = WizardPluginBase::getSelected($form_state, $parents, 'the_default_value', $element);
    $this->assertSame($expected, $actual);
    $this->assertSame($user_input, $form_state->getUserInput());
  }

  /**
   * Provides test data for testGetSelected().
   */
  public function providerTestGetSelected() {
    $data = [];
    // A form element with an invalid #type.
    $data['invalid_type'] = [
      'the_default_value',
      [
        '#type' => 'checkbox',
      ],
    ];
    // A form element with no #options.
    $data['no_options'] = [
      'the_default_value',
      [
        '#type' => 'select',
      ],
    ];
    // A valid form element with no user input.
    $data['no_user_input'] = [
      'the_default_value',
      [
        '#type' => 'select',
        '#options' => [
          'option1' => 'Option 1',
        ],
      ],
    ];
    // A valid form element with user input that doesn't correspond to it.
    $data['mismatched_input'] = [
      'the_default_value',
      [
        '#type' => 'select',
        '#options' => [
          'option1' => 'Option 1',
        ],
      ],
      ['foo', 'bar'],
      ['foo' => ['foo' => 'value1']],
    ];
    // A valid form element with a valid dynamic value that matches the default
    // value.
    $data['matching_default'] = [
      'the_default_value',
      [
        '#type' => 'select',
        '#options' => [
          'the_default_value' => 'Option 1',
        ],
      ],
      ['foo', 'bar'],
      ['foo' => ['bar' => 'the_default_value']],
    ];
    // A valid form element with a valid dynamic value that does not match the
    // default value.
    $data['mismatched_value'] = [
      'option1',
      [
        '#type' => 'select',
        '#options' => [
          'option1' => 'Option 1',
        ],
      ],
      ['foo', 'bar'],
      ['foo' => ['bar' => 'option1']],
      'the_default_value',
    ];
    return $data;
  }

}
