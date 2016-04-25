<?php

namespace Drupal\system\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure logging settings for this site.
 */
class LoggingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_logging_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system.logging'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('system.logging');
    $form['error_level'] = array(
      '#type' => 'radios',
      '#title' => t('Error messages to display'),
      '#default_value' => $config->get('error_level'),
      '#options' => array(
        ERROR_REPORTING_HIDE => t('None'),
        ERROR_REPORTING_DISPLAY_SOME => t('Errors and warnings'),
        ERROR_REPORTING_DISPLAY_ALL => t('All messages'),
        ERROR_REPORTING_DISPLAY_VERBOSE => t('All messages, with backtrace information'),
      ),
      '#description' => t('It is recommended that sites running on production environments do not display any errors.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('system.logging')
      ->set('error_level', $form_state->getValue('error_level'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
