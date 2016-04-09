<?php

namespace Drupal\update_test\Plugin\Archiver;

use Drupal\Core\Archiver\ArchiverInterface;

/**
 * Defines a test archiver implementation.
 *
 * @Archiver(
 *   id = "update_test_archiver",
 *   title = @Translation("Update Test Archiver"),
 *   extensions = {"update-test-extension"}
 * )
 */
class UpdateTestArchiver implements ArchiverInterface {

  /**
   * {@inheritdoc}
   */
  public function add($file_path) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function remove($path) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function extract($path, array $files = array()) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function listContents() {
    return array();
  }

}
