<?php

namespace Drupal\system\Tests\Update;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Tests system_post_update_add_region_to_entity_displays().
 *
 * @group Update
 */
class UpdateEntityDisplayTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that entity displays are updated with regions for their fields.
   */
  public function testUpdate() {
    // No region key appears pre-update.
    $entity_form_display = EntityFormDisplay::load('node.article.default');
    $options = $entity_form_display->getComponent('body');
    $this->assertFalse(array_key_exists('region', $options));

    $entity_view_display = EntityViewDisplay::load('node.article.default');
    $options = $entity_view_display->getComponent('body');
    $this->assertFalse(array_key_exists('region', $options));

    $this->runUpdates();

    // The region key has been populated with 'content'.
    $entity_form_display = EntityFormDisplay::load('node.article.default');
    $options = $entity_form_display->getComponent('body');
    $this->assertIdentical('content', $options['region']);

    $entity_view_display = EntityViewDisplay::load('node.article.default');
    $options = $entity_view_display->getComponent('body');
    $this->assertIdentical('content', $options['region']);
  }

}
