<?php

/**
 * @file
 * Contains \Drupal\config\Form\ConfigExportForm.
 */

namespace Drupal\config\Form;

use Drupal\Core\Form\FormBase;

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
  public function buildForm(array $form, array &$form_state) {
    $form['description'] = array(
      '#markup' => '<p>' . $this->t('Use the export button below to download your site configuration.') . '</p>',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['redirect_route']['route_name'] = 'config.export_download';
  }

}
