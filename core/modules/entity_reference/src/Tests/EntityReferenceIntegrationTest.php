<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceIntegrationTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests various Entity reference UI components.
 *
 * @group entity_reference
 */
class EntityReferenceIntegrationTest extends WebTestBase {

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('config_test', 'entity_test', 'entity_reference');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a test user.
    $web_user = $this->drupalCreateUser(array('administer entity_test content'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the entity reference field with all its supported field widgets.
   */
  public function testSupportedEntityTypesAndWidgets() {
    foreach ($this->getTestEntities() as $referenced_entities) {
      $this->fieldName = 'field_test_' . $referenced_entities[0]->getEntityTypeId();

      // Create an Entity reference field.
      entity_reference_create_field($this->entityType, $this->bundle, $this->fieldName, $this->fieldName, $referenced_entities[0]->getEntityTypeId(), 'default', array(), 2);

      // Test the default 'entity_reference_autocomplete' widget.
      entity_get_form_display($this->entityType, $this->bundle, 'default')->setComponent($this->fieldName)->save();

      $entity_name = $this->randomMachineName();
      $edit = array(
        'name[0][value]' => $entity_name,
        $this->fieldName . '[0][target_id]' => $referenced_entities[0]->label() . ' (' . $referenced_entities[0]->id() . ')',
        // Test an input of the entity label without a ' (entity_id)' suffix.
        $this->fieldName . '[1][target_id]' => $referenced_entities[1]->label(),
      );
      $this->drupalPostForm($this->entityType . '/add', $edit, t('Save'));
      $this->assertFieldValues($entity_name, $referenced_entities);

      // Try to post the form again with no modification and check if the field
      // values remain the same.
      $entity = current(entity_load_multiple_by_properties($this->entityType, array('name' => $entity_name)));
      $this->drupalPostForm($this->entityType . '/manage/' . $entity->id(), array(), t('Save'));
      $this->assertFieldValues($entity_name, $referenced_entities);

      // Test the 'entity_reference_autocomplete_tags' widget.
      entity_get_form_display($this->entityType, $this->bundle, 'default')->setComponent($this->fieldName, array(
        'type' => 'entity_reference_autocomplete_tags',
      ))->save();

      $entity_name = $this->randomMachineName();
      $target_id = $referenced_entities[0]->label() . ' (' . $referenced_entities[0]->id() . ')';
      // Test an input of the entity label without a ' (entity_id)' suffix.
      $target_id .= ', ' . $referenced_entities[1]->label();
      $edit = array(
        'name[0][value]' => $entity_name,
        $this->fieldName . '[target_id]' => $target_id,
      );
      $this->drupalPostForm($this->entityType . '/add', $edit, t('Save'));
      $this->assertFieldValues($entity_name, $referenced_entities);

      // Try to post the form again with no modification and check if the field
      // values remain the same.
      $entity = current(entity_load_multiple_by_properties($this->entityType, array('name' => $entity_name)));
      $this->drupalPostForm($this->entityType . '/manage/' . $entity->id(), array(), t('Save'));
      $this->assertFieldValues($entity_name, $referenced_entities);

      // Test all the other widgets supported by the entity reference field.
      // Since we don't know the form structure for these widgets, just test
      // that editing and saving an already created entity works.
      $exclude = array('entity_reference_autocomplete', 'entity_reference_autocomplete_tags');
      $entity = current(entity_load_multiple_by_properties($this->entityType, array('name' => $entity_name)));
      $supported_widgets = \Drupal::service('plugin.manager.field.widget')->getOptions('entity_reference');
      $supported_widget_types = array_diff(array_keys($supported_widgets), $exclude);

      foreach ($supported_widget_types as $widget_type) {
        entity_get_form_display($this->entityType, $this->bundle, 'default')->setComponent($this->fieldName, array(
          'type' => $widget_type,
        ))->save();

        $this->drupalPostForm($this->entityType . '/manage/' . $entity->id(), array(), t('Save'));
        $this->assertFieldValues($entity_name, $referenced_entities);
      }
    }
  }

  /**
   * Asserts that the reference field values are correct.
   *
   * @param string $entity_name
   *   The name of the test entity.
   * @param \Drupal\Core\Entity\EntityInterface[] $referenced_entities
   *   An array of referenced entities.
   */
  protected function assertFieldValues($entity_name, $referenced_entities) {
    $entity = current(entity_load_multiple_by_properties($this->entityType, array('name' => $entity_name)));

    $this->assertTrue($entity, format_string('%entity_type: Entity found in the database.', array('%entity_type' => $this->entityType)));

    $this->assertEqual($entity->{$this->fieldName}->target_id, $referenced_entities[0]->id());
    $this->assertEqual($entity->{$this->fieldName}->entity->id(), $referenced_entities[0]->id());
    $this->assertEqual($entity->{$this->fieldName}->entity->label(), $referenced_entities[0]->label());

    $this->assertEqual($entity->{$this->fieldName}[1]->target_id, $referenced_entities[1]->id());
    $this->assertEqual($entity->{$this->fieldName}[1]->entity->id(), $referenced_entities[1]->id());
    $this->assertEqual($entity->{$this->fieldName}[1]->entity->label(), $referenced_entities[1]->label());
  }

  /**
   * Creates two content and two config test entities.
   *
   * @return array
   *   An array of entity objects.
   */
  protected function getTestEntities() {
    $config_entity_1 = entity_create('config_test', array('id' => $this->randomMachineName(), 'label' => $this->randomMachineName()));
    $config_entity_1->save();
    $config_entity_2 = entity_create('config_test', array('id' => $this->randomMachineName(), 'label' => $this->randomMachineName()));
    $config_entity_2->save();

    $content_entity_1 = entity_create('entity_test', array('name' => $this->randomMachineName()));
    $content_entity_1->save();
    $content_entity_2 = entity_create('entity_test', array('name' => $this->randomMachineName()));
    $content_entity_2->save();

    return array(
      'config' => array(
        $config_entity_1,
        $config_entity_2,
      ),
      'content' => array(
        $content_entity_1,
        $content_entity_2,
      ),
    );
  }

}
