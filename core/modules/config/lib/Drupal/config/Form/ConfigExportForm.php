<?php

/**
 * @file
 * Contains \Drupal\config\Form\ConfigExportForm.
 */

namespace Drupal\config\Form;

use Drupal\Core\Form\FormInterface;

/**
 * Defines the configuration export form.
 */
class ConfigExportForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'config_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['description'] = array(
      '#markup' => '<p>' . t('Use the export button below to download your site configuration.') . '</p>',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Export'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['redirect'] = 'admin/config/development/export-download';
  }

}
