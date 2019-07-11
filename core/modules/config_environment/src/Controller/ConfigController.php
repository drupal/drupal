<?php

namespace Drupal\config_environment\Controller;

use Drupal\config\Controller\ConfigController as OriginalConfigController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for config module routes.
 */
class ConfigController extends OriginalConfigController {

  /**
   * The import transformer service.
   *
   * @var \Drupal\Core\Config\ImportStorageTransformer
   */
  protected $importTransformer;

  /**
   * The sync storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $syncStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = parent::create($container);
    $controller->importTransformer = $container->get('config.import_transformer');
    $controller->syncStorage = $container->get('config.storage.sync');

    return $controller;
  }

  /**
   * {@inheritdoc}
   */
  public function diff($source_name, $target_name = NULL, $collection = NULL) {
    $this->sourceStorage = $this->importTransformer->transform($this->syncStorage);

    return parent::diff($source_name, $target_name, $collection);
  }

}
