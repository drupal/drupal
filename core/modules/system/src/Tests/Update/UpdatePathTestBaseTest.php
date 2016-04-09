<?php

namespace Drupal\system\Tests\Update;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests the update path base class.
 *
 * @group Update
 */
class UpdatePathTestBaseTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_test_schema'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.update-test-schema-enabled.php',
    ];
  }

  /**
   * Tests that the database was properly loaded.
   */
  public function testDatabaseLoaded() {
    foreach (['user', 'node', 'system', 'update_test_schema'] as $module) {
      $this->assertEqual(drupal_get_installed_schema_version($module), 8000, SafeMarkup::format('Module @module schema is 8000', ['@module' => $module]));
    }

    // Ensure that all {router} entries can be unserialized. If they cannot be
    // unserialized a notice will be thrown by PHP.

    $result = \Drupal::database()->query("SELECT name, route from {router}")->fetchAllKeyed(0, 1);
    // For the purpose of fetching the notices and displaying more helpful error
    // messages, let's override the error handler temporarily.
    set_error_handler(function ($severity, $message, $filename, $lineno) {
      throw new \ErrorException($message, 0, $severity, $filename, $lineno);
    });
    foreach ($result as $route_name => $route) {
      try {
        unserialize($route);
      }
      catch (\Exception $e) {
        $this->fail(sprintf('Error "%s" while unserializing route %s', $e->getMessage(), Html::escape($route_name)));
      }
    }
    restore_error_handler();

    // Before accessing the site we need to run updates first or the site might
    // be broken.
    $this->runUpdates();
    $this->assertEqual(\Drupal::config('system.site')->get('name'), 'Site-Install');
    $this->drupalGet('<front>');
    $this->assertText('Site-Install');

    // Ensure that the database tasks have been run during set up. Neither MySQL
    // nor SQLite make changes that are testable.
    $database = $this->container->get('database');
    if ($database->driver() == 'pgsql') {
      $this->assertEqual('on', $database->query("SHOW standard_conforming_strings")->fetchField());
      $this->assertEqual('escape', $database->query("SHOW bytea_output")->fetchField());
    }
  }

  /**
   * Test that updates are properly run.
   */
  public function testUpdateHookN() {
    // Increment the schema version.
    \Drupal::state()->set('update_test_schema_version', 8001);
    $this->runUpdates();

    $select = \Drupal::database()->select('watchdog');
    $select->orderBy('wid', 'DESC');
    $select->range(0, 5);
    $select->fields('watchdog', ['message']);

    $container_cannot_be_saved_messages = array_filter(iterator_to_array($select->execute()), function($row) {
      return strpos($row->message, 'Container cannot be saved to cache.') !== FALSE;
    });
    $this->assertEqual([], $container_cannot_be_saved_messages);

    // Ensure schema has changed.
    $this->assertEqual(drupal_get_installed_schema_version('update_test_schema', TRUE), 8001);
    // Ensure the index was added for column a.
    $this->assertTrue(db_index_exists('update_test_schema_table', 'test'), 'Version 8001 of the update_test_schema module is installed.');
  }

}
