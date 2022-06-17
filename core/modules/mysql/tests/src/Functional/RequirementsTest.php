<?php

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
    $connectionInfo = Database::getConnectionInfo();
    if ($connectionInfo['default']['driver'] !== 'mysql') {
      $this->markTestSkipped("This test does not support the {$connectionInfo['default']['driver']} database driver.");
    }
  }

  /**
   * Test the isolation level warning message on status page.
   */
  public function testIsolationLevelWarningNotDisplaying() {
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'access site reports',
    ]);
    $this->drupalLogin($admin_user);

    // Change the isolation level to force the warning message.
    $this->writeIsolationLevelSettings('REPEATABLE READ');

    // Check if the warning message is being displayed.
    $this->drupalGet('admin/reports/status');
    $elements = $this->xpath('//details[@class="system-status-report__entry"]//div[contains(text(), :text)]', [
      ':text' => 'For the best performance and to minimize locking issues, the READ-COMMITTED',
    ]);
    $this->assertCount(1, $elements);
    $this->assertStringStartsWith('Transaction Isolation Level', $elements[0]->getParent()->getText());

    // Rollback the isolation level to read committed.
    $this->writeIsolationLevelSettings('READ COMMITTED');

    // Check if the warning message is gone.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextNotContains('Database Isolation Level');
    $elements = $this->xpath('//details[@class="system-status-report__entry"]//div[contains(text(), :text)]', [
      ':text' => 'For the best performance and to minimize locking issues, the READ-COMMITTED',
    ]);

    $this->assertCount(0, $elements);
  }

  /**
   * Writes the isolation level in settings.php.
   *
   * @param string $isolation_level
   *   The isolation level.
   */
  private function writeIsolationLevelSettings(string $isolation_level) {
    $settings['databases']['default']['default']['init_commands'] = (object) [
      'value'    => [
        'isolation' => "SET SESSION TRANSACTION ISOLATION LEVEL {$isolation_level}",
      ],
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

}
