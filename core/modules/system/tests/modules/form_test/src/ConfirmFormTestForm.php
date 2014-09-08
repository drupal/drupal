<?php

/**
 * @file
 * Contains \Drupal\form_test\ConfirmFormTestForm.
 */

namespace Drupal\form_test;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a test confirmation form.
 */
class ConfirmFormTestForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_confirm_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('ConfirmFormTestForm::getQuestion().');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('form_test.route8');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('ConfirmFormTestForm::getDescription().');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('ConfirmFormTestForm::getConfirmText().');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('ConfirmFormTestForm::getCancelText().');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['element'] = array('#markup' => '<p>The ConfirmFormTestForm::buildForm() method was used for this form.</p>');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The ConfirmFormTestForm::submitForm() method was used for this form.'));
    $form_state->setRedirect('<front>');
  }

}
