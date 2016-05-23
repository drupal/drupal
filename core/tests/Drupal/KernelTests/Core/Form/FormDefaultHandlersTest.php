<?php

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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['key_value_expire']);
  }

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
    $form['submit'] = array('#type' => 'submit', '#value' => 'Save');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function customValidateForm(array &$form, FormStateInterface $form_state) {
    $test_handlers = $form_state->get('test_handlers');
    $test_handlers['validate'][] = __FUNCTION__;
    $form_state->set('test_handlers', $test_handlers);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $test_handlers = $form_state->get('test_handlers');
    $test_handlers['validate'][] = __FUNCTION__;
    $form_state->set('test_handlers', $test_handlers);
  }

  /**
   * {@inheritdoc}
   */
  public function customSubmitForm(array &$form, FormStateInterface $form_state) {
    $test_handlers = $form_state->get('test_handlers');
    $test_handlers['submit'][] = __FUNCTION__;
    $form_state->set('test_handlers', $test_handlers);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $test_handlers = $form_state->get('test_handlers');
    $test_handlers['submit'][] = __FUNCTION__;
    $form_state->set('test_handlers', $test_handlers);
  }

  /**
   * Tests that default handlers are added even if custom are specified.
   */
  function testDefaultAndCustomHandlers() {
    $form_state = new FormState();
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);

    $handlers = $form_state->get('test_handlers');

    $this->assertIdentical(count($handlers['validate']), 2);
    $this->assertIdentical($handlers['validate'][0], 'customValidateForm');
    $this->assertIdentical($handlers['validate'][1], 'validateForm');

    $this->assertIdentical(count($handlers['submit']), 2);
    $this->assertIdentical($handlers['submit'][0], 'customSubmitForm');
    $this->assertIdentical($handlers['submit'][1], 'submitForm');
  }

}
