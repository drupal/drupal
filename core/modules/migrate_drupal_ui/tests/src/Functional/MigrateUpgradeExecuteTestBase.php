<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;

/**
 * Provides a base class for testing a complete upgrade via the UI.
 */
abstract class MigrateUpgradeExecuteTestBase extends MigrateUpgradeTestBase {

  use CreateTestContentEntitiesTrait;

  /**
   * Indicates if the watchdog logs should be output.
   *
   * @var bool
   */
  protected $outputLogs = FALSE;

  /**
   * The admin username after the migration.
   *
   * @var string
   */
  protected $migratedAdminUserName = 'admin';

  /**
   * The number of expected logged errors of type migrate_drupal_ui.
   *
   * @var string
   */
  protected $expectedLoggedErrors = 0;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create content.
    $this->createContent();

  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->outputLogs) {
      $this->outputLogs($this->migratedAdminUserName);
      $this->assertLogError();
    }
    parent::tearDown();
  }

  /**
   * Executes an upgrade and then an incremental upgrade.
   */
  public function doUpgradeAndIncremental() {
    // Start the upgrade process.
    $this->submitCredentialForm();
    $session = $this->assertSession();

    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Test the review form.
    $this->assertReviewForm();

    $this->useTestMailCollector();
    $this->submitForm([], 'Perform upgrade');
    $this->assertUpgrade($this->getEntityCounts());

    \Drupal::service('module_installer')->install(['forum']);
    \Drupal::service('module_installer')->install(['book']);

    // Test incremental migration.
    $this->createContentPostUpgrade();

    $this->drupalGet('/upgrade');
    $session->pageTextContains("An upgrade has already been performed on this site. To perform a new migration, create a clean and empty new install of Drupal $this->destinationSiteVersion. Rollbacks are not yet supported through the user interface.");
    $this->submitForm([], 'Import new configuration and content from old site');
    $this->submitForm($this->edits, 'Review upgrade');
    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Run the incremental migration and check the results.
    $this->submitForm([], 'Perform upgrade');
    $this->assertUpgrade($this->getEntityCountsIncremental());
  }

  /**
   * Helper to set the test mail collector in settings.php.
   */
  public function useTestMailCollector() {
    // Set up an override.
    $settings['config']['system.mail']['interface']['default'] = (object) [
      'value' => 'test_mail_collector',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Asserts log errors.
   */
  public function assertLogError() {
    $db = \Drupal::service('database');
    $num_errors = $db->select('watchdog', 'w')
      ->fields('w')
      ->condition('type', 'migrate_drupal_ui')
      ->condition('severity', RfcLogLevel::ERROR)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertSame($this->expectedLoggedErrors, (int) $num_errors);
  }

  /**
   * Preserve the logs pages.
   */
  public function outputLogs($username) {
    // Ensure user 1 is accessing the admin log. Change the username because
    // the migration changes the username of user 1 but not the password.
    if (\Drupal::currentUser()->id() != 1) {
      $this->rootUser->name = $username;
      $this->drupalLogin($this->rootUser);
    }
    $this->drupalGet('/admin/reports/dblog');
    while ($next_link = $this->getSession()->getPage()->findLink('Next page')) {
      $next_link->click();
    }

  }

}
