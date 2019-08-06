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
   *   The config factory. This parameter is required as of drupal:8.4.0 and
   *   trigger a fatal error if not passed in drupal:9.0.0.
   *
   * @todo Update the docblock and make $config_factory required in
   *   https://www.drupal.org/project/drupal/issues/3070114 when the
   *   drupal:9.0.x branch is opened.
   */
  public function __construct(ConfigFactoryInterface $config_factory = NULL) {
    // @todo Remove below conditional when the drupal:9.0.x branch is opened.
    // @see https://www.drupal.org/project/drupal/issues/3070114
    if (empty($config_factory)) {
      @trigger_error('Not passing the $config_factory parameter to ' . __METHOD__ . ' is deprecated in drupal:8.4.0 and will trigger a fatal error in drupal:9.0.0. See https://www.drupal.org/project/drupal/issues/2801777', E_USER_DEPRECATED);
      $config_factory = \Drupal::configFactory();
    }

    $this->configFactory = $config_factory;
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
