<?php

namespace Drupal\config\Form;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\UploadedFilesExtractor;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Defines the configuration import form.
 *
 * @internal
 */
class ConfigImportForm extends FormBase {

  use AutowireTrait;

  public function __construct(
    #[Autowire(service: 'config.storage.sync')]
    protected StorageInterface $configStorage,
    protected FileSystemInterface $fileSystem,
    protected Settings $settings,
    protected UploadedFilesExtractor $uploadedFilesExtractor,
  ) {}

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
    $directory = $this->settings->get('config_sync_directory');
    $directory_is_writable = is_writable($directory);
    if (!$directory_is_writable) {
      $this->messenger()->addError($this->t('The directory %directory is not writable.', ['%directory' => $directory]));
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
    $uploaded_files = $this->uploadedFilesExtractor->extractUploadedFiles('import_tarball');
    if (!empty($uploaded_files)) {
      $file_upload = reset($uploaded_files);
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
          if (str_ends_with($file['filename'], '.yml')) {
            $files[] = $file['filename'];
          }
        }
        $archiver->extractList($files, $this->settings->get('config_sync_directory'), '', FALSE, FALSE);
        foreach ($files as $file) {
          $this->fileSystem->chmod($this->settings->get('config_sync_directory') . DIRECTORY_SEPARATOR . $file);
        }
        $this->messenger()->addStatus($this->t('Your configuration files were successfully uploaded and are ready for import.'));
        $form_state->setRedirect('config.sync');
      }
      catch (\Exception $e) {
        $this->messenger()->addError($this->t('Could not extract the contents of the tar file. The error message is <em>@message</em>', ['@message' => $e->getMessage()]));
      }
      $this->fileSystem->unlink($path);
    }
  }

}
