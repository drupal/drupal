<?php

/**
 * @file
 * Contains \Drupal\form_test\ConfirmFormTestForm.
 */

namespace Drupal\form_test;

use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a test confirmation form.
 */
class ConfirmFormTestForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'form_test_confirm_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('ConfirmFormTestForm::getQuestion().');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('ConfirmFormTestForm::getDescription().');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('ConfirmFormTestForm::getConfirmText().');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('ConfirmFormTestForm::getCancelText().');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, Request $request = NULL) {
    $form['element'] = array('#markup' => '<p>The ConfirmFormTestForm::buildForm() method was used for this form.</p>');

    return parent::buildForm($form, $form_state, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_set_message(t('The ConfirmFormTestForm::submitForm() method was used for this form.'));
    $form_state['redirect'] = '';
  }

}
