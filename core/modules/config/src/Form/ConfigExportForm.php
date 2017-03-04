<?php

namespace Drupal\config\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the configuration export form.
 */
class ConfigExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('config.export_download');
  }

}
