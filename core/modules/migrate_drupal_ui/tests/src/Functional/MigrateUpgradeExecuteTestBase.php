<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;

/**
 * Provides a base class for testing a complete upgrade via the UI.
 */
abstract class MigrateUpgradeExecuteTestBase extends MigrateUpgradeTestBase {

  use CreateTestContentEntitiesTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create content.
    $this->createContent();

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

}
