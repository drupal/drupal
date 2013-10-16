<?php

/**
 * @file
 * Contains \Drupal\system\Form\FileSystemForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure file system settings for this site.
 */
class FileSystemForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_file_system_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('system.file');
    $form['file_public_path'] = array(
      '#type' => 'item',
      '#title' => t('Public file system path'),
      '#default_value' => PublicStream::basePath(),
      '#markup' => PublicStream::basePath(),
      '#description' => t('A local file system path where public files will be stored. This directory must exist and be writable by Drupal. This directory must be relative to the Drupal installation directory and be accessible over the web. This must be changed in settings.php'),
    );

    $form['file_private_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Private file system path'),
      '#default_value' => $config->get('path.private'),
      '#maxlength' => 255,
      '#description' => t('An existing local file system path for storing private files. It should be writable by Drupal and not accessible over the web. See the online handbook for <a href="@handbook">more information about securing private files</a>.', array('@handbook' => 'http://drupal.org/documentation/modules/file')),
      '#after_build' => array('system_check_directory'),
    );

    $form['file_temporary_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Temporary directory'),
      '#default_value' => $config->get('path.temporary'),
      '#maxlength' => 255,
      '#description' => t('A local file system path where temporary files will be stored. This directory should not be accessible over the web.'),
      '#after_build' => array('system_check_directory'),
    );
    // Any visible, writeable wrapper can potentially be used for the files
    // directory, including a remote file system that integrates with a CDN.
    foreach (file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE) as $scheme => $info) {
      $options[$scheme] = check_plain($info['description']);
    }

    if (!empty($options)) {
      $form['file_default_scheme'] = array(
        '#type' => 'radios',
        '#title' => t('Default download method'),
        '#default_value' => $config->get('default_scheme'),
        '#options' => $options,
        '#description' => t('This setting is used as the preferred download method. The use of public files is more efficient, but does not provide any access control.'),
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->configFactory->get('system.file')
      ->set('path.private', $form_state['values']['file_private_path'])
      ->set('path.temporary', $form_state['values']['file_temporary_path']);

    if (isset($form_state['values']['file_default_scheme'])) {
      $config->set('default_scheme', $form_state['values']['file_default_scheme']);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
