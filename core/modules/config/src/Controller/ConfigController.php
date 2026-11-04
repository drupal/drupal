<?php

namespace Drupal\config\Controller;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Diff\DiffFormatter;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\system\FileDownloadController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Returns responses for config module routes.
 */
class ConfigController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage'),
      $container->get('config.storage.sync'),
      $container->get('config.manager'),
      FileDownloadController::create($container),
      $container->get('diff.formatter'),
      $container->get('file_system'),
      $container->get('config.storage.export'),
      $container->get('config.import_transformer')
    );
  }

  /**
   * Constructs a ConfigController object.
   *
   * @param \Drupal\Core\Config\StorageInterface $targetStorage
   *   The target storage.
   * @param \Drupal\Core\Config\StorageInterface $syncStorage
   *   The sync storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The config manager.
   * @param \Drupal\system\FileDownloadController $fileDownloadController
   *   The file download controller.
   * @param \Drupal\Core\Diff\DiffFormatter $diffFormatter
   *   The diff formatter.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\Core\Config\StorageInterface $exportStorage
   *   The export storage.
   * @param \Drupal\Core\Config\ImportStorageTransformer $importTransformer
   *   The import transformer service.
   */
  public function __construct(
    protected StorageInterface $targetStorage,
    protected StorageInterface $syncStorage,
    protected ConfigManagerInterface $configManager,
    protected FileDownloadController $fileDownloadController,
    protected DiffFormatter $diffFormatter,
    protected FileSystemInterface $fileSystem,
    protected StorageInterface $exportStorage,
    protected ImportStorageTransformer $importTransformer,
  ) {
  }

  /**
   * Downloads a tarball of the site configuration.
   */
  public function downloadExport() {
    try {
      $this->fileSystem->delete($this->fileSystem->getTempDirectory() . '/config.tar.gz');
    }
    catch (FileException) {
      // Ignore failed deletes.
    }

    $archiver = new ArchiveTar($this->fileSystem->getTempDirectory() . '/config.tar.gz', 'gz');
    // Add all contents of the export storage to the archive.
    foreach ($this->exportStorage->listAll() as $name) {
      $archiver->addString("$name.yml", Yaml::encode($this->exportStorage->read($name)));
    }
    // Get all  data from the remaining collections.
    foreach ($this->exportStorage->getAllCollectionNames() as $collection) {
      $collection_storage = $this->exportStorage->createCollection($collection);
      foreach ($collection_storage->listAll() as $name) {
        $archiver->addString(str_replace('.', '/', $collection) . "/$name.yml", Yaml::encode($collection_storage->read($name)));
      }
    }

    $request = new Request(['file' => 'config.tar.gz']);
    return $this->fileDownloadController->download($request, 'temporary');
  }

  /**
   * Shows diff of specified configuration file.
   *
   * @param string $source_name
   *   The name of the configuration file.
   * @param string $target_name
   *   (optional) The name of the target configuration file if different from
   *   the $source_name.
   * @param string $collection
   *   (optional) The configuration collection name. Defaults to the default
   *   collection.
   *
   * @return array
   *   Table showing a two-way diff between the active and staged configuration.
   */
  public function diff($source_name, $target_name = NULL, $collection = NULL) {
    if (!isset($collection)) {
      $collection = StorageInterface::DEFAULT_COLLECTION;
    }
    $syncStorage = $this->importTransformer->transform($this->syncStorage);
    $diff = $this->configManager->diff($this->targetStorage, $syncStorage, $source_name, $target_name, $collection);
    $this->diffFormatter->show_header = FALSE;

    $build = [];

    $build['#title'] = $this->t('View changes of @config_file', ['@config_file' => $source_name]);
    // Add the CSS for the inline diff.
    $build['#attached']['library'][] = 'system/diff';

    $build['diff'] = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['diff'],
      ],
      '#header' => [
        ['data' => $this->t('Active'), 'colspan' => '2'],
        ['data' => $this->t('Staged'), 'colspan' => '2'],
      ],
      '#rows' => $this->diffFormatter->format($diff),
    ];

    $build['back'] = [
      '#type' => 'link',
      '#attributes' => [
        'class' => [
          'dialog-cancel',
        ],
      ],
      '#title' => "Back to 'Synchronize configuration' page.",
      '#url' => Url::fromRoute('config.sync'),
    ];

    return $build;
  }

}
