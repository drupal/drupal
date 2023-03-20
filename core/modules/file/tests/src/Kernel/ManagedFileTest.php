<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;

/**
 * Managed file element test.
 *
 * @group file
 *
 * @see \Drupal\file\Element\ManagedFile
 */
class ManagedFileTest extends FileManagedUnitTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_managed_file';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['managed_file'] = [
      '#type' => 'managed_file',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Tests that managed file elements can be programmatically submitted.
   */
  public function testManagedFileElement() {
    $form_state = new FormState();
    $values['managed_file'] = NULL;
    $form_state->setValues($values);
    $this->container->get('form_builder')->submitForm($this, $form_state);
    // Should submit without any errors.
    $this->assertEquals(0, count($form_state->getErrors()));
  }

}
