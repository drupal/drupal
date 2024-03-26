<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds a simple form to test states.
 *
 * @see \Drupal\FunctionalJavascriptTests\Core\Form\JavascriptStatesTest
 */
class JavascriptStatesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'javascript_states_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['header_triggers'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => 'Triggers',
    ];
    $form['checkbox_trigger'] = [
      '#type' => 'checkbox',
      '#title' => 'Checkbox trigger',
    ];
    $form['textfield_trigger'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield trigger',
    ];
    $form['radios_opposite1'] = [
      '#type' => 'radios',
      '#title' => 'Radios opposite 1',
      '#options' => [
        0 => 'zero',
        1 => 'one',
      ],
      '#default_value' => 0,
      0 => [
        '#states' => [
          'checked' => [
            ':input[name="radios_opposite2"]' => ['value' => 1],
          ],
        ],
      ],
      1 => [
        '#states' => [
          'checked' => [
            ':input[name="radios_opposite2"]' => ['value' => 0],
          ],
        ],
      ],
    ];
    $form['radios_opposite2'] = [
      '#type' => 'radios',
      '#title' => 'Radios opposite 2',
      '#options' => [
        0 => 'zero',
        1 => 'one',
      ],
      '#default_value' => 1,
      0 => [
        '#states' => [
          'checked' => [
            ':input[name="radios_opposite1"]' => ['value' => 1],
          ],
        ],
      ],
      1 => [
        '#states' => [
          'checked' => [
            ':input[name="radios_opposite1"]' => ['value' => 0],
          ],
        ],
      ],
    ];
    $form['radios_trigger'] = [
      '#type' => 'radios',
      '#title' => 'Radios trigger',
      '#options' => [
        'value1' => 'Value 1',
        'value2' => 'Value 2',
        'value3' => 'Value 3',
      ],
    ];
    $form['checkboxes_trigger'] = [
      '#type' => 'checkboxes',
      '#title' => 'Checkboxes trigger',
      '#options' => [
        'value1' => 'Value 1',
        'value2' => 'Value 2',
        'value3' => 'Value 3',
      ],
    ];
    $form['select_trigger'] = [
      '#type' => 'select',
      '#title' => 'Select trigger',
      '#options' => [
        'value1' => 'Value 1',
        'value2' => 'Value 2',
        'value3' => 'Value 3',
      ],
      '#empty_value' => '_none',
      '#empty_option' => '- None -',
    ];
    $form['number_trigger'] = [
      '#type' => 'number',
      '#title' => 'Number trigger',
    ];

    $form['multiple_select_trigger'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => 'Multiple select trigger',
      '#options' => [
        'value1' => 'Value 1',
        'value2' => 'Value 2',
        'value3' => 'Value 3',
      ],
    ];

    // Tested fields.
    $form['header_tested_elements'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => 'Tested elements',
    ];

    // Checkbox trigger.
    $form['header_checkbox'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Checkbox tests',
    ];
    $form['textfield_invisible_when_checkbox_trigger_checked'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield invisible when checkbox trigger checked',
      '#states' => [
        'invisible' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['textfield_required_when_checkbox_trigger_checked'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield required when checkbox trigger checked',
      '#states' => [
        'required' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['textfield_readonly_when_checkbox_trigger_checked'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield readonly when checkbox trigger checked',
      '#states' => [
        'readonly' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['textarea_readonly_when_checkbox_trigger_checked'] = [
      '#type' => 'textarea',
      '#title' => 'Textarea readonly when checkbox trigger checked',
      '#states' => [
        'readonly' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['details_expanded_when_checkbox_trigger_checked'] = [
      '#type' => 'details',
      '#title' => 'Details expanded when checkbox trigger checked',
      '#states' => [
        'expanded' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['details_expanded_when_checkbox_trigger_checked']['textfield_in_details'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield in details',
    ];
    $form['checkbox_checked_when_checkbox_trigger_checked'] = [
      '#type' => 'checkbox',
      '#title' => 'Checkbox checked when checkbox trigger checked',
      '#states' => [
        'checked' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['checkbox_unchecked_when_checkbox_trigger_checked'] = [
      '#type' => 'checkbox',
      '#title' => 'Checkbox unchecked when checkbox trigger checked',
      '#states' => [
        'unchecked' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['checkbox_visible_when_checkbox_trigger_checked'] = [
      '#type' => 'checkbox',
      '#title' => 'Checkbox visible when checkbox trigger checked',
      '#states' => [
        'visible' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['text_format_invisible_when_checkbox_trigger_checked'] = [
      '#type' => 'text_format',
      '#title' => 'Text format invisible when checkbox trigger checked',
      '#states' => [
        'invisible' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['checkboxes_all_checked_when_checkbox_trigger_checked'] = [
      '#type' => 'checkboxes',
      '#title' => 'Checkboxes: all checked when checkbox trigger checked',
      '#options' => [
        'value1' => 'Value 1',
        'value2' => 'Value 2',
        'value3' => 'Value 3',
      ],
      '#states' => [
        'checked' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['checkboxes_some_checked_when_checkbox_trigger_checked'] = [
      '#type' => 'checkboxes',
      '#title' => 'Checkboxes: some checked when checkbox trigger checked',
      '#options' => [
        'value1' => 'Value 1',
        'value2' => 'Value 2',
        'value3' => 'Value 3',
      ],
      'value1' => [
        '#states' => [
          'checked' => [
            ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
          ],
        ],
      ],
      'value3' => [
        '#states' => [
          'checked' => [
            ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
    $form['checkboxes_all_disabled_when_checkbox_trigger_checked'] = [
      '#type' => 'checkboxes',
      '#title' => 'Checkboxes: all disabled when checkbox trigger checked',
      '#options' => [
        'value1' => 'Value 1',
        'value2' => 'Value 2',
        'value3' => 'Value 3',
      ],
      '#states' => [
        'disabled' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['checkboxes_some_disabled_when_checkbox_trigger_checked'] = [
      '#type' => 'checkboxes',
      '#title' => 'Checkboxes: some disabled when checkbox trigger checked',
      '#options' => [
        'value1' => 'Value 1',
        'value2' => 'Value 2',
        'value3' => 'Value 3',
      ],
      'value1' => [
        '#states' => [
          'disabled' => [
            ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
          ],
        ],
      ],
      'value3' => [
        '#states' => [
          'disabled' => [
            ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
    $form['radios_checked_when_checkbox_trigger_checked'] = [
      '#type' => 'radios',
      '#title' => 'Radios checked when checkbox trigger checked',
      '#options' => [
        'value1' => 'Value 1',
        'value2' => 'Value 2',
      ],
      'value1' => [
        '#states' => [
          'checked' => [
            ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
    $form['radios_all_disabled_when_checkbox_trigger_checked'] = [
      '#type' => 'radios',
      '#title' => 'Radios: all disabled when checkbox trigger checked',
      '#options' => [
        'value1' => 'Value 1',
        'value2' => 'Value 2',
      ],
      '#states' => [
        'disabled' => [
          ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['radios_some_disabled_when_checkbox_trigger_checked'] = [
      '#type' => 'radios',
      '#title' => 'Radios: some disabled when checkbox trigger checked',
      '#options' => [
        'value1' => 'Value 1',
        'value2' => 'Value 2',
      ],
      'value1' => [
        '#states' => [
          'disabled' => [
            ':input[name="checkbox_trigger"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];

    // Checkboxes trigger.
    $form['header_checkboxes'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Checkboxes tests',
    ];
    $form['textfield_visible_when_checkboxes_trigger_value2_checked'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield visible when checkboxes trigger value2 checked',
      '#states' => [
        'visible' => [
          ':input[name="checkboxes_trigger[value2]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['textfield_visible_when_checkboxes_trigger_value3_checked'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield visible when checkboxes trigger value3 checked',
      '#states' => [
        'visible' => [
          ':input[name="checkboxes_trigger[value3]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Radios trigger.
    $form['header_radios'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Radios tests',
    ];
    $form['fieldset_visible_when_radios_trigger_has_value2'] = [
      '#type' => 'fieldset',
      '#title' => 'Fieldset visible when radio trigger has value2',
      '#states' => [
        'visible' => [
          ':input[name="radios_trigger"]' => ['value' => 'value2'],
        ],
      ],
    ];
    $form['fieldset_visible_when_radios_trigger_has_value2']['textfield_in_fieldset'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield in fieldset',
    ];
    $form['textfield_invisible_when_radios_trigger_has_value2'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield invisible when radio trigger has value2',
      '#states' => [
        'invisible' => [
          ':input[name="radios_trigger"]' => ['value' => 'value2'],
        ],
      ],
    ];
    $form['select_required_when_radios_trigger_has_value2'] = [
      '#type' => 'select',
      '#title' => 'Select required when radio trigger has value2',
      '#options' => [
        'value1' => 'Value 1',
        'value2' => 'Value 2',
        'value3' => 'Value 3',
      ],
      '#states' => [
        'required' => [
          ':input[name="radios_trigger"]' => ['value' => 'value2'],
        ],
      ],
    ];
    $form['checkbox_checked_when_radios_trigger_has_value3'] = [
      '#type' => 'checkbox',
      '#title' => 'Checkbox checked when radios trigger has value3',
      '#states' => [
        'checked' => [
          ':input[name="radios_trigger"]' => ['value' => 'value3'],
        ],
      ],
    ];
    $form['checkbox_unchecked_when_radios_trigger_has_value3'] = [
      '#type' => 'checkbox',
      '#title' => 'Checkbox unchecked when radios trigger has value3',
      '#states' => [
        'unchecked' => [
          ':input[name="radios_trigger"]' => ['value' => 'value3'],
        ],
      ],
    ];
    $form['details_expanded_when_radios_trigger_has_value3'] = [
      '#type' => 'details',
      '#title' => 'Details expanded when radio trigger has value3',
      '#states' => [
        'expanded' => [
          ':input[name="radios_trigger"]' => ['value' => 'value3'],
        ],
      ],
    ];
    $form['details_expanded_when_radios_trigger_has_value3']['textfield_in_details'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield in details',
    ];

    // Select trigger
    $form['header_select'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Select tests',
    ];
    $form['item_visible_when_select_trigger_has_value2'] = [
      '#type' => 'item',
      '#title' => 'Item visible when select trigger has value2',
      '#states' => [
        'visible' => [
          ':input[name="select_trigger"]' => ['value' => 'value2'],
        ],
      ],
    ];
    $form['textfield_visible_when_select_trigger_has_value3'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield visible when select trigger has value3',
      '#states' => [
        'visible' => [
          ':input[name="select_trigger"]' => ['value' => 'value3'],
        ],
      ],
    ];
    $form['textfield_visible_when_select_trigger_has_value2_or_value3'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield visible when select trigger has value2 or value3',
      '#states' => [
        'visible' => [
          ':input[name="select_trigger"]' => [
            ['value' => 'value2'],
            ['value' => 'value3'],
          ],
        ],
      ],
    ];

    // Multiple select trigger.
    $form['header_multiple_select'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Multiple select tests',
    ];
    $form['item_visible_when_multiple_select_trigger_has_value2'] = [
      '#type' => 'item',
      '#title' => 'Item visible when multiple select trigger has value2',
      '#states' => [
        'visible' => [
          'select[name="multiple_select_trigger[]"]' => ['value' => ['value2']],
        ],
      ],
    ];
    $form['item_visible_when_multiple_select_trigger_has_no_value'] = [
      '#type' => 'item',
      '#title' => 'Item visible when multiple select trigger has no value',
      '#states' => [
        'visible' => [
          'select[name="multiple_select_trigger[]"]' => ['value' => []],
        ],
      ],
    ];
    $form['textfield_visible_when_multiple_select_trigger_has_value3'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield visible when multiple select trigger has value3',
      '#states' => [
        'visible' => [
          'select[name="multiple_select_trigger[]"]' => ['value' => ['value3']],
        ],
      ],
    ];
    $form['textfield_visible_when_multiple_select_trigger_has_value2_or_value3'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield visible when multiple select trigger has value2 OR value3',
      '#states' => [
        'visible' => [
          'select[name="multiple_select_trigger[]"]' => [
            ['value' => ['value2']],
            ['value' => ['value3']],
          ],
        ],
      ],
    ];
    $form['textfield_visible_when_multiple_select_trigger_has_value2_and_value3'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield visible when multiple select trigger has value2 AND value3',
      '#states' => [
        'visible' => [
          'select[name="multiple_select_trigger[]"]' => [
            'value' => [
              'value3',
              'value2',
            ],
          ],
        ],
      ],
    ];

    // Textfield trigger.
    $form['header_textfield'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Textfield tests',
    ];
    $form['checkbox_checked_when_textfield_trigger_filled'] = [
      '#type' => 'checkbox',
      '#title' => 'Checkbox checked when textfield trigger filled',
      '#default_value' => '0',
      '#states' => [
        'checked' => [
          ':input[name="textfield_trigger"]' => ['filled' => TRUE],
        ],
      ],
    ];
    $form['checkbox_unchecked_when_textfield_trigger_filled'] = [
      '#type' => 'checkbox',
      '#title' => 'Checkbox unchecked when textfield trigger filled',
      '#default_value' => '1',
      '#states' => [
        'unchecked' => [
          ':input[name="textfield_trigger"]' => ['filled' => TRUE],
        ],
      ],
    ];
    $form['select_invisible_when_textfield_trigger_filled'] = [
      '#type' => 'select',
      '#title' => 'Select invisible when textfield trigger filled',
      '#options' => [0 => 0, 1 => 1, 2 => 2],
      '#states' => [
        'invisible' => [
          ':input[name="textfield_trigger"]' => ['filled' => TRUE],
        ],
      ],
    ];
    $form['select_visible_when_textfield_trigger_filled'] = [
      '#type' => 'select',
      '#title' => 'Select visible when textfield trigger filled',
      '#options' => [0 => 0, 1 => 1, 2 => 2],
      '#states' => [
        'visible' => [
          ':input[name="textfield_trigger"]' => ['filled' => TRUE],
        ],
      ],
    ];
    $form['textfield_required_when_textfield_trigger_filled'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield required  when textfield trigger filled',
      '#states' => [
        'required' => [
          ':input[name="textfield_trigger"]' => ['filled' => TRUE],
        ],
      ],
    ];
    $form['details_expanded_when_textfield_trigger_filled'] = [
      '#type' => 'details',
      '#title' => 'Details expanded when textfield trigger filled',
      '#states' => [
        'expanded' => [
          ':input[name="textfield_trigger"]' => ['filled' => TRUE],
        ],
      ],
    ];
    $form['details_expanded_when_textfield_trigger_filled']['textfield_in_details'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield in details',
    ];

    // Multiple triggers.
    $form['header_multiple_triggers'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Multiple triggers tests',
    ];
    $form['item_visible_when_select_trigger_has_value2_and_textfield_trigger_filled'] = [
      '#type' => 'item',
      '#title' => 'Item visible when select trigger has value2 and textfield trigger filled',
      '#states' => [
        'visible' => [
          ':input[name="select_trigger"]' => ['value' => 'value2'],
          ':input[name="textfield_trigger"]' => ['filled' => TRUE],
        ],
      ],
    ];

    // Number triggers.
    $form['header_number'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Number tests',
    ];
    $form['item_visible_when_number_trigger_filled_by_spinner'] = [
      '#type' => 'item',
      '#title' => 'Item visible when number trigger filled by spinner widget',
      '#states' => [
        'visible' => [
          ':input[name="number_trigger"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['select'] = [
      '#type' => 'select',
      '#title' => 'select 1',
      '#options' => [0 => 0, 1 => 1, 2 => 2],
    ];
    $form['number'] = [
      '#type' => 'number',
      '#title' => 'enter 1',
    ];
    $form['textfield'] = [
      '#type' => 'textfield',
      '#title' => 'textfield',
      '#states' => [
        'visible' => [
          [':input[name="select"]' => ['value' => '1']],
          'or',
          [':input[name="number"]' => ['value' => '1']],
        ],
      ],
    ];

    $form['link'] = [
      '#type' => 'link',
      '#title' => 'Link states test',
      '#url' => Url::fromRoute('<front>'),
      '#states' => [
        'visible' => [
          ':input[name="checkbox_trigger"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['test_button_disabled'] = [
      '#type' => 'container',
    ];

    $form['test_button_disabled']['submit_button'] = [
      '#type' => 'submit',
      '#value' => 'Submit button disabled when checkbox not checked',
      '#states' => [
        'disabled' => [':input[name="checkbox_enable_submit_button"]' => ['checked' => FALSE]],
      ],
    ];
    $form['test_button_disabled']['checkbox_enable_submit_button'] = [
      '#type' => 'checkbox',
      '#title' => 'Enable input submit',
    ];

    $form['test_button_disabled']['input_textfield'] = [
      '#type' => 'textfield',
      '#title' => 'Text field disabled when checkbox not checked',
      '#states' => [
        'disabled' => [':input[name="checkbox_enable_input_textfield"]' => ['checked' => FALSE]],
      ],
    ];
    $form['test_button_disabled']['checkbox_enable_input_textfield'] = [
      '#type' => 'checkbox',
      '#title' => 'Enable text field',
    ];

    $form['test_button_disabled']['test_select_disabled'] = [
      '#type' => 'select',
      '#title' => 'Select disabled when checkbox not checked',
      '#options' => [
        0 => 0,
        1 => 1,
      ],
      '#states' => [
        'disabled' => [':input[name="checkbox_enable_select"]' => ['checked' => FALSE]],
      ],
    ];
    $form['test_button_disabled']['checkbox_enable_select'] = [
      '#type' => 'checkbox',
      '#title' => 'Enable select',
    ];
    $form['test_button_disabled']['test_textarea_disabled'] = [
      '#type' => 'textarea',
      '#title' => 'Textarea disabled when checkbox not checked',
      '#states' => [
        'disabled' => [':input[name="checkbox_enable_textarea"]' => ['checked' => FALSE]],
      ],
    ];
    $form['test_button_disabled']['checkbox_enable_textarea'] = [
      '#type' => 'checkbox',
      '#title' => 'Enable textarea',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
