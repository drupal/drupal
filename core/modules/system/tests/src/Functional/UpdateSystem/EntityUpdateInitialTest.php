<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests handling of existing initial keys during updates.
 *
 * @see https://www.drupal.org/project/drupal/issues/2925550
 *
 * @group Update
 */
class EntityUpdateInitialTest extends BrowserTestBase {
  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test_update'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->ensureUpdatesToRun();
    $connection = Database::getConnection();

    // Simulate an entity type that had previously set an initial key schema for
    // a field.
    $schema = $connection->select('key_value')
      ->fields('key_value', ['value'])
      ->condition('collection', 'entity.storage_schema.sql')
      ->condition('name', 'entity_test_update.field_schema_data.name')
      ->execute()
      ->fetchField();

    $schema = unserialize($schema);
    $schema['entity_test_update']['fields']['name']['initial'] = 'test';

    $connection->update('key_value')
      ->fields(['value' => serialize($schema)])
      ->condition('collection', 'entity.storage_schema.sql')
      ->condition('name', 'entity_test_update.field_schema_data.name')
      ->execute();
  }

  /**
   * Tests that a pre-existing initial key in the field schema is not a change.
   */
  public function testInitialIsIgnored() {
    $this->runUpdates();
  }

}
