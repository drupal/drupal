<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceFormatterTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests the formatters functionality.
 *
 * @group entity_reference
 */
class EntityReferenceFormatterTest extends EntityUnitTestBase {

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
  protected $fieldName = 'field_test';

  /**
   * The entity to be referenced in this test.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $referencedEntity = NULL;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('entity_reference');

  protected function setUp() {
    parent::setUp();

    entity_reference_create_instance($this->entityType, $this->bundle, $this->fieldName, 'Field test', $this->entityType);

    // Set up a field, so that the entity that'll be referenced bubbles up a
    // cache tag when rendering it entirely.
    entity_create('field_storage_config', array(
      'name' => 'body',
      'entity_type' => $this->entityType,
      'type' => 'text',
      'settings' => array(),
    ))->save();
    entity_create('field_instance_config', array(
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
      'field_name' => 'body',
      'label' => 'Body',
    ))->save();
    entity_get_display($this->entityType, $this->bundle, 'default')
      ->setComponent('body', array(
        'type' => 'text_default',
        'settings' => array(),
      ))
      ->save();

    entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
    ))->save();

    // Create the entity to be referenced.
    $this->referencedEntity = entity_create($this->entityType, array('name' => $this->randomMachineName()));
    $this->referencedEntity->body = array(
      'value' => '<p>Hello, world!</p>',
      'format' => 'full_html',
    );
    $this->referencedEntity->save();
  }

  /**
   * Assert unaccessible items don't change the data of the fields.
   */
  public function testAccess() {
    $field_name = $this->fieldName;

    $referencing_entity = entity_create($this->entityType, array('name' => $this->randomMachineName()));
    $referencing_entity->save();
    $referencing_entity->{$field_name}->entity = $this->referencedEntity;

    // Assert user doesn't have access to the entity.
    $this->assertFalse($this->referencedEntity->access('view'), 'Current user does not have access to view the referenced entity.');

    $formatter_manager = $this->container->get('plugin.manager.field.formatter');

    // Get all the existing formatters.
    foreach ($formatter_manager->getOptions('entity_reference') as $formatter => $name) {
      // Set formatter type for the 'full' view mode.
      entity_get_display($this->entityType, $this->bundle, 'default')
        ->setComponent($field_name, array(
          'type' => $formatter,
        ))
        ->save();

      // Invoke entity view.
      entity_view($referencing_entity, 'default');

      // Verify the un-accessible item still exists.
      $this->assertEqual($referencing_entity->{$field_name}->target_id, $this->referencedEntity->id(), format_string('The un-accessible item still exists after @name formatter was executed.', array('@name' => $name)));
    }
  }

  /**
   * Tests the ID formatter.
   */
  public function testIdFormatter() {
    $formatter = 'entity_reference_entity_id';
    $field_name = $this->fieldName;

    // Create the entity that will have the entity reference field.
    $referencing_entity = entity_create($this->entityType, array('name' => $this->randomMachineName()));
    $referencing_entity->save();
    $referencing_entity->{$field_name}->entity = $this->referencedEntity;
    $referencing_entity->{$field_name}->access = TRUE;

    // Build the renderable array for the entity reference field.
    $items = $referencing_entity->get($field_name);
    $build = $items->view(array('type' => $formatter));

    $this->assertEqual($build[0]['#markup'], $this->referencedEntity->id(), format_string('The markup returned by the @formatter formatter is correct.', array('@formatter' => $formatter)));
    $expected_cache_tags = array(
      $this->entityType => array($this->referencedEntity->id()),
    );
    $this->assertEqual($build[0]['#cache']['tags'], $expected_cache_tags, format_string('The @formatter formatter has the expected cache tags.', array('@formatter' => $formatter)));

  }

  /**
   * Tests the entity formatter.
   */
  public function testEntityFormatter() {
    $formatter = 'entity_reference_entity_view';
    $field_name = $this->fieldName;

    // Create the entity that will have the entity reference field.
    $referencing_entity = entity_create($this->entityType, array('name' => $this->randomMachineName()));
    $referencing_entity->save();
    $referencing_entity->{$field_name}->entity = $this->referencedEntity;
    $referencing_entity->{$field_name}->access = TRUE;

    // Build the renderable array for the entity reference field.
    $items = $referencing_entity->get($field_name);
    $build = $items->view(array('type' => $formatter));

    $expected_rendered_name_field = '<div class="field field-entity-test--name field-name-name field-type-string field-label-hidden">
    <div class="field-items">
          <div class="field-item">' . $this->referencedEntity->label() . '</div>
      </div>
</div>
';
    $expected_rendered_body_field = '<div class="field field-entity-test--body field-name-body field-type-text field-label-above">
      <div class="field-label">Body:&nbsp;</div>
    <div class="field-items">
          <div class="field-item"><p>Hello, world!</p></div>
      </div>
</div>
';
    drupal_render($build[0]);
    $this->assertEqual($build[0]['#markup'], 'default | ' . $this->referencedEntity->label() .  $expected_rendered_name_field . $expected_rendered_body_field, format_string('The markup returned by the @formatter formatter is correct.', array('@formatter' => $formatter)));
    $expected_cache_tags = array(
      $this->entityType . '_view' => TRUE,
      $this->entityType => array($this->referencedEntity->id()),
      'filter_format' => array('full_html' => 'full_html'),
    );
    $this->assertEqual($build[0]['#cache']['tags'], $expected_cache_tags, format_string('The @formatter formatter has the expected cache tags.', array('@formatter' => $formatter)));
  }

}
