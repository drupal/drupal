<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests that the additional settings are added to the entity link field.
 *
 * @coversDefaultClass \Drupal\views\ViewsConfigUpdater
 *
 * @see views_post_update_entity_link_url()
 *
 * @group legacy
 */
class EntityLinkOutputUrlUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/entity-link-output-url.php',
    ];
  }

  /**
   * Tests that the additional settings are added to the config.
   *
   * @covers ::needsEntityLinkUrlUpdate
   */
  public function testViewsPostUpdateEntityLinkUrl() {
    $this->runUpdates();

    // Load and initialize our test view.
    $view = View::load('node_link_update_test');
    $data = $view->toArray();
    // Check that the field contains the new values.
    $this->assertIdentical(FALSE, $data['display']['default']['display_options']['fields']['view_node']['output_url_as_text']);
    $this->assertIdentical(FALSE, $data['display']['default']['display_options']['fields']['view_node']['absolute']);
  }

}
