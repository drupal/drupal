<?php

namespace Drupal\Tests\node\Functional\Update;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that node settings are properly updated during database updates.
 *
 * @group node
 * @group legacy
 */
class NodeUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8-rc1.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that the node entity type has a 'published' entity key.
   *
   * @see node_update_8301()
   */
  public function testPublishedEntityKey() {
    // Check that the 'published' entity key does not exist prior to the update.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('node');
    $this->assertFalse($entity_type->getKey('published'));

    // Run updates.
    $this->runUpdates();

    // Check that the entity key exists and it has the correct value.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('node');
    $this->assertEqual('status', $entity_type->getKey('published'));
  }

  /**
   * Tests that the node entity form has the status checkbox.
   *
   * @see node_post_update_configure_status_field_widget()
   */
  public function testStatusCheckbox() {
    // Run updates.
    $this->runUpdates();

    $query = \Drupal::entityQuery('entity_form_display')
      ->condition('targetEntityType', 'node');
    $ids = $query->execute();
    $form_displays = EntityFormDisplay::loadMultiple($ids);

    /**
     * @var string $id
     * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display
     */
    foreach ($form_displays as $id => $form_display) {
      $component = $form_display->getComponent('status');
      $this->assertEqual('boolean_checkbox', $component['type']);
      $this->assertEqual(['display_label' => TRUE], $component['settings']);
    }
  }

  /**
   * Tests that the node entity type has an 'owner' entity key.
   *
   * @see node_update_8700()
   */
  public function testOwnerEntityKey() {
    // Check that the 'owner' entity key does not exist prior to the update.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('node');
    $this->assertFalse($entity_type->getKey('owner'));

    // Run updates.
    $this->runUpdates();

    // Check that the entity key exists and it has the correct value.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('node');
    $this->assertEquals('uid', $entity_type->getKey('owner'));
  }

}
