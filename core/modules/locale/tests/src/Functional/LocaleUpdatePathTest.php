<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests Locale's update path.
 *
 * @group locale
 */
class LocaleUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      dirname(__DIR__, 4) . '/system/tests/fixtures/update/drupal-8.8.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests Locale's update path.
   */
  public function testUpdatePath() {
    $schema = Database::getConnection()->schema();
    $this->assertTrue($schema->indexExists('locales_location', 'string_id'));
    $this->assertTrue($schema->indexExists('locales_location', 'string_type'));

    $this->runUpdates();

    $this->assertFalse($schema->indexExists('locales_location', 'string_id'));
    $this->assertTrue($schema->indexExists('locales_location', 'string_type'));
  }

}
