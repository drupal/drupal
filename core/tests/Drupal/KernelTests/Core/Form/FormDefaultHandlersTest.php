<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests automatically added form handlers.
 *
 * @group Form
 */
class FormDefaultHandlersTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_form_handlers';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#validate'][] = '::customValidateForm';
    $form['#submit'][] = '::customSubmitForm';
    $form['submit'] = ['#type' => 'submit', '#value' => 'Save'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function customValidateForm(array &$form, FormStateInterface $form_state): void {
    $test_handlers = $form_state->get('test_handlers');
    $test_handlers['validate'][] = __FUNCTION__;
    $form_state->set('test_handlers', $test_handlers);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $test_handlers = $form_state->get('test_handlers');
    $test_handlers['validate'][] = __FUNCTION__;
    $form_state->set('test_handlers', $test_handlers);
  }

  /**
   * {@inheritdoc}
   */
  public function customSubmitForm(array &$form, FormStateInterface $form_state): void {
    $test_handlers = $form_state->get('test_handlers');
    $test_handlers['submit'][] = __FUNCTION__;
    $form_state->set('test_handlers', $test_handlers);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $test_handlers = $form_state->get('test_handlers');
    $test_handlers['submit'][] = __FUNCTION__;
    $form_state->set('test_handlers', $test_handlers);
  }

  /**
   * Tests that default handlers are added even if custom are specified.
   */
  public function testDefaultAndCustomHandlers(): void {
    $form_state = new FormState();
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);

    $handlers = $form_state->get('test_handlers');

    $this->assertCount(2, $handlers['validate']);
    $this->assertSame('customValidateForm', $handlers['validate'][0]);
    $this->assertSame('validateForm', $handlers['validate'][1]);

    $this->assertCount(2, $handlers['submit']);
    $this->assertSame('customSubmitForm', $handlers['submit'][0]);
    $this->assertSame('submitForm', $handlers['submit'][1]);
  }

}
