<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Form\StubForm.
 */

namespace Drupal\system\Tests\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a stub form for testing purposes.
 */
class StubForm extends FormBase {

  /**
   * The form array.
   *
   * @var array
   */
  protected $form;

  /**
   * The form ID.
   *
   * @var string
   */
  protected $formId;

  /**
   * Constructs a StubForm.
   *
   * @param string $form_id
   *   The form ID.
   * @param array $form
   *   The form array.
   */
  public function __construct($form_id, $form) {
    $this->formId = $form_id;
    $this->form = $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $this->formId;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return $this->form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
