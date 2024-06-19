<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Functional;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests isolation level warning when the config is set in settings.php.
 *
 * @group mysql
 */
class RequirementsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mysql'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The isolation_level option is only available for MySQL.
    $connection = Database::getConnection();
    if ($connection->driver() !== 'mysql') {
      $this->markTestSkipped("This test does not support the {$connection->driver()} database driver.");
    }
  }

  /**
   * Test the isolation level warning message on status page.
   */
  public function testIsolationLevelWarningNotDisplaying(): void {
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'access site reports',
    ]);
    $this->drupalLogin($admin_user);
    $connection = Database::getConnection();

    // Set the isolation level to a level that produces a warning.
    $this->writeIsolationLevelSettings('REPEATABLE READ');

    // Check the message is not a warning.
    $this->drupalGet('admin/reports/status');
    $elements = $this->xpath('//details[@class="system-status-report__entry"]//div[contains(text(), "REPEATABLE-READ")]');
    $this->assertCount(1, $elements);
    // Ensure it is a warning.
    $this->assertStringContainsString('warning', $elements[0]->getParent()->getParent()->find('css', 'summary')->getAttribute('class'));

    // Rollback the isolation level to read committed.
    $this->writeIsolationLevelSettings('READ COMMITTED');

    // Check the message is not a warning.
    $this->drupalGet('admin/reports/status');
    $elements = $this->xpath('//details[@class="system-status-report__entry"]//div[contains(text(), "READ-COMMITTED")]');
    $this->assertCount(1, $elements);
    // Ensure it is a not a warning.
    $this->assertStringNotContainsString('warning', $elements[0]->getParent()->getParent()->find('css', 'summary')->getAttribute('class'));

    $specification = [
      'fields' => [
        'text' => [
          'type' => 'text',
          'description' => 'A text field',
        ],
      ],
    ];

    $connection->schema()->createTable('test_table_without_primary_key', $specification);

    // Set the isolation level to a level that produces a warning.
    $this->writeIsolationLevelSettings('REPEATABLE READ');

    // Check the message is not a warning.
    $this->drupalGet('admin/reports/status');
    $elements = $this->xpath('//details[@class="system-status-report__entry"]//div[contains(text(), :text)]', [
      ':text' => 'The recommended level for Drupal is "READ COMMITTED". For this to work correctly, all tables must have a primary key. The following table(s) do not have a primary key: test_table_without_primary_key.',
    ]);
    $this->assertCount(1, $elements);
    $this->assertStringStartsWith('REPEATABLE-READ', $elements[0]->getParent()->getText());
    // Ensure it is a warning.
    $this->assertStringContainsString('warning', $elements[0]->getParent()->getParent()->find('css', 'summary')->getAttribute('class'));

    // Rollback the isolation level to read committed.
    $this->writeIsolationLevelSettings('READ COMMITTED');

    // Check the message is not a warning.
    $this->drupalGet('admin/reports/status');
    $elements = $this->xpath('//details[@class="system-status-report__entry"]//div[contains(text(), :text)]', [
      ':text' => 'For this to work correctly, all tables must have a primary key. The following table(s) do not have a primary key: test_table_without_primary_key.',
    ]);
    $this->assertCount(1, $elements);
    $this->assertStringStartsWith('READ-COMMITTED', $elements[0]->getParent()->getText());
    // Ensure it is an error.
    $this->assertStringContainsString('error', $elements[0]->getParent()->getParent()->find('css', 'summary')->getAttribute('class'));
  }

  /**
   * Writes the isolation level in settings.php.
   *
   * @param string $isolation_level
   *   The isolation level.
   */
  private function writeIsolationLevelSettings(string $isolation_level) {
    $settings['databases']['default']['default']['init_commands'] = (object) [
      'value' => [
        'isolation_level' => "SET SESSION TRANSACTION ISOLATION LEVEL {$isolation_level}",
      ],
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

}
