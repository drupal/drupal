<?php

namespace Drupal\config\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Component\Archiver\ArchiveTar;

class ConfigImportForm implements FormInterface {

  public function getFormID() {
    return 'config_import_form';
  }

  public function buildForm(array $form, array &$form_state) {
    $form['description'] = array(
      '#markup' => '<p>' . t('Use the upload button below.') . '</p>',
    );
    $form['import_tarball'] = array(
      '#type' => 'file',
      '#value' => t('Select your configuration export file'),
      '#description' => t('This form will redirect you to the import configuration screen.'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Upload'),
    );
    return $form;
  }

  public function validateForm(array &$form, array &$form_state) {
    if (!empty($_FILES['files']['error']['import_tarball'])) {
      form_set_error('import_tarball', t('The import tarball could not be uploaded.'));
    }
    else {
      $form_state['values']['import_tarball'] = $_FILES['files']['tmp_name']['import_tarball'];
    }
  }

  public function submitForm(array &$form, array &$form_state) {
    if ($path = $form_state['values']['import_tarball']) {
      \Drupal::service('config.storage.staging')->deleteAll();
      try {
        $archiver = new ArchiveTar($path, 'gz');
        $files = array();
        foreach ($archiver->listContent() as $file) {
          $files[] = $file['filename'];
        }
        $archiver->extractList($files, config_get_config_directory(CONFIG_STAGING_DIRECTORY));
        drupal_set_message('Your configuration files were successfully uploaded, ready for import.');
        $form_state['redirect'] = 'admin/config/development/sync';
      }
      catch (\Exception $e) {
        form_set_error('import_tarball', t('Could not extract the contents of the tar file. The error message is <em>@message</em>', array('@message' => $e->getMessage())));
      }
      drupal_unlink($path);
    }
  }
}

