<?php

namespace Drupal\form_test\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A multistep form for testing the form storage.
 *
 * It uses two steps for editing a virtual "thing". Any changes to it are saved
 * in the form storage and have to be present during any step. By setting the
 * request parameter "cache" the form can be tested with caching enabled, as
 * it would be the case, if the form would contain some #ajax callbacks.
 */
class FormTestStorageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_storage_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($form_state->isRebuilding()) {
      $form_state->setUserInput(array());
    }
    // Initialize
    $storage = $form_state->getStorage();
    if (empty($storage)) {
      $user_input = $form_state->getUserInput();
      if (empty($user_input)) {
        $_SESSION['constructions'] = 0;
      }
      // Put the initial thing into the storage
      $storage = [
        'thing' => [
          'title' => 'none',
          'value' => '',
        ],
      ];
      $form_state->setStorage($storage);
    }
    // Count how often the form is constructed.
    $_SESSION['constructions']++;
    drupal_set_message("Form constructions: " . $_SESSION['constructions']);

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => 'Title',
      '#default_value' => $storage['thing']['title'],
      '#required' => TRUE,
    );
    $form['value'] = array(
      '#type' => 'textfield',
      '#title' => 'Value',
      '#default_value' => $storage['thing']['value'],
      '#element_validate' => array('::elementValidateValueCached'),
    );
    $form['continue_button'] = array(
      '#type' => 'button',
      '#value' => 'Reset',
      // Rebuilds the form without keeping the values.
    );
    $form['continue_submit'] = array(
      '#type' => 'submit',
      '#value' => 'Continue submit',
      '#submit' => array('::continueSubmitForm'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

    // @todo Remove this in https://www.drupal.org/node/2524408, because form
    //   cache immutability is no longer necessary, because we no longer cache
    //   forms during safe HTTP methods. In the meantime, because
    //   Drupal\system\Tests\Form still has test coverage for a poisoned form
    //   cache following a GET request, trick $form_state into caching the form
    //   to keep that test working until we either remove it or change it in
    //   that issue.
    if ($this->getRequest()->get('immutable')) {
      $form_state->addBuildInfo('immutable', TRUE);
      if ($this->getRequest()->get('cache') && $this->getRequest()->isMethodSafe()) {
        $form_state->setRequestMethod('FAKE');
        $form_state->setCached();
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->getRequest()->get('cache')) {
      // Manually activate caching, so we can test that the storage keeps working
      // when it's enabled.
      $form_state->setCached();
    }
  }

  /**
   * Form element validation handler for 'value' element.
   *
   * Tests updating of cached form storage during validation.
   */
  public function elementValidateValueCached($element, FormStateInterface $form_state) {
    // If caching is enabled and we receive a certain value, change the storage.
    // This presumes that another submitted form value triggers a validation error
    // elsewhere in the form. Form API should still update the cached form storage
    // though.
    if ($this->getRequest()->get('cache') && $form_state->getValue('value') == 'change_title') {
      $form_state->set(['thing', 'changed'], TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function continueSubmitForm(array &$form, FormStateInterface $form_state) {
    $form_state->set(['thing', 'title'], $form_state->getValue('title'));
    $form_state->set(['thing', 'value'], $form_state->getValue('value'));
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message("Title: " . Html::escape($form_state->getValue('title')));
    drupal_set_message("Form constructions: " . $_SESSION['constructions']);
    if ($form_state->has(['thing', 'changed'])) {
      drupal_set_message("The thing has been changed.");
    }
    $form_state->setRedirect('<front>');
  }

}
