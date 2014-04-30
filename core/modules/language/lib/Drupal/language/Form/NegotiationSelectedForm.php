<?php

/**
 * @file
 * Contains \Drupal\language\Form\NegotiationSelectedForm.
 */

namespace Drupal\language\Form;

use Drupal\Core\Language\Language;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure the selected language negotiation method for this site.
 */
class NegotiationSelectedForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_selected_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->config('language.negotiation');
    $form['selected_langcode'] = array(
      '#type' => 'language_select',
      '#title' => t('Language'),
      '#languages' => Language::STATE_CONFIGURABLE | Language::STATE_SITE_DEFAULT,
      '#default_value' => $config->get('selected_langcode'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->config('language.negotiation')
      ->set('selected_langcode', $form_state['values']['selected_langcode'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
