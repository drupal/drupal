<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal_ui\Functional\d7;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests that a double slash is not in d7_file file not found migrate messages.
 *
 * @group migrate_drupal_ui
 */
class DoubleSlashTest extends MigrateUpgradeExecuteTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'migrate_drupal_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->loadFixture(\Drupal::service('extension.list.module')->getPath('migrate_drupal') . '/tests/fixtures/drupal7.php');
  }

  /**
   * Executes all steps of migrations upgrade.
   */
  public function testMigrateUpgradeExecute(): void {
    // Change fid 1 to a filename that does not exist.
    $this->sourceDatabase
      ->update('file_managed')
      ->condition('fid', 1)
      ->fields([
        'filename' => 'foo.txt',
        'uri' => 'public://foo.txt',
      ])
      ->execute();

    // Get valid credentials.
    $edits = $this->translatePostValues($this->getCredentials());

    // Start the upgrade process.
    $this->drupalGet('/upgrade');
    $this->submitForm([], 'Continue');
    $this->submitForm($edits, 'Review upgrade');
    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $this->useTestMailCollector();
    $this->submitForm([], 'Perform upgrade');

    // Tests the migration log contains an error message.
    $migration = $this->getMigrationPluginManager()->createInstance('d7_file');
    $messages = $migration->getIdMap()->getMessages();

    $count = 0;
    foreach ($messages as $message) {
      $count++;
      $this->assertStringContainsString('/migrate_drupal_ui/tests/src/Functional/d7/files/sites/default/files/foo.txt', $message->message);
      $this->assertSame(MigrationInterface::MESSAGE_ERROR, (int) $message->level);
    }
    $this->assertSame(1, $count);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath(): string {
    return __DIR__ . '/files';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths(): array {
    return [];
  }

}
