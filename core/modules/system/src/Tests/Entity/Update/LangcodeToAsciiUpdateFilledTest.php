<?php

namespace Drupal\system\Tests\Entity\Update;

/**
 * Runs LangcodeToAsciiUpdateTest with a dump filled with content.
 *
 * @group Entity
 */
class LangcodeToAsciiUpdateFilledTest extends LangcodeToAsciiUpdateTest {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.filled.standard.php.gz',
    ];
  }

}
