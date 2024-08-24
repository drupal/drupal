<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A form for testing form labels and required marks.
 *
 * @internal
 */
class FormTestLabelForm extends FormBase {

  /**
   * An array of elements that render a title.
   *
   * @var array
   */
  public static $typesWithTitle = [
    'checkbox',
    'checkboxes',
    'color',
    'date',
    'datelist',
    'datetime',
    'details',
    'email',
    'fieldset',
    'file',
    'item',
    'managed_file',
    'number',
    'password',
    'password_confirm',
    'radio',
    'radios',
    'range',
    'search',
    'select',
    'tel',
    'textarea',
    'textfield',
    'text_format',
    'url',
    'weight',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_label_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['form_checkboxes_test'] = [
      '#type' => 'checkboxes',
      '#title' => t('Checkboxes test'),
      '#options' => [
        'first-checkbox' => t('First checkbox'),
        'second-checkbox' => t('Second checkbox'),
        'third-checkbox' => t('Third checkbox'),
        '0' => t('0'),
      ],
    ];
    $form['form_radios_test'] = [
      '#type' => 'radios',
      '#title' => t('Radios test'),
      '#options' => [
        'first-radio' => t('First radio'),
        'second-radio' => t('Second radio'),
        'third-radio' => t('Third radio'),
        '0' => t('0'),
      ],
      // Test #field_prefix and #field_suffix placement.
      '#field_prefix' => '<span id="form-test-radios-field-prefix">' . t('Radios #field_prefix element') . '</span>',
      '#field_suffix' => '<span id="form-test-radios-field-suffix">' . t('Radios #field_suffix element') . '</span>',
    ];
    $form['form_checkbox_test'] = [
      '#type' => 'checkbox',
      '#title' => t('Checkbox test'),
    ];
    $form['form_textfield_test_title_and_required'] = [
      '#type' => 'textfield',
      '#title' => t('Textfield test for required with title'),
      '#required' => TRUE,
    ];
    $form['form_textfield_test_no_title_required'] = [
      '#type' => 'textfield',
      // We use an empty title, since not setting #title suppresses the label
      // and required marker.
      '#title' => '',
      '#required' => TRUE,
    ];
    $form['form_textfield_test_title'] = [
      '#type' => 'textfield',
      '#title' => t('Textfield test for title only'),
      // Not required.
      // Test #prefix and #suffix placement.
      '#prefix' => '<div id="form-test-textfield-title-prefix">' . t('Textfield #prefix element') . '</div>',
      '#suffix' => '<div id="form-test-textfield-title-suffix">' . t('Textfield #suffix element') . '</div>',
    ];
    $form['form_textfield_test_title_after'] = [
      '#type' => 'textfield',
      '#title' => t('Textfield test for title after element'),
      '#title_display' => 'after',
    ];
    $form['form_textfield_test_title_invisible'] = [
      '#type' => 'textfield',
      '#title' => t('Textfield test for invisible title'),
      '#title_display' => 'invisible',
    ];
    // Textfield test for title set not to display.
    $form['form_textfield_test_title_no_show'] = [
      '#type' => 'textfield',
    ];
    // Checkboxes & radios with title as attribute.
    $form['form_checkboxes_title_attribute'] = [
      '#type' => 'checkboxes',
      '#title' => 'Checkboxes test',
      '#title_display' => 'attribute',
      '#options' => [
        'first-checkbox' => 'First checkbox',
        'second-checkbox' => 'Second checkbox',
      ],
      '#required' => TRUE,
    ];
    $form['form_radios_title_attribute'] = [
      '#type' => 'radios',
      '#title' => 'Radios test',
      '#title_display' => 'attribute',
      '#options' => [
        'first-radio' => 'First radio',
        'second-radio' => 'Second radio',
      ],
      '#required' => TRUE,
    ];
    $form['form_checkboxes_title_invisible'] = [
      '#type' => 'checkboxes',
      '#title' => 'Checkboxes test invisible',
      '#title_display' => 'invisible',
      '#options' => [
        'first-checkbox' => 'First checkbox',
        'second-checkbox' => 'Second checkbox',
      ],
      '#required' => TRUE,
    ];
    $form['form_radios_title_invisible'] = [
      '#type' => 'radios',
      '#title' => 'Radios test invisible',
      '#title_display' => 'invisible',
      '#options' => [
        'first-radio' => 'First radio',
        'second-radio' => 'Second radio',
      ],
      '#required' => TRUE,
    ];

    foreach (static::$typesWithTitle as $type) {
      $form['form_' . $type . '_title_no_xss'] = [
        '#type' => $type,
        '#title' => "$type <script>alert('XSS')</script> is XSS filtered!",
      ];
      // Add keys that are required for some elements to be processed correctly.
      if (in_array($type, ['checkboxes', 'radios'], TRUE)) {
        $form['form_' . $type . '_title_no_xss']['#options'] = [];
      }
      if ($type === 'datetime') {
        $form['form_' . $type . '_title_no_xss']['#default_value'] = NULL;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
