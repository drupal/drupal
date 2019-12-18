<?php

namespace Drupal\config\Form;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the configuration import form.
 *
 * @internal
 */
class ConfigImportForm extends FormBase {

  /**
   * The configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The settings object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs a new ConfigImportForm.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The configuration storage.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings object.
   */
  public function __construct(StorageInterface $config_storage, FileSystemInterface $file_system = NULL, Settings $settings = NULL) {
    $this->configStorage = $config_storage;
    if (!isset($file_system)) {
      @trigger_error('The $file_system parameter was added in Drupal 8.8.0 and will be required in 9.0.0. See https://www.drupal.org/node/3021434.', E_USER_DEPRECATED);
      $file_system = \Drupal::service('file_system');
    }
    $this->fileSystem = $file_system;
    if (!isset($settings)) {
      @trigger_error('The $settings parameter was added in Drupal 8.8.0 and will be required in 9.0.0. See https://www.drupal.org/node/2980712.', E_USER_DEPRECATED);
      $settings = \Drupal::service('settings');
    }
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage.sync'),
      $container->get('file_system'),
      $container->get('settings')
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
        $archiver->extractList($files, $this->settings->get('config_sync_directory'), '', FALSE, FALSE);
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
