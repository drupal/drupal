<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\UuidUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Performs major version release upgrade tests on a populated database.
 *
 * Loads an installation of Drupal 7.x and runs the upgrade process on it.
 *
 * The install contains the minimal profile modules (along with generated
 * content) so that an update from of a site under this profile may be tested.
 */
class UuidUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name'  => 'UUID upgrade test',
      'description'  => 'Upgrade tests for a node and user data.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    // Path to the database dump files.
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.filled.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.language.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests a successful point release update.
   */
  public function testUuidUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Confirm that all {node} entries has uuid.
    $result = db_query('SELECT COUNT(*) FROM {comment} WHERE uuid IS NULL')->fetchField();
    $this->assertFalse($result, 'All comments has uuid assigned');

    // Confirm that all {node} entries has uuid.
    $result = db_query('SELECT COUNT(*) FROM {file_managed} WHERE uuid IS NULL')->fetchField();
    $this->assertFalse($result, 'All files has uuid assigned');

    // Confirm that all {node} entries has uuid.
    $result = db_query('SELECT COUNT(*) FROM {node} WHERE uuid IS NULL')->fetchField();
    $this->assertFalse($result, 'All nodes has uuid assigned');

    // Confirm that all {node} entries has uuid.
    $result = db_query('SELECT COUNT(*) FROM {taxonomy_term_data} WHERE uuid IS NULL')->fetchField();
    $this->assertFalse($result, 'All taxonomy terms has uuid assigned');

    // Confirm that all {user} entries has uuid.
    $result = db_query('SELECT COUNT(*) FROM {users} WHERE uuid IS NULL')->fetchField();
    $this->assertFalse($result, 'All users has uuid assigned');
  }
}
