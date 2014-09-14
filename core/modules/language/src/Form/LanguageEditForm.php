<?php

/**
 * @file
 * Contains \Drupal\language\Form\LanguageEditForm.
 */

namespace Drupal\language\Form;

use Drupal\Core\Form\FormStateInterface;
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
  public function form(array $form, FormStateInterface $form_state) {
    $this->commonForm($form);
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save language'),
      '#validate' => array('::validateCommon'),
      '#submit' => array('::submitForm', '::save'),
    );
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirect('language.admin_overview');
    $this->logger('language')->notice('The %language (%langcode) language has been updated.', array('%language' => $this->entity->label(), '%langcode' => $this->entity->id()));
  }

}
