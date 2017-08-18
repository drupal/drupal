<?php

namespace Drupal\Tests\system\Kernel\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Tests the maxlength HTML attribute on form elements.
 *
 * @group Form
 */
class FormElementMaxlengthTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_maxlength';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#maxlength' => 255,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Ensures maxlength attribute can be used in compatible elements.
   */
  public function testAttributes() {

    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form_builder = $this->container->get('form_builder');
    $form_state = new FormState();
    $elements = $form_builder->buildForm($this, $form_state);
    $this->render($elements);

    $css_selector_converter = new CssSelectorConverter();
    $elements = $this->xpath($css_selector_converter->toXPath('input[name=title][maxlength=255]'));
    $this->assertCount(1, $elements, 'Text field has correct maxlength in form.');
    $elements = $this->xpath($css_selector_converter->toXPath('textarea[name=description][maxlength=255]'));
    $this->assertCount(1, $elements, 'Textarea field has correct maxlength in form.');
  }

}
