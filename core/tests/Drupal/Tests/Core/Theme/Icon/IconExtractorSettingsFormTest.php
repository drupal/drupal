<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Theme\Icon\IconExtractorSettingsForm;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Icon\IconExtractorSettingsForm
 *
 * @group icon
 */
class IconExtractorSettingsFormTest extends UnitTestCase {

  /**
   * Provide data for testGenerateSettingsForm.
   *
   * @return array
   *   The data for settings and expected.
   *
   * @phpcs:disable
   */
  public static function settingsFormDataProvider(): array {
    return [
      'default case for string field' => [
        'settings' => [
          'test_string_default' => [
            'title' => 'Test String',
            'type' => 'string',
            'description' => 'Form test string.',
          ],
        ],
        'expected' => [
          'test_string_default' => [
            '#title' => 'Test String',
            '#description' => 'Form test string.',
            '#type' => 'textfield',
          ],
        ],
      ],
      'case for string field' => [
        'settings' => [
          'test_string' => [
            'title' => 'Test String',
            'type' => 'string',
            'description' => 'Form test string',
            'maxLength' => 33,
            'pattern' => '_pattern_',
          ],
        ],
        'expected' => [
          'test_string' => [
            '#title' => 'Test String',
            '#description' => 'Form test string',
            '#type' => 'textfield',
            '#pattern' => '_pattern_',
            '#maxlength' => 33,
          ],
        ],
      ],
      'case for string field with min' => [
        'settings' => [
          'test_string' => [
            'title' => 'Test String',
            'type' => 'string',
            'description' => 'Form test string',
            'minLength' => 10,
            'maxLength' => 33,
          ],
        ],
        'expected' => [
          'test_string' => [
            '#title' => 'Test String',
            '#description' => 'Form test string',
            '#type' => 'textfield',
            '#pattern' => '^.{10,}$',
            '#maxlength' => 33,
          ],
        ],
      ],
      'case for number field' => [
        'settings' => [
          'test_number' => [
            'title' => 'Test Number',
            'description' => 'Form test number',
            'type' => 'number',
          ],
        ],
        'expected' => [
          'test_number' => [
            '#title' => 'Test Number',
            '#description' => 'Form test number',
            '#type' => 'number',
          ],
        ],
      ],
      'case for number field with min/max/step' => [
        'settings' => [
          'test_number' => [
            'title' => 'Test Number',
            'description' => 'Form test number',
            'type' => 'number',
            'minimum' => 1,
            'maximum' => 100,
            'multipleOf' => 1,
          ],
        ],
        'expected' => [
          'test_number' => [
            '#title' => 'Test Number',
            '#description' => 'Form test number',
            '#type' => 'number',
            '#step' => 1,
            '#min' => 1,
            '#max' => 100,
          ],
        ],
      ],
      'case for integer field' => [
        'settings' => [
          'test_integer' => [
            'title' => 'Test Integer',
            'description' => 'Form test integer',
            'type' => 'integer',
          ],
        ],
        'expected' => [
          'test_integer' => [
            '#title' => 'Test Integer',
            '#description' => 'Form test integer',
            '#type' => 'number',
            '#step' => 1,
          ],
        ],
      ],
      'case for integer field with step' => [
        'settings' => [
          'test_integer' => [
            'title' => 'Test Integer',
            'description' => 'Form test integer',
            'type' => 'integer',
            'multipleOf' => 5,
          ],
        ],
        'expected' => [
          'test_integer' => [
            '#title' => 'Test Integer',
            '#description' => 'Form test integer',
            '#type' => 'number',
            '#step' => 5,
          ],
        ],
      ],
      'case for boolean field' => [
        'settings' => [
          'test_boolean' => [
            'title' => 'Test Boolean',
            'description' => 'Form test boolean',
            'type' => 'boolean',
          ],
        ],
        'expected' => [
          'test_boolean' => [
            '#title' => 'Test Boolean',
            '#description' => 'Form test boolean',
            '#type' => 'checkbox',
          ],
        ],
      ],
      'case for field with enum' => [
        'settings' => [
          'test_enum' => [
            'title' => 'Test Enum',
            'description' => 'Form test enum',
            'type' => 'string',
            'enum' => ['option1', 'option2', 'option3'],
          ],
        ],
        'expected' => [
          'test_enum' => [
            '#title' => 'Test Enum',
            '#description' => 'Form test enum',
            '#type' => 'select',
            '#options' => array_combine(['option1', 'option2', 'option3'], ['Option1', 'Option2', 'Option3']),
          ],
        ],
      ],
      'case for field with meta:enum' => [
        'settings' => [
          'test_enum' => [
            'title' => 'Test Enum',
            'description' => 'Form test enum',
            'type' => 'string',
            'enum' => ['option1', 'option2', 'option3'],
            'meta:enum' => [
              'option1' => 'Option 1',
              'option2' => 'Option 2',
              'option3' => 'Option 3',
            ],
          ],
        ],
        'expected' => [
          'test_enum' => [
            '#title' => 'Test Enum',
            '#description' => 'Form test enum',
            '#type' => 'select',
            '#options' => array_combine(['option1', 'option2', 'option3'], ['Option 1', 'Option 2', 'Option 3']),
          ],
        ],
      ],
      'case for field with meta:enum missing' => [
        'settings' => [
          'test_enum' => [
            'title' => 'Test Enum',
            'description' => 'Form test enum',
            'type' => 'string',
            'enum' => ['option1', 'option2', 'option3'],
            'meta:enum' => [
              'option1' => 'Option 1',
              'option3' => 'Option 3',
            ],
          ],
        ],
        'expected' => [
          'test_enum' => [
            '#title' => 'Test Enum',
            '#description' => 'Form test enum',
            '#type' => 'select',
            '#options' => array_combine(['option1', 'option3'], ['Option 1', 'Option 3']),
          ],
        ],
      ],
      'case for field with enum and default' => [
        'settings' => [
          'test_enum' => [
            'title' => 'Test Enum',
            'description' => 'Form test enum',
            'type' => 'string',
            'enum' => ['option1', 'option2', 'option3'],
            'default' => 'option2',
          ],
        ],
        'expected' => [
          'test_enum' => [
            '#title' => 'Test Enum',
            '#description' => 'Form test enum',
            '#type' => 'select',
            '#options' => array_combine(['option1', 'option2', 'option3'], ['Option1', 'Option2', 'Option3']),
            '#default_value' => 'option2',
          ],
        ],
      ],
      'case for field with default value' => [
        'settings' => [
          'test_default' => [
            'title' => 'Test Default',
            'description' => 'Form test default',
            'type' => 'string',
            'default' => 'default value',
          ],
        ],
        'expected' => [
          'test_default' => [
            '#title' => 'Test Default',
            '#description' => 'Form test default',
            '#default_value' => 'default value',
            '#type' => 'textfield',
          ],
        ],
      ],
      'case for float field' => [
        'settings' => [
          'test_number' => [
            'title' => 'Test float',
            'description' => 'Form test float',
            'type' => 'number',
            'minimum' => 10.0,
            'maximum' => 12.0,
            'multipleOf' => 0.1,
          ],
        ],
        'expected' => [
          'test_number' => [
            '#title' => 'Test float',
            '#description' => 'Form test float',
            '#type' => 'number',
            '#step' => 0.1,
            '#min' => 10.0,
            '#max' => 12.0,
          ],
        ],
      ],
      'case for float 2 decimal field' => [
        'settings' => [
          'test_number' => [
            'title' => 'Test float',
            'description' => 'Form test float',
            'type' => 'number',
            'minimum' => 10.01,
            'maximum' => 12.01,
            'multipleOf' => 0.01,
          ],
        ],
        'expected' => [
          'test_number' => [
            '#title' => 'Test float',
            '#description' => 'Form test float',
            '#type' => 'number',
            '#step' => 0.01,
            '#min' => 10.01,
            '#max' => 12.01,
          ],
        ],
      ],
      'case for color field' => [
        'settings' => [
          'test_color' => [
            'title' => 'Test color',
            'description' => 'Form test color',
            'type' => 'string',
            'format' => 'color',
          ],
        ],
        'expected' => [
          'test_color' => [
            '#title' => 'Test color',
            '#description' => 'Form test color',
            '#type' => 'color',
          ],
        ],
      ],
      'case for color field default' => [
        'settings' => [
          'test_color' => [
            'title' => 'Test color',
            'description' => 'Form test color',
            'type' => 'string',
            'format' => 'color',
            'default' => '#123456',
          ],
        ],
        'expected' => [
          'test_color' => [
            '#title' => 'Test color',
            '#description' => 'Form test color',
            '#type' => 'color',
            '#default_value' => '#123456',
          ],
        ],
      ],
    ];
  }

