<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Components;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the correct rendering of components in form.
 */
#[RunTestsInSeparateProcesses]
#[Group('sdc')]
class ComponentAsFormElementTest extends ComponentKernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'sdc_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['sdc_theme_test'];

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'component_as_form_element_in_form_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['sdc_input'] = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:input',
    ];

    $form['sdc_input_basic'] = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:input',
      '#default_value' => 'test_data_default_value_basic',
    ];

    $form['sdc_input_with_label'] = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:input',
      '#slots' => [
        'label' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'id' => 'test_data_label_container',
          ],
          'content' => [
            '#markup' => 'test_data_label',
          ],
        ],
      ],
    ];

    $form['sdc_input_with_default_value'] = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:input',
      '#default_value' => 'test_data_default_value',
    ];

    $form['sdc_input_with_value'] = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:input',
      '#value' => 'test_data_value',
    ];

    $form['sdc_input_with_value_and_default_value'] = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:input',
      '#default_value' => 'test_data_default_value',
      '#value' => 'test_data_value',
    ];

    $form['sdc_input_with_required'] = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:input',
      '#required' => TRUE,
    ];

    $form['sdc_input_with_id_as_prop'] = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:input',
      '#props' => [
        'id' => 'test_sdc_input_prop_id',
      ],
    ];

    $form['sdc_input_with_id_as_prop_attributes'] = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:input',
      '#props' => [
        'attributes' => new Attribute(
          [
            'id' => 'test_sdc_input_prop_attributes_id',
          ]
        ),
      ],
    ];

    $form['sdc_input_with_validation'] = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:input',
      '#default_value' => 'test_data_valid_value',
      '#element_validate' => [
        [
          $this,
          'customValidator',
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => 'Submit',
      ],
    ];

    return $form;
  }

  /**
   * Validation callback for a datetime element.
   *
   * If the date is valid, the date object created from the user input is set in
   * the form for use by the caller. The work of compiling the user input back
   * into a date object is handled by the value callback, so we can use it here.
   * We also have the raw input available for validation testing.
   *
   * @param array $element
   *   The form element whose value is being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function customValidator(&$element, FormStateInterface $form_state, &$complete_form): void {
    $input_exists = FALSE;
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);

    // Example: Only allow 'test_data_valid_value' as valid.
    if ($input !== "test_data_valid_value") {
      $form_state->setError($element, "Invalid value provided.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Check that submitted data are present (set with #default_value).
    $data = [
      'sdc_input' => '',
      'sdc_input_basic' => 'test_data_default_value_basic',
      'sdc_input_with_label' => '',
      'sdc_input_with_default_value' => 'test_data_default_value',
      'sdc_input_with_value' => 'test_data_value',
      'sdc_input_with_value_and_default_value' => 'test_data_value',
      'sdc_input_with_id_as_prop' => '',
      'sdc_input_with_id_as_prop_attributes' => '',
    ];
    foreach ($data as $key => $value) {
      $this->assertSame($value, $form_state->getValue($key));
    }
  }

  /**
   * Tests that fields validation messages are sorted in the fields order.
   */
  public function testFormRenderingAndSubmission(): void {
    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form_builder = \Drupal::service('form_builder');
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $form = $form_builder->getForm($this);

    // Test form rendering.
    $markup = $renderer->renderRoot($form);
    $this->setRawContent($markup);

    // Ensure form elements are rendered once.
    $this->assertCount(1, $this->cssSelect('input[name="sdc_input"]'), 'The sdc_input textfield should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('input[name="sdc_input_basic"]'), 'The sdc_input_basic textfield should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('input[name="sdc_input_with_label"]'), 'The sdc_input_with_label textfield should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('span[id="test_data_label_container"]'), 'The span with id "test_data_label_container" should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('input[name="sdc_input_with_default_value"]'), 'The sdc_input_with_default_value textfield should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('input[name="sdc_input_with_value"]'), 'The sdc_input_with_value textfield should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('input[name="sdc_input_with_value_and_default_value"]'), 'The sdc_input_with_value_and_default_value textfield should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('input[name="sdc_input_with_required"]'), 'The sdc_input_with_required textfield should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('input[name="sdc_input_with_id_as_prop"]'), 'The sdc_input_with_id_as_prop textfield should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('input[id=test_sdc_input_prop_id]'), 'A textfield with id "test_sdc_input_prop_id" should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('input[name=sdc_input_with_id_as_prop]'), 'A sdc_input with id "sdc_input_with_id_as_prop" should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('input[name=sdc_input_with_id_as_prop_attributes]'), 'A sdc_input with id "sdc_input_with_id_as_prop_attributes" should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('div[id=test_sdc_input_prop_attributes_id]'), 'A div wrapper with id "test_sdc_input_prop_attributes_id" should have been rendered once.');

    // Check the position of the form elements in the DOM.
    $paths = [
      '//form/div[1]/input[@name="sdc_input"]',
      '//form/div[2]/input[@name="sdc_input_basic"]',
      '//form/div[3]/input[@name="sdc_input_with_label"]',
      '//form/div[4]/input[@name="sdc_input_with_default_value"]',
      '//form/div[5]/input[@name="sdc_input_with_value"]',
      '//form/div[6]/input[@name="sdc_input_with_value_and_default_value"]',
      '//form/div[7]/input[@name="sdc_input_with_required"]',
      '//form/div[8]/input[@name="sdc_input_with_id_as_prop"]',
      '//form/div[9]/input[@name="sdc_input_with_id_as_prop_attributes"]',
    ];

    foreach ($paths as $path) {
      $this->assertNotEmpty($this->xpath($path), 'There should be a result with the path: ' . $path . '.');
    }

    // Test form submission. Assertions are in submitForm().
    $form_state = new FormState();

    $form_builder->submitForm($this, $form_state);
  }

  /**
   * Tests that #element_validate works as expected.
   */
  public function testElementValidateCallback(): void {
    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form_builder = \Drupal::service('form_builder');

    // Build the form.
    $form_builder->getForm($this);

    // Simulate form submission with a value that should pass validation.
    $form_state = new FormState();
    $form_state->setValues([
      'sdc_input_with_required' => 'test_data_required_value',
      'sdc_input_with_validation' => 'test_data_valid_value',
    ]);
    $form_builder->submitForm($this, $form_state);

    // There should be no errors for valid value.
    $this->assertFalse($form_state->hasAnyErrors(), "No errors should be set for valid value.");

    // Simulate form submission with a value that should fail validation because
    // an invalid value is provided.
    $form_state = new FormState();
    $form_state->setValues([
      'sdc_input_with_required' => 'test_data_required_value',
      'sdc_input_with_validation' => 'invalid_value',
    ]);
    // You may need to adjust your customValidator to actually set
    // an error for this value.
    $form_builder->submitForm($this, $form_state);

    // There should be an error for invalid value.
    $this->assertTrue($form_state->hasAnyErrors(), "An error should be set for invalid value.");
    $this->assertArrayHasKey('sdc_input_with_validation', $form_state->getErrors(), "An error should be set for invalid value on sdc_input_with_validation.");

    // Simulate form submission with a value that should fail
    // validation because an invalid value is provided.
    $form_state = new FormState();
    $form_state->setValues([
      'sdc_input_with_validation' => 'test_data_valid_value',
    ]);
    // You may need to adjust your customValidator
    // to actually set an error for this value.
    $form_builder->submitForm($this, $form_state);

    // There should be an error for invalid value.
    $this->assertTrue($form_state->hasAnyErrors(), "An error should be set when required value is not provided.");
    $this->assertArrayHasKey('sdc_input_with_required', $form_state->getErrors(), "An error should be set for required field sdc_input_with_required.");
  }

}
