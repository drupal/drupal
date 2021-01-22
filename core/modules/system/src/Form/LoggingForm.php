<?php

namespace Drupal\system\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure logging settings for this site.
 *
 * @internal
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
    $form['error_level'] = [
      '#type' => 'radios',
      '#title' => $this->t('Error messages to display'),
      '#default_value' => $config->get('error_level'),
      '#options' => [
        ERROR_REPORTING_HIDE => $this->t('None'),
        ERROR_REPORTING_DISPLAY_SOME => $this->t('Errors and warnings'),
        ERROR_REPORTING_DISPLAY_ALL => $this->t('All messages'),
        ERROR_REPORTING_DISPLAY_VERBOSE => $this->t('All messages, with backtrace information'),
      ],
      '#description' => $this->t('It is recommended that sites running on production environments do not display any errors.'),
    ];

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
