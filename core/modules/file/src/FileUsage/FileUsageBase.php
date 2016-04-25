<?php

namespace Drupal\file\FileUsage;

use Drupal\file\FileInterface;

/**
 * Defines the base class for database file usage backend.
 */
abstract class FileUsageBase implements FileUsageInterface {

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
    // If there are no more remaining usages of this file, mark it as temporary,
    // which result in a delete through system_cron().
    $usage = \Drupal::service('file.usage')->listUsage($file);
    if (empty($usage)) {
      $file->setTemporary();
      $file->save();
    }
  }
}
