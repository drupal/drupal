<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests update of update.settings:fetch.url if it's still the default of "".
 *
 * @group system
 * @covers \update_post_update_set_blank_fetch_url_to_null
 */
class UpdateSettingsDefaultFetchUrlUpdateTest extends UpdatePathTestBase {

  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $connection = Database::getConnection();

    // Set the schema version.
    $connection->merge('key_value')
      ->fields([
        'value' => 'i:8001;',
        'name' => 'update',
        'collection' => 'system.schema',
      ])
      ->condition('collection', 'system.schema')
      ->condition('name', 'update')
      ->execute();

    // Update core.extension.
    $extensions = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();
    $extensions = unserialize($extensions);
    $extensions['module']['update'] = 0;
    $connection->update('config')
      ->fields(['data' => serialize($extensions)])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();

    // Create update.settings config.
    $default_update_settings = [
      'check' => [
        'disabled_extensions' => FALSE,
        'interval_days' => 1,
      ],
      'fetch' => [
        'url' => '',
        'max_attempts' => 2,
        'timeout' => 30,
      ],
      'notification' => [
        'emails' => [],
        'threshold' => 'all',
      ],
    ];
    $connection->insert('config')
      ->fields([
        'collection',
        'name',
        'data',
      ])
      ->values([
        'collection' => '',
        'name' => 'update.settings',
        'data' => serialize($default_update_settings),
      ])
      ->execute();
  }

  /**
   * Tests update of update.settings:fetch.url.
   */
  public function testUpdate(): void {
    $fetch_url_before = $this->config('update.settings')->get('fetch.url');
    $this->assertSame('', $fetch_url_before);

    $this->runUpdates();

    $fetch_url_after = $this->config('update.settings')->get('fetch.url');
    $this->assertNull($fetch_url_after);
  }

}
