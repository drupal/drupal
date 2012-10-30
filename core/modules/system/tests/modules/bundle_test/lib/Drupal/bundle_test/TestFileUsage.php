<?php

/**
 * @file
 * Definition of Drupal\bundle_test\TestFileUsage.
 */

namespace Drupal\bundle_test;

use Drupal\file\Plugin\Core\Entity\File;

class TestFileUsage extends FileUsageBase {

  /**
   * Implements Drupal\file\FileUsage\FileUsageInterface::add().
   */
  public function add(File $file, $module, $type, $id, $count = 1) {
  }

  /**
   * Implements Drupal\file\FileUsage\FileUsageInterface::delete().
   */
  public function delete(File $file, $module, $type = NULL, $id = NULL, $count = 1) {
  }

  /**
   * Implements Drupal\file\FileUsage\FileUsageInterface::listUsage().
   */
  public function listUsage(File $file) {
  }
}
