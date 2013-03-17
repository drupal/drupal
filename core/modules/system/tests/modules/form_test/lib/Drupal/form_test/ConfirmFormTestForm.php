<?php

/**
 * @file
 * Contains \Drupal\form_test\ConfirmFormTestForm.
 */

namespace Drupal\form_test;

use Drupal\Core\Form\ConfirmFormBase;

/**
 * Provides a test confirmation form.
 */
class ConfirmFormTestForm extends ConfirmFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'form_test_confirm_test_form';
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getQuestion().
   */
  protected function getQuestion() {
    return t('ConfirmFormTestForm::getQuestion().');
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getCancelPath().
   */
  protected function getCancelPath() {
    return 'admin';
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::getDescription().
   */
  protected function getDescription() {
    return t('ConfirmFormTestForm::getDescription().');
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::getConfirmText().
   */
  protected function getConfirmText() {
    return t('ConfirmFormTestForm::getConfirmText().');
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::getCancelText().
   */
  protected function getCancelText() {
    return t('ConfirmFormTestForm::getCancelText().');
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $form['element'] = array('#markup' => '<p>The ConfirmFormTestForm::buildForm() method was used for this form.</p>');

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_set_message(t('The ConfirmFormTestForm::submitForm() method was used for this form.'));
    $form_state['redirect'] = '';
  }

}
