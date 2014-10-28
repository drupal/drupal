<?php

/**
 * @file
 * Contains \Drupal\language\Form\NegotiationSessionForm.
 */

namespace Drupal\language\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure the session language negotiation method for this site.
 */
class NegotiationSessionForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_session_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('language.negotiation');
    $form['language_negotiation_session_param'] = array(
      '#title' => $this->t('Request/session parameter'),
      '#type' => 'textfield',
      '#default_value' => $config->get('session.parameter'),
      '#description' => $this->t('Name of the request/session parameter used to determine the desired language.'),
    );

    $form_state->setRedirect('language.negotiation');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('language.negotiation')
      ->set('session.parameter', $form_state->getValue('language_negotiation_session_param'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
