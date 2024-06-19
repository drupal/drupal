<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Functional;

use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests updates MySQL 8 when sql_require_primary_key is on.
 *
 * This acts as a generic test the Drupal supports this setting and does not
 * break during updates.
 *
 * @group mysql
 */
class Mysql8RequirePrimaryKeyUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function runDbTasks() {
    parent::runDbTasks();
    $database = Database::getConnection();
    $is_maria = method_exists($database, 'isMariaDb') && $database->isMariaDb();
    if ($database->databaseType() !== 'mysql' || $is_maria || version_compare($database->version(), '8.0.13', '<')) {
      $this->markTestSkipped('This test only runs on MySQL 8.0.13 and above');
    }

    $database->query("SET sql_require_primary_key = 1;")->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareSettings() {
    parent::prepareSettings();

    // Set sql_require_primary_key for any future connections.
    $settings['databases']['default']['default']['init_commands'] = (object) [
      'value'    => ['sql_require_primary_key' => 'SET sql_require_primary_key = 1;'],
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles[] = __DIR__ . '/../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz';
  }

  /**
   * Tests updates.
   */
  public function testDatabaseLoaded(): void {
    $this->runUpdates();

    // Ensure that after updating a user can be created and do a basic test that
    // the site is available by logging in.
    $this->drupalLogin($this->createUser(admin: TRUE));
    $this->assertSession()->statusCodeEquals(200);
  }

}
