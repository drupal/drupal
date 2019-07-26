<?php

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure file system settings for this site.
 *
 * @internal
 */
class FileSystemForm extends ConfigFormBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a FileSystemForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatterInterface $date_formatter, StreamWrapperManagerInterface $stream_wrapper_manager, FileSystemInterface $file_system = NULL) {
    parent::__construct($config_factory);
    $this->dateFormatter = $date_formatter;
    $this->streamWrapperManager = $stream_wrapper_manager;
    if (!$file_system) {
      @trigger_error('Calling FileSystemForm::__construct() without the $file_system argument is deprecated in drupal:8.8.0. The $file_system argument will be required in drupal:9.0.0. See https://www.drupal.org/node/3039255', E_USER_DEPRECATED);
      $file_system = \Drupal::service('file_system');
    }
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('stream_wrapper_manager'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_file_system_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system.file'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('system.file');
    $form['file_public_path'] = [
      '#type' => 'item',
      '#title' => t('Public file system path'),
      '#markup' => PublicStream::basePath(),
      '#description' => t('A local file system path where public files will be stored. This directory must exist and be writable by Drupal. This directory must be relative to the Drupal installation directory and be accessible over the web. This must be changed in settings.php'),
    ];

    $form['file_public_base_url'] = [
      '#type' => 'item',
      '#title' => t('Public file base URL'),
      '#markup' => PublicStream::baseUrl(),
      '#description' => t('The base URL that will be used for public file URLs. This can be changed in settings.php'),
    ];

    $form['file_private_path'] = [
      '#type' => 'item',
      '#title' => t('Private file system path'),
      '#markup' => (PrivateStream::basePath() ? PrivateStream::basePath() : t('Not set')),
      '#description' => t('An existing local file system path for storing private files. It should be writable by Drupal and not accessible over the web. This must be changed in settings.php'),
    ];

    $form['file_temporary_path'] = [
      '#type' => 'item',
      '#title' => t('Temporary directory'),
      '#markup' => $this->fileSystem->getTempDirectory(),
      '#description' => t('A local file system path where temporary files will be stored. This directory should not be accessible over the web. This must be changed in settings.php.'),
    ];
    // Any visible, writeable wrapper can potentially be used for the files
    // directory, including a remote file system that integrates with a CDN.
    $options = $this->streamWrapperManager->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE);

    if (!empty($options)) {
      $form['file_default_scheme'] = [
        '#type' => 'radios',
        '#title' => t('Default download method'),
        '#default_value' => $config->get('default_scheme'),
        '#options' => $options,
        '#description' => t('This setting is used as the preferred download method. The use of public files is more efficient, but does not provide any access control.'),
      ];
    }

    $intervals = [0, 21600, 43200, 86400, 604800, 2419200, 7776000];
    $period = array_combine($intervals, array_map([$this->dateFormatter, 'formatInterval'], $intervals));
    $period[0] = t('Never');
    $form['temporary_maximum_age'] = [
      '#type' => 'select',
      '#title' => t('Delete temporary files after'),
      '#default_value' => $config->get('temporary_maximum_age'),
      '#options' => $period,
      '#description' => t('Temporary files are not referenced, but are in the file system and therefore may show up in administrative lists. <strong>Warning:</strong> If enabled, temporary files will be permanently deleted and may not be recoverable.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('system.file')
      ->set('temporary_maximum_age', $form_state->getValue('temporary_maximum_age'));

    if ($form_state->hasValue('file_default_scheme')) {
      $config->set('default_scheme', $form_state->getValue('file_default_scheme'));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
