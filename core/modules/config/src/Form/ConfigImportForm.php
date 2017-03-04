<?php

namespace Drupal\config\Form;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the configuration import form.
 */
class ConfigImportForm extends FormBase {

  /**
   * The configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Constructs a new ConfigImportForm.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The configuration storage.
   */
  public function __construct(StorageInterface $config_storage) {
    $this->configStorage = $config_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage.sync')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
    $directory_is_writable = is_writable($directory);
    if (!$directory_is_writable) {
      drupal_set_message($this->t('The directory %directory is not writable.', ['%directory' => $directory]), 'error');
    }
    $form['import_tarball'] = [
      '#type' => 'file',
      '#title' => $this->t('Configuration archive'),
      '#description' => $this->t('Allowed types: @extensions.', ['@extensions' => 'tar.gz tgz tar.bz2']),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#disabled' => !$directory_is_writable,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $all_files = $this->getRequest()->files->get('files', []);
    if (!empty($all_files['import_tarball'])) {
      $file_upload = $all_files['import_tarball'];
      if ($file_upload->isValid()) {
        $form_state->setValue('import_tarball', $file_upload->getRealPath());
        return;
      }
    }

    $form_state->setErrorByName('import_tarball', $this->t('The file could not be uploaded.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($path = $form_state->getValue('import_tarball')) {
      $this->configStorage->deleteAll();
      try {
        $archiver = new ArchiveTar($path, 'gz');
        $files = [];
        foreach ($archiver->listContent() as $file) {
          $files[] = $file['filename'];
        }
        $archiver->extractList($files, config_get_config_directory(CONFIG_SYNC_DIRECTORY));
        drupal_set_message($this->t('Your configuration files were successfully uploaded and are ready for import.'));
        $form_state->setRedirect('config.sync');
      }
      catch (\Exception $e) {
        drupal_set_message($this->t('Could not extract the contents of the tar file. The error message is <em>@message</em>', ['@message' => $e->getMessage()]), 'error');
      }
      drupal_unlink($path);
    }
  }

}
