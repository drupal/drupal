<?php

namespace Drupal\Tests\system\Kernel\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests fieldset element rendering and description placement.
 *
 * @group Form
 */
class ElementsFieldsetTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_fieldset_element';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['fieldset_default'] = [
      '#type' => 'fieldset',
      '#title' => 'Fieldset title for default description display',
      '#description' => 'Fieldset description for default description display.',
    ];
    $form['meta_default'] = [
      '#type' => 'container',
      '#title' => 'Group element',
      '#group' => 'fieldset_default',
    ];
    $form['meta_default']['element'] = [
      '#type' => 'textfield',
      '#title' => 'Nested text field inside meta_default element',
    ];

    $form['fieldset_before'] = [
      '#type' => 'fieldset',
      '#title' => 'Fieldset title for description displayed before element',
      '#description' => 'Fieldset description for description displayed before element.',
      '#description_display' => 'before',
    ];
    $form['meta_before'] = [
      '#type' => 'container',
      '#title' => 'Group element',
      '#group' => 'fieldset_before',
    ];
    $form['meta_before']['element'] = [
      '#type' => 'textfield',
      '#title' => 'Nested text field inside meta_before element',
    ];

    $form['fieldset_after'] = [
      '#type' => 'fieldset',
      '#title' => 'Fieldset title for description displayed after element',
      '#description' => 'Fieldset description for description displayed after element.',
      '#description_display' => 'after',
    ];
    $form['meta_after'] = [
      '#type' => 'container',
      '#title' => 'Group element',
      '#group' => 'fieldset_after',
    ];
    $form['meta_after']['element'] = [
      '#type' => 'textfield',
      '#title' => 'Nested text field inside meta_after element',
    ];

    $form['fieldset_invisible'] = [
      '#type' => 'fieldset',
      '#title' => 'Fieldset title for description displayed as visually hidden element',
      '#description' => 'Fieldset description for description displayed as visually hidden element.',
      '#description_display' => 'invisible',
    ];
    $form['meta_invisible'] = [
      '#type' => 'container',
      '#title' => 'Group element',
      '#group' => 'fieldset_invisible',
    ];
    $form['meta_invisible']['element'] = [
      '#type' => 'textfield',
      '#title' => 'Nested text field inside meta_invisible element',
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
   * Tests different display options for fieldset element descriptions.
   */
  public function testFieldsetDescriptions() {
    $form_state = new FormState();
    $form = \Drupal::formBuilder()->getForm($this);
    $this->render($form);

    // Check #description placement with #description_display not set. By
    // default, the #description should appear after any fieldset elements.
    $field_id = 'edit-fieldset-default';
    $description_id = $field_id . '--description';
    $elements = $this->xpath('//fieldset[@id="' . $field_id . '" and @aria-describedby="' . $description_id . '"]//div[@id="edit-meta-default"]/following-sibling::div[@id="' . $description_id . '"]');
    $this->assertCount(1, $elements);

    // Check #description placement with #description_display set to 'before'.
    $field_id = 'edit-fieldset-before';
    $description_id = $field_id . '--description';
    $elements = $this->xpath('//fieldset[@id="' . $field_id . '" and @aria-describedby="' . $description_id . '"]//div[@id="edit-meta-before"]/preceding-sibling::div[@id="' . $description_id . '"]');
    $this->assertCount(1, $elements);

    // Check #description placement with #description_display set to 'after'.
    $field_id = 'edit-fieldset-after';
    $description_id = $field_id . '--description';
    $elements = $this->xpath('//fieldset[@id="' . $field_id . '" and @aria-describedby="' . $description_id . '"]//div[@id="edit-meta-after"]/following-sibling::div[@id="' . $description_id . '"]');
    $this->assertCount(1, $elements);

    // Check if the 'visually-hidden' class is set on the fieldset description
    // with #description_display set to 'invisible'. Also check that the
    // description is placed after the form element.
    $field_id = 'edit-fieldset-invisible';
    $description_id = $field_id . '--description';
    $elements = $this->xpath('//fieldset[@id="' . $field_id . '" and @aria-describedby="' . $description_id . '"]//div[@id="edit-meta-invisible"]/following-sibling::div[contains(@class, "visually-hidden")]');
    $this->assertCount(1, $elements);

    \Drupal::formBuilder()->submitForm($this, $form_state);
    $errors = $form_state->getErrors();
    $this->assertEmpty($errors);
  }

}
