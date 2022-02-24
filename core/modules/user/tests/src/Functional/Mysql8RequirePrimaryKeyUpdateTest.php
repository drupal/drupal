<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests user_update_9301() on MySQL 8 when sql_require_primary_key is on.
 *
 * @group user
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
    $this->databaseDumpFiles[] = __DIR__ . '/../../../../system/tests/fixtures/update/drupal-9.0.0.bare.standard.php.gz';
  }

  /**
   * Tests user_update_9301().
   */
  public function testDatabaseLoaded() {
    $key_value_store = \Drupal::keyValue('entity.storage_schema.sql');
    $id_schema = $key_value_store->get('user.field_schema_data.uid', []);
    $this->assertSame('int', $id_schema['users']['fields']['uid']['type']);

    $this->runUpdates();

    $key_value_store = \Drupal::keyValue('entity.storage_schema.sql');
    $id_schema = $key_value_store->get('user.field_schema_data.uid', []);
    $this->assertSame('serial', $id_schema['users']['fields']['uid']['type']);
  }

}
