<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Functional;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests for the MessageForm class.
 *
 * @group migrate
 */
class MigrateMessageFormTest extends MigrateMessageTestBase {

  /**
   * Tests the message form.
   */
  public function testFilter(): void {
    $session = $this->assertSession();

    // Create map and message tables.
    $this->createTables($this->migrationIds);

    // Expected counts for each error level.
    $expected = [
      MigrationInterface::MESSAGE_ERROR => 3,
      MigrationInterface::MESSAGE_WARNING => 0,
      MigrationInterface::MESSAGE_NOTICE => 0,
      MigrationInterface::MESSAGE_INFORMATIONAL => 1,
    ];

    // Confirm that all the entries are displayed.
    $this->drupalGet('/admin/reports/migration-messages/custom_test');
    $session->statusCodeEquals(200);
    $messages = $this->getMessages();
    $this->assertCount(4, $messages);

    // Set the filter to match each of the two filter-type attributes and
    // confirm the correct number of entries are displayed.
    foreach ($expected as $level => $expected_count) {
      $edit['severity[]'] = $level;
      $this->submitForm($edit, 'Filter');
      $count = $this->getLevelCounts($expected);
      $this->assertEquals($expected_count, $count[$level], sprintf('Count for level %s failed', $level));
    }

    // Reset the filter
    $this->submitForm([], 'Reset');
    $messages = $this->getMessages();
    $this->assertCount(4, $messages);
  }

  /**
   * Gets the count of migration messages by level.
   *
   * @param array $levels
   *   The error levels to check.
   *
   * @return array
   *   The count of each error level keyed by the error level.
   */
  protected function getLevelCounts(array $levels): array {
    $entries = $this->getMessages();
    $count = array_fill(1, count($levels), 0);
    foreach ($entries as $entry) {
      if (array_key_exists($entry['severity'], $levels)) {
        $count[$entry['severity']]++;
      }
    }
    return $count;
  }

  /**
   * Gets the migrate messages.
   *
   * @return array[]
   *   List of log events where each event is an array with following keys:
   *   - msg_id: (string) A message id.
   *   - severity: (int) The MigrationInterface error level.
   *   - message: (string) The migration message.
   */
  protected function getMessages(): array {
    $levels = [
      'Error' => MigrationInterface::MESSAGE_ERROR,
      'Warning' => MigrationInterface::MESSAGE_WARNING,
      'Notice' => MigrationInterface::MESSAGE_NOTICE,
      'Info' => MigrationInterface::MESSAGE_INFORMATIONAL,
    ];
    $entries = [];
    $table = $this->xpath('.//table[@id="admin-migrate-msg"]/tbody/tr');
    foreach ($table as $row) {
      $cells = $row->findAll('css', 'td');
      if (count($cells) === 3) {
        $entries[] = [
          'msg_id' => $cells[0]->getText(),
          'severity' => $levels[$cells[1]->getText()],
          'message' => $cells[2]->getText(),
        ];
      }
    }
    return $entries;
  }

}
