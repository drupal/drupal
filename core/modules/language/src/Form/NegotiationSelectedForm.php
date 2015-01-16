<?php

/**
 * @file
 * Contains \Drupal\language\Form\NegotiationSelectedForm.
 */

namespace Drupal\language\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
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
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['language.negotiation'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('language.negotiation');
    $form['selected_langcode'] = array(
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => LanguageInterface::STATE_CONFIGURABLE | LanguageInterface::STATE_SITE_DEFAULT,
      '#default_value' => $config->get('selected_langcode'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('language.negotiation')
      ->set('selected_langcode', $form_state->getValue('selected_langcode'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
