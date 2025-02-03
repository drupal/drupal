<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\EntityReference\Views;

use Drupal\entity_test\Entity\EntityTestMulChanged;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests entity reference relationship data.
 *
 * @group entity_reference
 *
 * @see \Drupal\views\Hook\ViewsViewsHooks::fieldViewsData()
 */
class EntityReferenceRelationshipTest extends ViewsKernelTestBase {

  use EntityReferenceFieldCreationTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = [
    'test_entity_reference_entity_test_view',
    'test_entity_reference_entity_test_view_long',
    'test_entity_reference_reverse_entity_test_view',
    'test_entity_reference_entity_test_mul_view',
    'test_entity_reference_reverse_entity_test_mul_view',
    'test_entity_reference_group_by_empty_relationships',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'field',
    'entity_test',
    'views',
    'entity_reference_test_views',
  ];

  /**
   * The entity_test entities used by the test.
   *
   * @var array
   */
  protected $entities = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('user_role');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('entity_test_mul_changed');

    // Create reference from entity_test to entity_test_mul.
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_data', 'field_test_data', 'entity_test_mul');

    // Create reference from entity_test_mul to entity_test.
    $this->createEntityReferenceField('entity_test_mul', 'entity_test_mul', 'field_data_test', 'field_data_test', 'entity_test');

    // Create another field for testing with a long name. So its storage name
    // will become hashed. Use entity_test_mul_changed, so the resulting field
    // tables created will be greater than 48 chars long.
    // @see \Drupal\Core\Entity\Sql\DefaultTableMapping::generateFieldTableName()
    $this->createEntityReferenceField('entity_test_mul_changed', 'entity_test_mul_changed', 'field_test_data_with_a_long_name', 'field_test_data_with_a_long_name', 'entity_test');

    // Create reference from entity_test_mul to entity_test cardinality: infinite.
    $this->createEntityReferenceField('entity_test_mul', 'entity_test_mul', 'field_data_test_unlimited', 'field_data_test_unlimited', 'entity_test', 'default', [], FieldStorageConfig::CARDINALITY_UNLIMITED);

