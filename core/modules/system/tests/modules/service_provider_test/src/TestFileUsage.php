<?php

/**
 * @file
 * Contains \Drupal\service_provider_test\TestFileUsage.
 */

namespace Drupal\service_provider_test;

use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageBase;

class TestFileUsage extends FileUsageBase {

  /**
   * Implements Drupal\file\FileUsage\FileUsageInterface::add().
   */
  public function add(FileInterface $file, $module, $type, $id, $count = 1) {
  }

  /**
   * Implements Drupal\file\FileUsage\FileUsageInterface::delete().
   */
  public function delete(FileInterface $file, $module, $type = NULL, $id = NULL, $count = 1) {
  }

  /**
   * Implements Drupal\file\FileUsage\FileUsageInterface::listUsage().
   */
  public function listUsage(FileInterface $file) {
  }
}
