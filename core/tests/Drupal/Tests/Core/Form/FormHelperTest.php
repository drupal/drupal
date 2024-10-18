<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormHelper;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormHelper
 * @group Form
 */
class FormHelperTest extends UnitTestCase {

  /**
   * Tests rewriting the #states selectors.
   *
   * @covers ::rewriteStatesSelector
   */
  public function testRewriteStatesSelector(): void {

    // Simple selectors.
    $value = ['value' => 'medium'];
    $form['foo']['#states'] = [
      'visible' => [
        'select[name="fields[foo-id][settings_edit_form][settings][image_style]"]' => $value,
      ],
    ];
    FormHelper::rewriteStatesSelector($form, 'fields[foo-id][settings_edit_form]', 'options');
    $expected_selector = 'select[name="options[settings][image_style]"]';
    $this->assertSame($form['foo']['#states']['visible'][$expected_selector], $value, 'The #states selector was not properly rewritten.');

    // Complex selectors.
    $form = [];
    $form['bar']['#states'] = [
      'visible' => [
        [
          ':input[name="menu[type]"]' => ['value' => 'normal'],
        ],
        [
          ':input[name="menu[type]"]' => ['value' => 'tab'],
        ],
        ':input[name="menu[type]"]' => ['value' => 'default tab'],
      ],
      // Example from https://www.drupal.org/node/1464758
      'disabled' => [
        '[name="menu[options][dependee_1]"]' => ['value' => 'ON'],
        [
          ['[name="menu[options][dependee_2]"]' => ['value' => 'ON']],
          ['[name="menu[options][dependee_3]"]' => ['value' => 'ON']],
        ],
        [
          ['[name="menu[options][dependee_4]"]' => ['value' => 'ON']],
          'xor',
          ['[name="menu[options][dependee_5]"]' => ['value' => 'ON']],
        ],
      ],
    ];
    $expected['bar']['#states'] = [
      'visible' => [
        [
          ':input[name="options[type]"]' => ['value' => 'normal'],
        ],
        [
          ':input[name="options[type]"]' => ['value' => 'tab'],
        ],
        ':input[name="options[type]"]' => ['value' => 'default tab'],
      ],
      'disabled' => [
        '[name="options[options][dependee_1]"]' => ['value' => 'ON'],
        [
          ['[name="options[options][dependee_2]"]' => ['value' => 'ON']],
          ['[name="options[options][dependee_3]"]' => ['value' => 'ON']],
        ],
        [
          ['[name="options[options][dependee_4]"]' => ['value' => 'ON']],
          'xor',
          ['[name="options[options][dependee_5]"]' => ['value' => 'ON']],
        ],
      ],
    ];
    FormHelper::rewriteStatesSelector($form, 'menu', 'options');
    $this->assertSame($expected, $form, 'The #states selectors were properly rewritten.');
  }

  /**
   * @covers ::processStates
   * @dataProvider providerElements
   */
  public function testProcessStates($elements, $key): void {
    $json = Json::encode($elements['#states']);
    FormHelper::processStates($elements);
    $this->assertEquals(['core/drupal.states'], $elements['#attached']['library']);
    $this->assertEquals($json, $elements[$key]['data-drupal-states']);
  }

  /**
   * Provides a list of elements to test.
   */
  public static function providerElements() {
    return [
      [
        [
          '#type' => 'date',
          '#states' => [
            'visible' => [
              ':input[name="toggle_me"]' => ['checked' => TRUE],
            ],
          ],
        ],
        '#attributes',
      ],
      [
        [
          '#type' => 'item',
          '#states' => [
            'visible' => [
              ':input[name="foo"]' => ['value' => 'bar'],
            ],
          ],
          '#markup' => '',
          '#input' => TRUE,
        ],
        '#wrapper_attributes',
      ],
    ];
  }

}
