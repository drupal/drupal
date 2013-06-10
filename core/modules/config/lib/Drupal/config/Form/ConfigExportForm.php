<?php

namespace Drupal\config\Form;

use Drupal\Core\Form\FormInterface;

class ConfigExportForm implements FormInterface {

  public function getFormID() {
    return 'config_export_form';
  }

  public function buildForm(array $form, array &$form_state) {
    $form['#action'] = '/admin/config/development/export-download';
    $form['description'] = array(
      '#markup' => '<p>' . t('Use the export button below to download your site configuration.') . '</p>',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Export'),
    );
    return $form;
  }

  public function validateForm(array &$form, array &$form_state) {
  }

  public function submitForm(array &$form, array &$form_state) {
  }
}

