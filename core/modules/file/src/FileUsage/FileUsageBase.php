<?php

namespace Drupal\file\FileUsage;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\file\FileInterface;

/**
 * Defines the base class for database file usage backend.
 */
abstract class FileUsageBase implements FileUsageInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a FileUsageBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   (optional) The config factory. Defaults to NULL and will use
   *   \Drupal::configFactory() instead.
   *
   * @deprecated The $config_factory parameter will become required in Drupal
   *   9.0.0.
   */
  public function __construct(ConfigFactoryInterface $config_factory = NULL) {
    $this->configFactory = $config_factory ?: \Drupal::configFactory();
  }

  /**
   * {@inheritdoc}
   */
  public function add(FileInterface $file, $module, $type, $id, $count = 1) {
    // Make sure that a used file is permanent.
    if (!$file->isPermanent()) {
      $file->setPermanent();
      $file->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(FileInterface $file, $module, $type = NULL, $id = NULL, $count = 1) {
    // Do not actually mark files as temporary when the behavior is disabled.
    if (!$this->configFactory->get('file.settings')->get('make_unused_managed_files_temporary')) {
      return;
    }
    // If there are no more remaining usages of this file, mark it as temporary,
    // which result in a delete through system_cron().
    $usage = \Drupal::service('file.usage')->listUsage($file);
    if (empty($usage)) {
      $file->setTemporary();
      $file->save();
    }
  }

}
