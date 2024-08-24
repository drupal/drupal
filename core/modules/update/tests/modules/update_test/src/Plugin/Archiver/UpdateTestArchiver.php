<?php

declare(strict_types=1);

namespace Drupal\update_test\Plugin\Archiver;

use Drupal\Core\Archiver\ArchiverInterface;
use Drupal\Core\Archiver\Attribute\Archiver;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a test archiver implementation.
 */
#[Archiver(
  id: 'update_test_archiver',
  title: new TranslatableMarkup('Tar'),
  description: new TranslatableMarkup('Update Test Archiver'),
  extensions: ['update-test-extension']
)]
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
  public function extract($path, array $files = []) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function listContents() {
    return [];
  }

}
