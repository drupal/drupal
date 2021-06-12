<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;

/**
 * Tries to update a module which has no pre-existing schema.
 *
 * @group Update
 */
class NoPreExistingSchemaUpdateTest extends BrowserTestBase {
  use RequirementsPageTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    $connection = Database::getConnection();

    // Enable the update_test_no_preexisting module by altering the
    // core.extension configuration directly, so that the schema version
    // information is missing.
    $extensions = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();
    $extensions = unserialize($extensions);
    $connection->update('config')
      ->fields([
        'data' => serialize(array_merge_recursive($extensions, ['module' => ['update_test_no_preexisting' => 0]])),
      ])
      ->condition('name', 'core.extension')
      ->execute();
  }

  /**
   * Tests the system module updates with no dependencies installed.
   */
  public function testNoPreExistingSchema() {
    $schema = \Drupal::keyValue('system.schema')->getAll();
    $this->assertArrayNotHasKey('update_test_no_preexisting', $schema);
    $this->assertFalse(\Drupal::state()->get('update_test_no_preexisting_update_8001', FALSE));

    $update_url = Url::fromRoute('system.db_update');
    require_once $this->root . '/core/includes/update.inc';
    // The site might be broken at the time so logging in using the UI might
    // not work, so we use the API itself.
    $this->writeSettings([
      'settings' => [
        'update_free_access' => (object) [
          'value' => TRUE,
          'required' => TRUE,
        ],
      ],
    ]);

    $this->drupalGet($update_url);
    $this->updateRequirementsProblem();

    $schema = \Drupal::keyValue('system.schema')->getAll();
    $this->assertArrayHasKey('update_test_no_preexisting', $schema);
    $this->assertEquals('8001', $schema['update_test_no_preexisting']);
    // The schema version has been fixed, but the update was never run.
    $this->assertFalse(\Drupal::state()->get('update_test_no_preexisting_update_8001', FALSE));
    $this->assertSession()->pageTextContains('Schema information for module update_test_no_preexisting was missing from the database. You should manually review the module updates and your database to check if any updates have been skipped up to, and including, update_test_no_preexisting_update_8001().');
  }

}
