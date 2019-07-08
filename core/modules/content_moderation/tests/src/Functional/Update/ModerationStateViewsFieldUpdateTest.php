<?php

namespace Drupal\Tests\content_moderation\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Test updating the views moderation state field plugin ID.
 *
 * @group Update
 * @group legacy
 *
 * @see content_moderation_post_update_views_field_plugin_id()
 */
class ModerationStateViewsFieldUpdateTest extends UpdatePathTestBase {

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
   * Test the views field ID update.
   */
  public function testViewsFieldIdUpdate() {
    $views_display = View::load('moderated_content')->getDisplay('default');
    $this->assertEquals('field', $views_display['display_options']['fields']['moderation_state']['plugin_id']);

    $this->runUpdates();

    $views_display = View::load('moderated_content')->getDisplay('default');
    $this->assertEquals('moderation_state_field', $views_display['display_options']['fields']['moderation_state']['plugin_id']);
  }

  /**
   * Tests that the update succeeds even if Views is not installed.
   */
  public function testViewsFieldIdUpdateWithoutViews() {
    $this->container->get('module_installer')->uninstall(['views']);
    $this->runUpdates();
  }

}
