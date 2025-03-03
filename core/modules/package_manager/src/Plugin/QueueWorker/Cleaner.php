<?php

namespace Drupal\package_manager\Plugin\QueueWorker;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes a queue of defunct stage directories, deleting them.
 */
#[QueueWorker(
  id: 'package_manager_cleanup',
  title: new TranslatableMarkup('Stage directory cleaner'),
  cron: ['time' => 30],
)]
final class Cleaner extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(array $configuration, string $plugin_id, mixed $plugin_definition, private readonly FileSystemInterface $fileSystem) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(FileSystemInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($dir): void {
    assert(is_string($dir));

    if (file_exists($dir)) {
      $this->fileSystem->deleteRecursive($dir, function (string $path): void {
        $this->fileSystem->chmod($path, is_dir($path) ? 0700 : 0600);
      });
    }
  }

}
