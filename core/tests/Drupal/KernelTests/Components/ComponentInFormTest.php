<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Components;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;

/**
 * Tests the correct rendering of components in form.
 *
 * @group sdc
 */
class ComponentInFormTest extends ComponentKernelTestBase implements FormInterface {

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
    return 'component_in_form_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['normal'] = [
      '#type' => 'textfield',
      '#title' => 'Normal form element',
      '#default_value' => 'fake 1',
    ];

    // We want to test form elements inside a component, itself inside a
    // component.
    $form['banner'] = [
      '#type' => 'component',
      '#component' => 'sdc_test:my-banner',
      '#props' => [
        'ctaText' => 'Click me!',
        'ctaHref' => 'https://www.example.org',
        'ctaTarget' => '',
      ],
      'banner_body' => [
        '#type' => 'component',
        '#component' => 'sdc_theme_test:my-card',
        '#props' => [
          'header' => 'Card header',
        ],
        'card_body' => [
          'foo' => [
            '#type' => 'textfield',
            '#title' => 'Textfield in component',
            '#default_value' => 'fake 2',
          ],
          'bar' => [
            '#type' => 'select',
            '#title' => 'Select in component',
            '#options' => [
              'option_1' => 'Option 1',
              'option_2' => 'Option 2',
            ],
            '#empty_option' => 'Empty option',
            '#default_value' => 'option_1',
          ],
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
      'normal' => 'fake 1',
      'foo' => 'fake 2',
      'bar' => 'option_1',
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

    // Test form structure after being processed.
    $this->assertTrue($form['normal']['#processed'], 'The normal textfield should have been processed.');
    $this->assertTrue($form['banner']['banner_body']['card_body']['bar']['#processed'], 'The textfield inside component should have been processed.');
    $this->assertTrue($form['banner']['banner_body']['card_body']['foo']['#processed'], 'The select inside component should have been processed.');
    $this->assertTrue($form['actions']['submit']['#processed'], 'The submit button should have been processed.');

    // Test form rendering.
    $markup = $renderer->renderRoot($form);
    $this->setRawContent($markup);

    // Ensure form elements are rendered once.
    $this->assertCount(1, $this->cssSelect('input[name="normal"]'), 'The normal textfield should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('input[name="foo"]'), 'The foo textfield should have been rendered once.');
    $this->assertCount(1, $this->cssSelect('select[name="bar"]'), 'The bar select should have been rendered once.');

    // Check the position of the form elements in the DOM.
    $paths = [
      '//form/div[1]/input[@name="normal"]',
      '//form/div[2][@data-component-id="sdc_test:my-banner"]/div[2][@class="component--my-banner--body"]/div[1][@data-component-id="sdc_theme_test:my-card"]/div[1][@class="component--my-card__body"]/div[1]/input[@name="foo"]',
      '//form/div[2][@data-component-id="sdc_test:my-banner"]/div[2][@class="component--my-banner--body"]/div[1][@data-component-id="sdc_theme_test:my-card"]/div[1][@class="component--my-card__body"]/div[2]/select[@name="bar"]',
    ];
    foreach ($paths as $path) {
      $this->assertNotEmpty($this->xpath($path), 'There should be a result with the path: ' . $path . '.');
    }

    // Test form submission. Assertions are in submitForm().
    $form_state = new FormState();
    $form_builder->submitForm($this, $form_state);
  }

}
