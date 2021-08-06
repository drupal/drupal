<?php

namespace Drupal\Tests\user\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

// cSpell:ignore refobjid regclass attname attrelid attnum refobjsubid objid
// cSpell:ignore classid

/**
 * Tests user_update_9301().
 *
 * @group user
 */
class UidUpdateToSerialTest extends UpdatePathTestBase {

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

    $connection = \Drupal::database();
    if ($connection->driver() == 'pgsql') {
      $seq_name = $connection->makeSequenceName('users', 'uid');
      $seq_owner = $connection->query("SELECT d.refobjid::regclass as table_name, a.attname as field_name
        FROM pg_depend d
        JOIN pg_attribute a ON a.attrelid = d.refobjid AND a.attnum = d.refobjsubid
        WHERE d.objid = :seq_name::regclass
        AND d.refobjsubid > 0
        AND d.classid = 'pg_class'::regclass", [':seq_name' => 'public.' . $seq_name])->fetchObject();
      $this->assertEquals($connection->tablePrefix('users') . 'users', $seq_owner->table_name);
      $this->assertEquals('uid', $seq_owner->field_name);

      $seq_last_value = $connection->query("SELECT last_value FROM $seq_name")->fetchField();
      $maximum_uid = $connection->query('SELECT MAX([uid]) FROM {users}')->fetchField();
      $this->assertEquals($maximum_uid + 1, $seq_last_value);
    }
  }

}
