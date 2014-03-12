<?php

/**
 * @file
 * Contains \Drupal\config\Controller\ConfigController
 */

namespace Drupal\config\Controller;

use Drupal\Component\Archiver\ArchiveTar;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\system\FileDownloadController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for config module routes.
 */
class ConfigController implements ContainerInjectionInterface {

  /**
   * The target storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $targetStorage;

  /**
   * The source storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sourceStorage;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The file download controller.
   *
   * @var \Drupal\system\FileDownloadController
   */
  protected $fileDownloadController;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage'),
      $container->get('config.storage.staging'),
      $container->get('config.manager'),
      new FileDownloadController()
    );
  }

  /**
   * Constructs a ConfigController object.
   *
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The target storage.
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The source storage
   * @param \Drupal\system\FileDownloadController $file_download_controller
   *   The file download controller.
   */
  public function __construct(StorageInterface $target_storage, StorageInterface $source_storage, ConfigManagerInterface $config_manager, FileDownloadController $file_download_controller) {
    $this->targetStorage = $target_storage;
    $this->sourceStorage = $source_storage;
    $this->configManager = $config_manager;
    $this->fileDownloadController = $file_download_controller;
  }

  /**
   * Downloads a tarball of the site configuration.
   */
  public function downloadExport() {
    $archiver = new ArchiveTar(file_directory_temp() . '/config.tar.gz', 'gz');
    $config_dir = config_get_config_directory();
    $config_files = array();
    foreach (\Drupal::service('config.storage')->listAll() as $config_name) {
      $config_files[] = $config_dir . '/' . $config_name . '.yml';
    }
    $archiver->createModify($config_files, '', config_get_config_directory());

    $request = new Request(array('file' => 'config.tar.gz'));
    return $this->fileDownloadController->download($request, 'temporary');
  }

  /**
   * Shows diff of specificed configuration file.
   *
   * @param string $config_file
   *   The name of the configuration file.
   *
   * @return string
   *   Table showing a two-way diff between the active and staged configuration.
   */
  public function diff($config_file) {

    $diff = $this->configManager->diff($this->targetStorage, $this->sourceStorage, $config_file);
    $formatter = new \DrupalDiffFormatter();
    $formatter->show_header = FALSE;

    $build = array();

    $build['#title'] = t('View changes of @config_file', array('@config_file' => $config_file));
    // Add the CSS for the inline diff.
    $build['#attached']['css'][] = drupal_get_path('module', 'system') . '/css/system.diff.css';

    $build['diff'] = array(
      '#type' => 'table',
      '#header' => array(
        array('data' => t('Old'), 'colspan' => '2'),
        array('data' => t('New'), 'colspan' => '2'),
      ),
      '#rows' => $formatter->format($diff),
    );

    $build['back'] = array(
      '#type' => 'link',
      '#attributes' => array(
        'class' => array(
          'dialog-cancel',
        ),
      ),
      '#title' => "Back to 'Synchronize configuration' page.",
      '#href' => 'admin/config/development/configuration',
    );

    return $build;
  }
}