    ViewTestData::createTestViews(static::class, ['entity_reference_test_views']);
  }

  /**
   * Tests using the views relationship.
   */
  public function testNoDataTableRelationship(): void {

    // Create some test entities which link each other.
    $referenced_entity = EntityTestMul::create();
    $referenced_entity->save();

    $entity = EntityTest::create();
    $entity->field_test_data->target_id = $referenced_entity->id();
    $entity->save();
    $this->assertEquals($referenced_entity->id(), $entity->field_test_data[0]->entity->id());
    $this->entities[] = $entity;

    $entity = EntityTest::create();
    $entity->field_test_data->target_id = $referenced_entity->id();
    $entity->save();
    $this->assertEquals($referenced_entity->id(), $entity->field_test_data[0]->entity->id());
    $this->entities[] = $entity;

    Views::viewsData()->clear();

    // Check the generated views data.
    $views_data = Views::viewsData()->get('entity_test__field_test_data');
    $this->assertEquals('standard', $views_data['field_test_data']['relationship']['id']);
    $this->assertEquals('entity_test_mul_property_data', $views_data['field_test_data']['relationship']['base']);
    $this->assertEquals('id', $views_data['field_test_data']['relationship']['base field']);
    $this->assertEquals('field_test_data_target_id', $views_data['field_test_data']['relationship']['relationship field']);
    $this->assertEquals('entity_test_mul', $views_data['field_test_data']['relationship']['entity type']);

    // Check the backwards reference.
    $views_data = Views::viewsData()->get('entity_test_mul_property_data');
    $this->assertEquals('entity_reverse', $views_data['reverse__entity_test__field_test_data']['relationship']['id']);
    $this->assertEquals('entity_test', $views_data['reverse__entity_test__field_test_data']['relationship']['base']);
    $this->assertEquals('id', $views_data['reverse__entity_test__field_test_data']['relationship']['base field']);
    $this->assertEquals('entity_test__field_test_data', $views_data['reverse__entity_test__field_test_data']['relationship']['field table']);
    $this->assertEquals('field_test_data_target_id', $views_data['reverse__entity_test__field_test_data']['relationship']['field field']);
    $this->assertEquals('field_test_data', $views_data['reverse__entity_test__field_test_data']['relationship']['field_name']);
    $this->assertEquals('entity_test', $views_data['reverse__entity_test__field_test_data']['relationship']['entity_type']);
    $this->assertEquals(['field' => 'deleted', 'value' => 0, 'numeric' => TRUE], $views_data['reverse__entity_test__field_test_data']['relationship']['join_extra'][0]);

    // Check an actual test view.
    $view = Views::getView('test_entity_reference_entity_test_view');
    $this->executeView($view);
    /** @var \Drupal\views\ResultRow $row */
    foreach ($view->result as $index => $row) {
      // Check that the actual ID of the entity is the expected one.
      $this->assertEquals($this->entities[$index]->id(), $row->id);

      // Also check that we have the correct result entity.
      $this->assertEquals($this->entities[$index]->id(), $row->_entity->id());

      // Test the forward relationship.
      $this->assertEquals(1, $row->entity_test_mul_property_data_entity_test__field_test_data_i);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals(1, $row->_relationship_entities['field_test_data']->id());
      $this->assertEquals('entity_test_mul', $row->_relationship_entities['field_test_data']->bundle());
    }

    // Check the backwards reference view.
    $view = Views::getView('test_entity_reference_reverse_entity_test_view');
    $this->executeView($view);
    /** @var \Drupal\views\ResultRow $row */
    foreach ($view->result as $index => $row) {
      $this->assertEquals(1, $row->id);
      $this->assertEquals(1, $row->_entity->id());

      // Test the backwards relationship.
      $this->assertEquals($this->entities[$index]->id(), $row->field_test_data_entity_test_mul_property_data_id);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals($this->entities[$index]->id(), $row->_relationship_entities['reverse__entity_test__field_test_data']->id());
      $this->assertEquals('entity_test', $row->_relationship_entities['reverse__entity_test__field_test_data']->bundle());
    }
  }

  /**
   * Tests views data generated for relationship.
   *
   * @see entity_reference_field_views_data()
   */
  public function testDataTableRelationship(): void {

    // Create some test entities which link each other.
    $referenced_entity = EntityTest::create();
    $referenced_entity->save();

    $entity = EntityTestMul::create();
    $entity->field_data_test->target_id = $referenced_entity->id();
    $entity->save();
    $this->assertEquals($referenced_entity->id(), $entity->field_data_test[0]->entity->id());
    $this->entities[] = $entity;

    $entity = EntityTestMul::create();
    $entity->field_data_test->target_id = $referenced_entity->id();
    $entity->save();
    $this->assertEquals($referenced_entity->id(), $entity->field_data_test[0]->entity->id());
    $this->entities[] = $entity;

    Views::viewsData()->clear();

    // Check the generated views data.
    $views_data = Views::viewsData()->get('entity_test_mul__field_data_test');
    $this->assertEquals('standard', $views_data['field_data_test']['relationship']['id']);
    $this->assertEquals('entity_test', $views_data['field_data_test']['relationship']['base']);
    $this->assertEquals('id', $views_data['field_data_test']['relationship']['base field']);
    $this->assertEquals('field_data_test_target_id', $views_data['field_data_test']['relationship']['relationship field']);
    $this->assertEquals('entity_test', $views_data['field_data_test']['relationship']['entity type']);

    // Check the backwards reference.
    $views_data = Views::viewsData()->get('entity_test');
    $this->assertEquals('entity_reverse', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['id']);
    $this->assertEquals('entity_test_mul_property_data', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['base']);
    $this->assertEquals('id', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['base field']);
    $this->assertEquals('entity_test_mul__field_data_test', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['field table']);
    $this->assertEquals('field_data_test_target_id', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['field field']);
    $this->assertEquals('field_data_test', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['field_name']);
    $this->assertEquals('entity_test_mul', $views_data['reverse__entity_test_mul__field_data_test']['relationship']['entity_type']);
    $this->assertEquals(['field' => 'deleted', 'value' => 0, 'numeric' => TRUE], $views_data['reverse__entity_test_mul__field_data_test']['relationship']['join_extra'][0]);

    // Check an actual test view.
    $view = Views::getView('test_entity_reference_entity_test_mul_view');
    $this->executeView($view);
    /** @var \Drupal\views\ResultRow $row */
    foreach ($view->result as $index => $row) {
      // Check that the actual ID of the entity is the expected one.
      $this->assertEquals($this->entities[$index]->id(), $row->id);

      // Also check that we have the correct result entity.
      $this->assertEquals($this->entities[$index]->id(), $row->_entity->id());

      // Test the forward relationship.
      $this->assertEquals(1, $row->entity_test_entity_test_mul__field_data_test_id);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals(1, $row->_relationship_entities['field_data_test']->id());
      $this->assertEquals('entity_test', $row->_relationship_entities['field_data_test']->bundle());

    }

    // Check the backwards reference view.
    $view = Views::getView('test_entity_reference_reverse_entity_test_mul_view');
    $this->executeView($view);
    /** @var \Drupal\views\ResultRow $row */
    foreach ($view->result as $index => $row) {
      $this->assertEquals(1, $row->id);
      $this->assertEquals(1, $row->_entity->id());

      // Test the backwards relationship.
      $this->assertEquals($this->entities[$index]->id(), $row->field_data_test_entity_test_id);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals($this->entities[$index]->id(), $row->_relationship_entities['reverse__entity_test_mul__field_data_test']->id());
      $this->assertEquals('entity_test_mul', $row->_relationship_entities['reverse__entity_test_mul__field_data_test']->bundle());
    }
  }

  /**
   * Tests views data generated for relationship.
   *
   * @see entity_reference_field_views_data()
   */
  public function testDataTableRelationshipWithLongFieldName(): void {
    // Create some test entities which link each other.
    $referenced_entity = EntityTest::create();
    $referenced_entity->save();

    $entity = EntityTestMulChanged::create();
    $entity->field_test_data_with_a_long_name->target_id = $referenced_entity->id();
    $entity->save();
    $this->entities[] = $entity;

    $entity = EntityTestMulChanged::create();
    $entity->field_test_data_with_a_long_name->target_id = $referenced_entity->id();
    $entity->save();
    $this->entities[] = $entity;

    Views::viewsData()->clear();

    // Check an actual test view.
    $view = Views::getView('test_entity_reference_entity_test_view_long');
    $this->executeView($view);
    /** @var \Drupal\views\ResultRow $row */
    foreach ($view->result as $index => $row) {
      // Check that the actual ID of the entity is the expected one.
      $this->assertEquals($this->entities[$index]->id(), $row->id);

      // Also check that we have the correct result entity.
      $this->assertEquals($this->entities[$index]->id(), $row->_entity->id());

      // Test the forward relationship.
      // $this->assertEquals(1, $row->entity_test_entity_test_mul__field_data_test_id);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals(1, $row->_relationship_entities['field_test_data_with_a_long_name']->id());
      $this->assertEquals('entity_test', $row->_relationship_entities['field_test_data_with_a_long_name']->bundle());

    }
  }

  /**
   * Tests group by with optional and empty relationship.
   */
  public function testGroupByWithEmptyRelationships(): void {
    $entities = [];
    // Create 4 entities with name1 and 3 entities with name2.
    for ($i = 1; $i <= 4; $i++) {
      $entity = [
        'name' => 'name' . $i,
      ];
      $entity = EntityTest::create($entity);
      $entities[] = $entity;
      $entity->save();
    }

    $entity = EntityTestMul::create([
      'name' => 'name1',
    ]);
    $entity->field_data_test_unlimited = [
      ['target_id' => $entities[0]->id()],
      ['target_id' => $entities[1]->id()],
      ['target_id' => $entities[2]->id()],
    ];
    $entity->save();

    $entity = EntityTestMul::create([
      'name' => 'name2',
    ]);
    $entity->field_data_test_unlimited = [
      ['target_id' => $entities[0]->id()],
      ['target_id' => $entities[1]->id()],
    ];
    $entity->save();

    $entity = EntityTestMul::create([
      'name' => 'name3',
    ]);
    $entity->field_data_test_unlimited->target_id = $entities[0]->id();
    $entity->save();

    $view = Views::getView('test_entity_reference_group_by_empty_relationships');
    $this->executeView($view);
    $this->assertCount(4, $view->result);
    // First three results should contain a reference from EntityTestMul.
    $this->assertNotEmpty($view->getStyle()->getField(0, 'name_2'));
    $this->assertNotEmpty($view->getStyle()->getField(1, 'name_2'));
    $this->assertNotEmpty($view->getStyle()->getField(2, 'name_2'));
    // Fourth result has no reference from EntityTestMul hence the output for
    // should be empty.
    $this->assertEquals('', $view->getStyle()->getField(3, 'name_2'));

    $fields = $view->field;
    // Check getValue for reference with a value. The first 3 rows reference
    // EntityTestMul, so have value 'name1'.
    $this->assertEquals('name1', $fields['name_2']->getValue($view->result[0]));
    $this->assertEquals('name1', $fields['name_2']->getValue($view->result[1]));
    $this->assertEquals('name1', $fields['name_2']->getValue($view->result[2]));
    // Ensure getValue works on empty references.
    $this->assertNull($fields['name_2']->getValue($view->result[3]));
  }

  /**
   * Test that config entities don't get relationships added.
   */
  public function testEntityReferenceConfigEntity(): void {
    // Create reference from entity_test to a config entity.
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_config_entity', 'field_test_config_entity', 'user_role');
    Views::viewsData()->clear();
    $views_data = Views::viewsData()->getAll();
    // Test that a relationship got added for content entities but not config
    // entities.
    $this->assertTrue(isset($views_data['entity_test__field_test_data']['field_test_data']['relationship']));
    $this->assertFalse(isset($views_data['entity_test__field_test_config_entity']['field_test_config_entity']['relationship']));
  }

}
