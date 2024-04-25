<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
  protected bool $outputLogs = FALSE;

  /**
   * The admin username after the migration.
   *
   * @var string
   */
  protected string $migratedAdminUserName = 'admin';

  /**
   * The number of expected logged errors of type migrate_drupal_ui.
   *
   * @var int
   */
  protected int $expectedLoggedErrors = 0;

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
    $settings['config']['system.mail']['mailer_dsn']['scheme'] = (object) [
      'value' => 'null',
      'required' => TRUE,
    ];
    $settings['config']['system.mail']['mailer_dsn']['host'] = (object) [
      'value' => 'null',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Checks the number of the specified entity's revisions.
   *
   * Revision translations are excluded.
   *
   * @param string $content_entity_type_id
   *   The entity type ID of the content entity, e.g. 'node', 'media',
   *   'block_content'.
   * @param int $expected_revision_count
   *   The expected number of the revisions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function assertEntityRevisionsCount(string $content_entity_type_id, int $expected_revision_count) {
    $entity_storage = \Drupal::entityTypeManager()->getStorage($content_entity_type_id);
    assert($entity_storage instanceof ContentEntityStorageInterface);
    $revision_ids = $entity_storage
      ->getQuery()
      ->allRevisions()
      ->accessCheck(FALSE)
      ->execute();
    $this->assertCount(
      $expected_revision_count,
      $revision_ids,
      sprintf(
        "The number of %s revisions is different than expected",
        $content_entity_type_id
      )
    );
  }

  /**
   * Asserts log errors.
   */
  public function assertLogError(): void {
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
  public function outputLogs(string $username): void {
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
