<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Test updating the dependencies of entity form displays.
 *
 * @group Update
 * @group legacy
 *
 * @see content_moderation_post_update_entity_display_dependencies()
 */
class EntityFormDisplayDependenciesUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../fixtures/update/drupal-8.4.0-content_moderation_installed.php',
      __DIR__ . '/../../fixtures/update/drupal-8.entity-form-display-dependencies-2915383.php',
    ];
  }

  /**
   * Tests updating the dependencies of entity displays.
   */
  public function testEntityDisplaysUpdated() {
    $no_moderation_form_display = EntityFormDisplay::load('block_content.basic.default');
    $has_moderation_form_display = EntityFormDisplay::load('node.article.default');

    // Assert the moderation field and content_moderation dependency exists on
    // an entity type that does not have moderation enabled, these will be
    // removed.
    $this->assertEquals('moderation_state_default', $no_moderation_form_display->getComponent('moderation_state')['type']);
    $this->assertTrue(in_array('content_moderation', $no_moderation_form_display->getDependencies()['module']));

    // Assert the editorial config dependency doesn't exist on the entity form
    // with moderation, this will be added.
    $this->assertFalse(in_array('workflows.workflow.editorial', $has_moderation_form_display->getDependencies()['config']));

    $this->runUpdates();

    $no_moderation_form_display = EntityFormDisplay::load('block_content.basic.default');
    $has_moderation_form_display = EntityFormDisplay::load('node.article.default');

    // The moderation_state field has been removed from the non-moderated block
    // entity form display.
    $this->assertEquals(NULL, $no_moderation_form_display->getComponent('moderation_state'));
    $this->assertFalse(in_array('content_moderation', $no_moderation_form_display->getDependencies()['module']));

    // The editorial workflow config dependency has been added to moderated
    // form display.
    $this->assertTrue(in_array('workflows.workflow.editorial', $has_moderation_form_display->getDependencies()['config']));
  }

}
