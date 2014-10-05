<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\Views\EntityReferenceRelationshipTest.
 */

namespace Drupal\entity_reference\Tests\Views;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests entity reference relationship data.
 *
 * @group entity_reference
 * @see entity_reference_field_views_data()
 */
class EntityReferenceRelationshipTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_reference_view');

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('user', 'field', 'entity_test', 'entity_reference', 'views', 'entity_reference_test_views');

  /**
   * The entity_test entities used by the test.
   *
   * @var array
   */
  protected $entities = array();

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');

    ViewTestData::createTestViews(get_class($this), array('entity_reference_test_views'));

    $field_storage = FieldStorageConfig::create(array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'type' => 'entity_reference',
      'settings' => array(
        'target_type' => 'entity_test',
      ),
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ));
    $field_storage->save();

    $field = FieldConfig::create(array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
      'settings' => array(
        'handler' => 'default',
        'handler_settings' => array(),
      ),
    ));
    $field->save();

    // Create some test entities which link each other.
    $entity_storage= \Drupal::entityManager()->getStorage('entity_test');
    $referenced_entity = $entity_storage->create(array());
    $referenced_entity->save();
    $this->entities[$referenced_entity->id()] = $referenced_entity;

    $entity = $entity_storage->create(array());
    $entity->field_test->target_id = $referenced_entity->id();
    $entity->save();
    $this->assertEqual($entity->field_test[0]->entity->id(), $referenced_entity->id());
    $this->entities[$entity->id()] = $entity;

    $entity = $entity_storage->create(array());
    $entity->field_test->target_id = $referenced_entity->id();
    $entity->save();
    $this->assertEqual($entity->field_test[0]->entity->id(), $referenced_entity->id());
    $this->entities[$entity->id()] = $entity;
  }

  /**
   * Tests using the views relationship.
   */
  public function testRelationship() {
    // Check just the generated views data.
    $views_data_field_test = Views::viewsData()->get('entity_test__field_test');
    $this->assertEqual($views_data_field_test['field_test']['relationship']['id'], 'standard');
    $this->assertEqual($views_data_field_test['field_test']['relationship']['base'], 'entity_test');
    $this->assertEqual($views_data_field_test['field_test']['relationship']['base field'], 'id');
    $this->assertEqual($views_data_field_test['field_test']['relationship']['relationship field'], 'field_test_target_id');

    // Check the backwards reference.
    $views_data_entity_test = Views::viewsData()->get('entity_test');
    $this->assertEqual($views_data_entity_test['reverse__entity_test__field_test']['relationship']['id'], 'entity_reverse');
    $this->assertEqual($views_data_entity_test['reverse__entity_test__field_test']['relationship']['base'], 'entity_test');
    $this->assertEqual($views_data_entity_test['reverse__entity_test__field_test']['relationship']['base field'], 'id');
    $this->assertEqual($views_data_entity_test['reverse__entity_test__field_test']['relationship']['field table'], 'entity_test__field_test');
    $this->assertEqual($views_data_entity_test['reverse__entity_test__field_test']['relationship']['field field'], 'field_test_target_id');


    // Check an actual test view.
    $view = Views::getView('test_entity_reference_view');
    $this->executeView($view);

    foreach (array_keys($view->result) as $index) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEqual($view->result[$index]->id, $this->entities[$index + 1]->id());
      // Test the forward relationship.
      // The second and third entity refer to the first one.
      // The value key on the result will be in the format
      // BASE_TABLE_FIELD_NAME.
      $this->assertEqual($view->result[$index]->entity_test_entity_test__field_test_id, $index == 0 ? NULL : 1);

      if ($index > 0) {
        // Test that the correct relationship entity is on the row.
        $this->assertEqual($view->result[$index]->_relationship_entities['test_relationship']->id(), 1);
      }
    }

    $view->destroy();
    $this->executeView($view, 'embed_1');

    foreach (array_keys($view->result) as $index) {
      $this->assertEqual($view->result[$index]->id, $this->entities[$index + 1]->id());
      // The second and third entity refer to the first one.
      $this->assertEqual($view->result[$index]->entity_test_entity_test__field_test_id, $index == 0 ? NULL : 1);
    }
  }

}
