<?php

/**
 * @file
 * Contains \Drupal\language\Form\LanguageAddForm.
 */

namespace Drupal\language\Form;

use Drupal\language\Form\LanguageFormBase;
use Drupal\Core\Language\Language;

/**
 * Controller for language addition forms.
 */
class LanguageAddForm extends LanguageFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // @todo Remove in favour of base method.
    return 'language_admin_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form['#title'] = $this->t('Add language');

    $predefined_languages = $this->languageManager->getStandardLanguageListWithoutConfigured();

    $predefined_languages['custom'] = $this->t('Custom language...');
    $predefined_default = !empty($form_state['values']['predefined_langcode']) ? $form_state['values']['predefined_langcode'] : key($predefined_languages);
    $form['predefined_langcode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Language name'),
      '#default_value' => $predefined_default,
      '#options' => $predefined_languages,
    );
    $form['predefined_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add language'),
      '#limit_validation_errors' => array(array('predefined_langcode'), array('predefined_submit')),
      '#states' => array(
        'invisible' => array(
          'select#edit-predefined-langcode' => array('value' => 'custom'),
        ),
      ),
      '#validate' => array(array($this, 'validatePredefined')),
      '#submit' => array(array($this, 'submitForm')),
    );

    $custom_language_states_conditions = array(
      'select#edit-predefined-langcode' => array('value' => 'custom'),
    );
    $form['custom_language'] = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => $custom_language_states_conditions,
      ),
    );
    $this->commonForm($form['custom_language']);
    $form['custom_language']['langcode']['#states'] = array(
      'required' => $custom_language_states_conditions,
    );
    $form['custom_language']['name']['#states'] = array(
      'required' => $custom_language_states_conditions,
    );
    $form['custom_language']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add custom language'),
      '#validate' => array(array($this, 'validateCustom')),
      '#submit' => array(array($this, 'submitForm')),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $langcode = $form_state['values']['predefined_langcode'];
    if ($langcode == 'custom') {
      $langcode = $form_state['values']['langcode'];
      // Custom language form.
      $language = new Language(array(
        'id' => $langcode,
        'name' => $form_state['values']['name'],
        'direction' => $form_state['values']['direction'],
      ));
    }
    else {
      $language = new Language(array('id' => $langcode));
    }
    // Save the language and inform the user that it happened.
    $language = language_save($language);

    drupal_set_message($this->t('The language %language has been created and can now be used.', array('%language' => $language->name)));

    // Tell the user they have the option to add a language switcher block
    // to their theme so they can switch between the languages.
    drupal_set_message($this->t('Use one of the language switcher blocks to allow site visitors to switch between languages. You can enable these blocks on the <a href="@block-admin">block administration page</a>.', array('@block-admin' => url('admin/structure/block'))));
    $form_state['redirect_route']['route_name'] = 'language.admin_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, array &$form_state) {
    // No actions needed.
    return array();
  }

  /**
   * Validates the language addition form on custom language button.
   */
  public function validateCustom(array $form, array &$form_state) {
    if ($form_state['values']['predefined_langcode'] == 'custom') {
      $langcode = $form_state['values']['langcode'];
      // Reuse the editing form validation routine if we add a custom language.
      $this->validateCommon($form['custom_language'], $form_state);

      if ($language = language_load($langcode)) {
        $this->setFormError('langcode', $form_state, $this->t('The language %language (%langcode) already exists.', array('%language' => $language->name, '%langcode' => $langcode)));
      }
    }
    else {
      $this->setFormError('predefined_langcode', $form_state, $this->t('Use the <em>Add language</em> button to save a predefined language.'));
    }
  }

  /**
   * Element specific validator for the Add language button.
   */
  public function validatePredefined($form, &$form_state) {
    $langcode = $form_state['values']['predefined_langcode'];
    if ($langcode == 'custom') {
      $this->setFormError('predefined_langcode', $form_state, $this->t('Fill in the language details and save the language with <em>Add custom language</em>.'));
    }
    else {
      if ($language = language_load($langcode)) {
        $this->setFormError('predefined_langcode', $form_state, $this->t('The language %language (%langcode) already exists.', array('%language' => $language->name, '%langcode' => $langcode)));
      }
    }
  }

}
