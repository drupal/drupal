<?php

namespace Drupal\service_provider_test;

use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageBase;

class TestFileUsage extends FileUsageBase {

  /**
   * {@inheritdoc}
   */
  public function add(FileInterface $file, $module, $type, $id, $count = 1) {
  }

  /**
   * {@inheritdoc}
   */
  public function delete(FileInterface $file, $module, $type = NULL, $id = NULL, $count = 1) {
  }

  /**
   * {@inheritdoc}
   */
  public function listUsage(FileInterface $file) {
  }
}
