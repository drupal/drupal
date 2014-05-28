<?php

/**
 * @file
 * Contains \Drupal\language\Form\LanguageEditForm.
 */

namespace Drupal\language\Form;

use Drupal\language\Form\LanguageFormBase;

/**
 * Controller for language edit forms.
 */
class LanguageEditForm extends LanguageFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // @todo Remove in favour of base method.
    return 'language_admin_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $this->commonForm($form);
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, array &$form_state) {
    $actions['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save language'),
      '#validate' => array(array($this, 'validateCommon')),
      '#submit' => array(array($this, 'submitForm')),
    );
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Prepare a language object for saving.
    $languages = language_list();
    $langcode = $form_state['values']['langcode'];
    $language = $languages[$langcode];
    $language->name = $form_state['values']['name'];
    $language->direction = $form_state['values']['direction'];
    language_save($language);

    $form_state['redirect_route']['route_name'] = 'language.admin_overview';
  }

}