  /**
   * Test the IconExtractorSettingsForm::generateSettingsForm method.
   *
   * @param array<string, array<string, string>> $settings
   *   The settings to test.
   * @param array<string, array<string, string>> $expected
   *   The expected result.
   *
   * @dataProvider settingsFormDataProvider
   */
  public function testGenerateSettingsForm(array $settings, array $expected): void {
    $actual = IconExtractorSettingsForm::generateSettingsForm($settings);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test the IconExtractorSettingsForm::generateSettingsForm method.
   */
  public function testGenerateSettingsFormWithValues(): void {
    $options = [
      'test_saved' => [
        'title' => 'Test Saved',
        'description' => 'Form test saved',
        'type' => 'string',
      ],
    ];

    $form_state = $this->createMock(SubformStateInterface::class);
    $subform_state = $this->createMock(SubformStateInterface::class);
    $form_state->method('getCompleteFormState')->willReturn($subform_state);
    $subform_state->method('getValue')->with('saved_values')->willReturn(['test_saved' => 'saved value']);

    $actual = IconExtractorSettingsForm::generateSettingsForm($options, $form_state);
    $expected = [
      'test_saved' => [
        '#title' => $options['test_saved']['title'],
        '#description' => $options['test_saved']['description'],
        '#default_value' => 'saved value',
        '#type' => 'textfield',
      ],
    ];
    $this->assertSame($expected, $actual);
  }

}
