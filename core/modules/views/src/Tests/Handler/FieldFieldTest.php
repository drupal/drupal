<?php

/**
 * @file
 * Contains Drupal\views\Tests\Handler\FieldFieldTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Plugin\views\field\Field;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Provides some integration tests for the Field handler.
 *
 * @see \Drupal\views\Plugin\views\field\Field
 * @group views
 */
class FieldFieldTest extends ViewUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'entity_test', 'user'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_field_field_test', 'test_field_field_revision_test'];

  /**
   * The stored test entities.
   *
   * @var \Drupal\entity_test\Entity\EntityTest[]
   */
  protected $entities;

  /**
   * The stored revisionable test entities.
   *
   * @var \Drupal\entity_test\Entity\EntityTestRev[]
   */
  protected $entityRevision;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_rev');

    // Setup a field storage and field, but also change the views data for the
    // entity_test entity type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test',
      'type' => 'integer',
      'entity_type' => 'entity_test',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();

    $random_number = (string) 30856;
    for ($i = 0; $i < 5; $i++) {
      $this->entities[$i] = $entity = EntityTest::create([
        'bundle' => 'entity_test',
        'field_test' => $random_number[$i],
      ]);
      $entity->save();
    }

    // Setup some test data for entities with revisions.
    // We are testing both base field revisions and field config revisions.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test',
      'type' => 'integer',
      'entity_type' => 'entity_test_rev',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test_rev',
      'bundle' => 'entity_test_rev',
    ]);
    $field->save();

    $this->entityRevision = [];
    $this->entityRevision[0] = $entity = EntityTestRev::create([
      'name' => 'base value',
      'field_test' => 1,
    ]);
    $entity->save();

    $entity = clone $entity;
    $entity->setNewRevision(TRUE);
    $entity->name->value = 'revision value1';
    $entity->field_test->value = 2;
    $entity->save();
    $this->entityRevision[1] = $entity;

    $entity = clone $entity;
    $entity->setNewRevision(TRUE);
    $entity->name->value = 'revision value2';
    $entity->field_test->value = 3;
    $entity->save();
    $this->entityRevision[2] = $entity;

    $this->entityRevision[0] = $entity = EntityTestRev::create([
      'name' => 'next entity value',
      'field_test' => 4,
    ]);
    $entity->save();

    \Drupal::state()->set('entity_test.views_data', [
      'entity_test' => [
        'id' => [
          'field' => [
            'id' => 'field',
          ],
        ],
      ],
      'entity_test_rev_revision' => [
        'id' => [
          'field' => [
            'id' => 'field',
          ],
        ],
      ],
    ]);

    Views::viewsData()->clear();
  }

  /**
   * Tests the result of a view with base fields and configurable fields.
   */
  public function testSimpleExecute() {
    $executable = Views::getView('test_field_field_test');
    $executable->execute();

    $this->assertTrue($executable->field['id'] instanceof Field);
    $this->assertTrue($executable->field['field_test'] instanceof Field);

    $this->assertIdenticalResultset($executable,
      [
        ['id' => 1, 'field_test' => 3],
        ['id' => 2, 'field_test' => 0],
        ['id' => 3, 'field_test' => 8],
        ['id' => 4, 'field_test' => 5],
        ['id' => 5, 'field_test' => 6],
      ],
      ['id' => 'id', 'field_test' => 'field_test']
    );
  }

  /**
   * Tests the output of a view with base fields and configurable fields.
   */
  public function testSimpleRender() {
    $executable = Views::getView('test_field_field_test');
    $executable->execute();

    $this->assertEqual(1, $executable->getStyle()->getField(0, 'id'));
    $this->assertEqual(3, $executable->getStyle()->getField(0, 'field_test'));
    $this->assertEqual(2, $executable->getStyle()->getField(1, 'id'));
    $this->assertEqual(0, $executable->getStyle()->getField(1, 'field_test'));
    $this->assertEqual(3, $executable->getStyle()->getField(2, 'id'));
    $this->assertEqual(8, $executable->getStyle()->getField(2, 'field_test'));
    $this->assertEqual(4, $executable->getStyle()->getField(3, 'id'));
    $this->assertEqual(5, $executable->getStyle()->getField(3, 'field_test'));
    $this->assertEqual(5, $executable->getStyle()->getField(4, 'id'));
    $this->assertEqual(6, $executable->getStyle()->getField(4, 'field_test'));
  }

  /**
   * Tests the revision result.
   */
  public function testRevisionExecute() {
    $executable = Views::getView('test_field_field_revision_test');
    $executable->execute();

    $this->assertTrue($executable->field['name'] instanceof Field);
    $this->assertTrue($executable->field['field_test'] instanceof Field);

    $this->assertIdenticalResultset($executable,
      [
        ['id' => 1, 'field_test' => 1, 'revision_id' => 1, 'name' => 'base value'],
        ['id' => 1, 'field_test' => 2, 'revision_id' => 2, 'name' => 'revision value1'],
        ['id' => 1, 'field_test' => 3, 'revision_id' => 3, 'name' => 'revision value2'],
        ['id' => 2, 'field_test' => 4, 'revision_id' => 4, 'name' => 'next entity value'],
      ],
      ['entity_test_rev_revision_id' => 'id', 'revision_id' => 'revision_id', 'name' => 'name', 'field_test' => 'field_test']
    );
  }

  /**
   * Tests the output of a revision view with base fields and configurable fields.
   */
  public function testRevisionRender() {
    $executable = Views::getView('test_field_field_revision_test');
    $executable->execute();

    $this->assertEqual(1, $executable->getStyle()->getField(0, 'id'));
    $this->assertEqual(1, $executable->getStyle()->getField(0, 'revision_id'));
    $this->assertEqual(1, $executable->getStyle()->getField(0, 'field_test'));
    $this->assertEqual('base value', $executable->getStyle()->getField(0, 'name'));

    $this->assertEqual(1, $executable->getStyle()->getField(1, 'id'));
    $this->assertEqual(2, $executable->getStyle()->getField(1, 'revision_id'));
    $this->assertEqual(2, $executable->getStyle()->getField(1, 'field_test'));
    $this->assertEqual('revision value1', $executable->getStyle()->getField(1, 'name'));

    $this->assertEqual(1, $executable->getStyle()->getField(2, 'id'));
    $this->assertEqual(3, $executable->getStyle()->getField(2, 'revision_id'));
    $this->assertEqual(3, $executable->getStyle()->getField(2, 'field_test'));
    $this->assertEqual('revision value2', $executable->getStyle()->getField(2, 'name'));

    $this->assertEqual(2, $executable->getStyle()->getField(3, 'id'));
    $this->assertEqual(4, $executable->getStyle()->getField(3, 'revision_id'));
    $this->assertEqual(4, $executable->getStyle()->getField(3, 'field_test'));
    $this->assertEqual('next entity value', $executable->getStyle()->getField(3, 'name'));

  }

}
