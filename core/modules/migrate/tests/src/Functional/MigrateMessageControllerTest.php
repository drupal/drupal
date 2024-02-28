<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Functional;

/**
 * Tests for the MigrateController class.
 *
 * @group migrate
 */
class MigrateMessageControllerTest extends MigrateMessageTestBase {

  /**
   * Tests the overview page for migrate messages.
   *
   * Tests the overview page with the following scenarios;
   * - No message tables.
   * - With message tables.
   */
  public function testOverview(): void {
    $session = $this->assertSession();

    // First, test with no source database or message tables.
    $this->drupalGet('/admin/reports/migration-messages');
    $session->titleEquals('Migration messages | Drupal');
    $session->pageTextContainsOnce('There are no migration message tables.');

    // Create map and message tables.
    $this->createTables($this->migrationIds);

    // Now, test with message tables.
    $this->drupalGet('/admin/reports/migration-messages');
    foreach ($this->migrationIds as $migration_id) {
      $session->pageTextContains($migration_id);
    }
  }

  /**
   * Tests the detail pages for migrate messages.
   *
   * Tests the detail page with the following scenarios;
   * - No source database connection or message tables with a valid and an
   *   invalid migration.
   * - A source database connection with message tables with a valid and an
   *   invalid migration.
   * - A source database connection with message tables and a source plugin
   *   that does not have a description for a source ID in the values returned
   *   from fields().
   */
  public function testDetail(): void {
    $session = $this->assertSession();

    // Details page with invalid migration.
    $this->drupalGet('/admin/reports/migration-messages/invalid');
    $session->statusCodeEquals(404);

    // Details page with valid migration.
    $this->drupalGet('/admin/reports/migration-messages/custom_test');
    $session->statusCodeEquals(404);

    // Create map and message tables.
    $this->createTables($this->migrationIds);

    $not_available_text = "When there is an error processing a row, the migration system saves the error message but not the source ID(s) of the row. That is why some messages in this table have 'Not available' in the source ID column(s).";

    // Test details page for each migration.
    foreach ($this->migrationIds as $migration_id) {
      $this->drupalGet("/admin/reports/migration-messages/$migration_id");
      $session->pageTextContains($migration_id);
      if ($migration_id == 'custom_test') {
        $session->pageTextContains('Not available');
        $session->pageTextContains($not_available_text);
      }
    }

    // Details page with invalid migration.
    $this->drupalGet('/admin/reports/migration-messages/invalid');
    $session->statusCodeEquals(404);

    // Details page for a migration without a map table.
    $this->database->schema()->dropTable('migrate_map_custom_test');
    $this->drupalGet('/admin/reports/migration-messages/custom_test');
    $session->statusCodeEquals(404);

    // Details page for a migration with a map table but no message table.
    $this->createTables($this->migrationIds);
    $this->database->schema()->dropTable('migrate_message_custom_test');
    $this->drupalGet('/admin/reports/migration-messages/custom_test');
    $session->pageTextContains('The message table is missing for this migration.');
  }

}
