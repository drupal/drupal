<?php

namespace Drupal\Tests\content_moderation\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for updating the moderated content view.
 *
 * @group Update
 * @group legacy
 *
 * @see content_moderation_post_update_set_views_filter_latest_translation_affected_revision()
 */
class ModeratedContentViewLatestRevisionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.4.0-content_moderation_installed.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.5.0-content_moderation_installed.php',
    ];
  }

  /**
   * Tests updating the moderated content view.
   */
  public function testUpdateModeratedContentView() {
    $display = View::load('moderated_content')->getDisplay('default');
    $this->assertArrayHasKey('latest_revision', $display['display_options']['filters']);
    $this->assertArrayNotHasKey('latest_translation_affected_revision', $display['display_options']['filters']);

    $this->runUpdates();

    $display = View::load('moderated_content')->getDisplay('default');
    $this->assertArrayNotHasKey('latest_revision', $display['display_options']['filters']);
    $this->assertArrayHasKey('latest_translation_affected_revision', $display['display_options']['filters']);
  }

}
