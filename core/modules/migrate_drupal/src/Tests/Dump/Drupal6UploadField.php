<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6Upload.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

class Drupal6UploadField extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->setModuleVersion('upload', 6000);
  }

}
