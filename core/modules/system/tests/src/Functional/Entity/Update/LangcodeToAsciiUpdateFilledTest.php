<?php

namespace Drupal\Tests\system\Functional\Entity\Update;

/**
 * Runs LangcodeToAsciiUpdateTest with a dump filled with content.
 *
 * @group Entity
 * @group legacy
 */
class LangcodeToAsciiUpdateFilledTest extends LangcodeToAsciiUpdateTest {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../fixtures/update/drupal-8.filled.standard.php.gz',
    ];
  }

}
