<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that a site on 10.4.0 is prevented from downgrading to 11.0.0.
 *
 * This tests the upgrade path when there is a pair of equivalent updates. The
 * earlier update is 10400 and the latter one is 11102.
 *
 * @group Update
 */
class PreventDowngradeTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles[] = __DIR__ . '/../../../../tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz';
  }

  /**
   * Tests prevention of incorrect update path.
   */
  public function testDowngradePrevention(): void {
    $database = \Drupal::database();

    // Record the update 10400 as an equivalent to update 11102.
    $database->insert('key_value')
      ->fields([
        'collection' => 'core.equivalent_updates',
        'name' => 'downgrade_prevention_test',
        'value' => 'a:1:{i:11102;a:2:{s:10:"ran_update";s:5:"10400";s:21:"future_version_string";s:6:"11.1.0";}}',
      ])
      ->execute();

    // Set the test module schema to 10400.
    $database->insert('key_value')
      ->fields([
        'value' => 'i:10400;',
        'collection' => 'system.schema',
        'name' => 'downgrade_prevention_test',
      ])
      ->execute();

    // Running the updates should fail with a requirements failure because the
    // later update, 11102, is missing from the code base.
    try {
      $this->runUpdates();
    }
    catch (\Exception) {
      // Continue.
    }
    $this->assertSession()->pageTextContains('Missing updates for: 10.4 downgrade prevention test');
    $this->assertSession()->pageTextContains('The version of the 10.4 downgrade prevention test module that you are attempting to update to is missing update 11102 (which was marked as an equivalent by 10400). Update to at least Drupal Core 11.1.0.');

    // Repeat the test with a code base that does have the 11102 update,
    // downgrade_prevention_test_update_11102(). First, install
    // downgrade_prevention_test.
    $extensions = $database->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();
    $extensions = unserialize($extensions);

    // Install the 'downgrade_prevention_test' module.
    $extensions['module']['downgrade_prevention_test'] = 0;
    $database->update('config')
      ->fields([
        'data' => serialize($extensions),
      ])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();

    // Set the schema for 'downgrade_prevention_test' to the update function,
    // 11102.
    $database->update('key_value')
      ->fields([
        'value' => 'i:11102;',
      ])
      ->condition('collection', 'system.schema')
      ->condition('name', 'downgrade_prevention_test')
      ->execute();

    // Running the updates should succeed because the 11102 update function,
    // downgrade_prevention_test_update_11102(), now exists.
    $this->runUpdates();

    $this->assertSession()->pageTextContains('Updates were attempted.');
  }

}
