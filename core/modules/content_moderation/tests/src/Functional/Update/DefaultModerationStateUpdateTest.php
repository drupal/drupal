<?php

namespace Drupal\Tests\content_moderation\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests the upgrade path for updating the 'default_moderation_state' setting.
 *
 * @group Update
 * @group legacy
 *
 * @see content_moderation_post_update_set_default_moderation_state()
 */
class DefaultModerationStateUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.4.0-content_moderation_installed.php',
    ];
  }

  /**
   * Tests updating the default moderation state setting.
   */
  public function testUpdateDefaultModerationState() {
    $workflow = Workflow::load('editorial');
    $this->assertArrayNotHasKey('default_moderation_state', $workflow->getTypePlugin()->getConfiguration());

    $this->runUpdates();

    $workflow = Workflow::load('editorial');
    $this->assertEquals('draft', $workflow->getTypePlugin()->getConfiguration()['default_moderation_state']);
  }

}
