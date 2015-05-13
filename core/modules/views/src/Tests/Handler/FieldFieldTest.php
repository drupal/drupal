<?php

/**
 * @file
 * Contains Drupal\views\Tests\Handler\FieldFieldTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\Entity\User;
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
  public static $testViews = ['test_field_field_test', 'test_field_alias_test', 'test_field_field_complex_test', 'test_field_field_revision_test', 'test_field_field_revision_complex_test'];

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
   * Stores a couple of test users.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $testUsers;

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_rev');

    // Bypass any field access.
    $this->adminUser = User::create();
    $this->adminUser->save();
    $this->container->get('current_user')->setAccount($this->adminUser);

    $this->testUsers = [];
    for ($i = 0; $i < 5; $i++) {
      $this->testUsers[$i] = User::create([
        'name' => 'test ' . $i,
        'timezone' => User::getAllowedTimezones()[$i],
      ]);
      $this->testUsers[$i]->save();
    }

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

    $field_storage_multiple = FieldStorageConfig::create([
      'field_name' => 'field_test_multiple',
      'type' => 'integer',
      'entity_type' => 'entity_test',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage_multiple->save();

    $field_multiple = FieldConfig::create([
      'field_name' => 'field_test_multiple',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field_multiple->save();

    $random_number = (string) 30856;
    $random_number_multiple = (string) 1370359990;
    for ($i = 0; $i < 5; $i++) {
      $this->entities[$i] = $entity = EntityTest::create([
        'bundle' => 'entity_test',
        'name' => 'test ' . $i,
        'field_test' => $random_number[$i],
        'field_test_multiple' => [$random_number_multiple[$i * 2], $random_number_multiple[$i * 2 + 1]],
        'user_id' => $this->testUsers[$i]->id(),
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

    $field_storage_multiple = FieldStorageConfig::create([
      'field_name' => 'field_test_multiple',
      'type' => 'integer',
      'entity_type' => 'entity_test_rev',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage_multiple->save();

    $field_multiple = FieldConfig::create([
      'field_name' => 'field_test_multiple',
      'entity_type' => 'entity_test_rev',
      'bundle' => 'entity_test_rev',
    ]);
    $field_multiple->save();

    $this->entityRevision = [];
    $this->entityRevision[0] = $entity = EntityTestRev::create([
      'name' => 'base value',
      'field_test' => 1,
      'field_test_multiple' => [1, 3, 7],
      'user_id' =>  $this->testUsers[0]->id(),
    ]);
    $entity->save();
    $original_entity = clone $entity;

    $entity = clone $original_entity;
    $entity->setNewRevision(TRUE);
    $entity->name->value = 'revision value1';
    $entity->field_test->value = 2;
    $entity->field_test_multiple[0]->value = 0;
    $entity->field_test_multiple[1]->value = 3;
    $entity->field_test_multiple[2]->value = 5;
    $entity->user_id->target_id = $this->testUsers[1]->id();
    $entity->save();
    $this->entityRevision[1] = $entity;

    $entity = clone $original_entity;
    $entity->setNewRevision(TRUE);
    $entity->name->value = 'revision value2';
    $entity->field_test->value = 3;
    $entity->field_test_multiple[0]->value = 9;
    $entity->field_test_multiple[1]->value = 9;
    $entity->field_test_multiple[2]->value = 9;
    $entity->user_id->target_id = $this->testUsers[2]->id();
    $entity->save();
    $this->entityRevision[2] = $entity;

    $this->entityRevision[3] = $entity = EntityTestRev::create([
      'name' => 'next entity value',
      'field_test' => 4,
      'field_test_multiple' => [2, 9, 9],
      'user_id' => $this->testUsers[3]->id(),
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
    // @todo Switch this assertion to assertIdentical('', ...) when
    //   https://www.drupal.org/node/2488006 gets fixed.
    $this->assertEqual(0, $executable->getStyle()->getField(1, 'field_test'));
    $this->assertEqual(3, $executable->getStyle()->getField(2, 'id'));
    $this->assertEqual(8, $executable->getStyle()->getField(2, 'field_test'));
    $this->assertEqual(4, $executable->getStyle()->getField(3, 'id'));
    $this->assertEqual(5, $executable->getStyle()->getField(3, 'field_test'));
    $this->assertEqual(5, $executable->getStyle()->getField(4, 'id'));
    $this->assertEqual(6, $executable->getStyle()->getField(4, 'field_test'));
  }

  /**
   * Tests the result of a view with complex field configuration.
   *
   * A complex field configuration contains multiple times the same field, with
   * different delta limit / offset.
   */
  public function testFieldAlias() {
    $executable = Views::getView('test_field_alias_test');
    $executable->execute();

    $this->assertTrue($executable->field['id'] instanceof Field);
    $this->assertTrue($executable->field['name'] instanceof Field);
    $this->assertTrue($executable->field['name_alias'] instanceof Field);

    $this->assertIdenticalResultset($executable,
      [
        ['id' => 1, 'name' => 'test 0', 'name_alias' => 'test 0'],
        ['id' => 2, 'name' => 'test 1', 'name_alias' => 'test 1'],
        ['id' => 3, 'name' => 'test 2', 'name_alias' => 'test 2'],
        ['id' => 4, 'name' => 'test 3', 'name_alias' => 'test 3'],
        ['id' => 5, 'name' => 'test 4', 'name_alias' => 'test 4'],
      ],
      ['id' => 'id', 'name' => 'name', 'name_alias' => 'name_alias']
    );
  }

  /**
   * Tests the result of a view with complex field configuration.
   *
   * A complex field configuration contains multiple times the same field, with
   * different delta limit / offset.
   */
  public function testFieldAliasRender() {
    $executable = Views::getView('test_field_alias_test');
    $executable->execute();

    for ($i = 0; $i < 5; $i++) {
      $this->assertEqual($i + 1, $executable->getStyle()->getField($i, 'id'));
      $this->assertEqual('test ' . $i, $executable->getStyle()->getField($i, 'name'));
      $entity = EntityTest::load($i + 1);
      $this->assertEqual('<a href="' . $entity->url() . '" hreflang="' . $entity->language()->getId() . '">test ' . $i . '</a>', $executable->getStyle()->getField($i, 'name_alias'));
    }
  }

  /**
   * Tests the result of a view with complex field configuration.
   *
   * A complex field configuration contains multiple times the same field, with
   * different delta limit / offset.
   */
  public function testComplexExecute() {
    $executable = Views::getView('test_field_field_complex_test');
    $executable->execute();

    $timezones = [];
    foreach ($this->testUsers as $user) {
      $timezones[] = $user->getTimeZone();
    }

    $this->assertTrue($executable->field['field_test_multiple'] instanceof Field);
    $this->assertTrue($executable->field['field_test_multiple_1'] instanceof Field);
    $this->assertTrue($executable->field['field_test_multiple_2'] instanceof Field);
    $this->assertTrue($executable->field['timezone'] instanceof Field);

    $this->assertIdenticalResultset($executable,
      [
        ['timezone' => $timezones[0], 'field_test_multiple' => [1, 3], 'field_test_multiple_1' => [1, 3], 'field_test_multiple_2' => [1, 3]],
        ['timezone' => $timezones[1], 'field_test_multiple' => [7, 0], 'field_test_multiple_1' => [7, 0], 'field_test_multiple_2' => [7, 0]],
        ['timezone' => $timezones[2], 'field_test_multiple' => [3, 5], 'field_test_multiple_1' => [3, 5], 'field_test_multiple_2' => [3, 5]],
        ['timezone' => $timezones[3], 'field_test_multiple' => [9, 9], 'field_test_multiple_1' => [9, 9], 'field_test_multiple_2' => [9, 9]],
        ['timezone' => $timezones[4], 'field_test_multiple' => [9, 0], 'field_test_multiple_1' => [9, 0], 'field_test_multiple_2' => [9, 0]],
      ],
      ['timezone' => 'timezone', 'field_test_multiple' => 'field_test_multiple', 'field_test_multiple_1' => 'field_test_multiple_1', 'field_test_multiple_2' => 'field_test_multiple_2']
    );
  }

  /**
   * Tests the output of a view with complex field configuration.
   */
  public function testComplexRender() {
    $executable = Views::getView('test_field_field_complex_test');
    $executable->execute();

    $this->assertEqual($this->testUsers[0]->getTimeZone(), $executable->getStyle()->getField(0, 'timezone'));
    $this->assertEqual("1, 3", $executable->getStyle()->getField(0, 'field_test_multiple'));
    $this->assertEqual("1", $executable->getStyle()->getField(0, 'field_test_multiple_1'));
    $this->assertEqual("3", $executable->getStyle()->getField(0, 'field_test_multiple_2'));

    $this->assertEqual($this->testUsers[1]->getTimeZone(), $executable->getStyle()->getField(1, 'timezone'));
    $this->assertEqual("7, 0", $executable->getStyle()->getField(1, 'field_test_multiple'));
    $this->assertEqual("7", $executable->getStyle()->getField(1, 'field_test_multiple_1'));
    $this->assertEqual("0", $executable->getStyle()->getField(1, 'field_test_multiple_2'));

    $this->assertEqual($this->testUsers[2]->getTimeZone(), $executable->getStyle()->getField(2, 'timezone'));
    $this->assertEqual("3, 5", $executable->getStyle()->getField(2, 'field_test_multiple'));
    $this->assertEqual("3", $executable->getStyle()->getField(2, 'field_test_multiple_1'));
    $this->assertEqual("5", $executable->getStyle()->getField(2, 'field_test_multiple_2'));

    $this->assertEqual($this->testUsers[3]->getTimeZone(), $executable->getStyle()->getField(3, 'timezone'));
    $this->assertEqual("9, 9", $executable->getStyle()->getField(3, 'field_test_multiple'));
    $this->assertEqual("9", $executable->getStyle()->getField(3, 'field_test_multiple_1'));
    $this->assertEqual("9", $executable->getStyle()->getField(3, 'field_test_multiple_2'));

    $this->assertEqual($this->testUsers[4]->getTimeZone(), $executable->getStyle()->getField(4, 'timezone'));
    $this->assertEqual("9, 0", $executable->getStyle()->getField(4, 'field_test_multiple'));
    $this->assertEqual("9", $executable->getStyle()->getField(4, 'field_test_multiple_1'));
    $this->assertEqual("0", $executable->getStyle()->getField(4, 'field_test_multiple_2'));
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
   * Tests the output of a revision view with base and configurable fields.
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

  /**
   * Tests the result set of a complex revision view.
   */
  public function testRevisionComplexExecute() {
    $executable = Views::getView('test_field_field_revision_complex_test');
    $executable->execute();

    $timezones = [];
    foreach ($this->testUsers as $user) {
      $timezones[] = $user->getTimeZone();
    }

    $this->assertTrue($executable->field['id'] instanceof Field);
    $this->assertTrue($executable->field['revision_id'] instanceof Field);
    $this->assertTrue($executable->field['timezone'] instanceof Field);
    $this->assertTrue($executable->field['field_test_multiple'] instanceof Field);
    $this->assertTrue($executable->field['field_test_multiple_1'] instanceof Field);
    $this->assertTrue($executable->field['field_test_multiple_2'] instanceof Field);

    $this->assertIdenticalResultset($executable,
      [
        ['id' => 1, 'field_test' => 1, 'revision_id' => 1, 'uid' => $this->testUsers[0]->id(), 'timezone' => $timezones[0], 'field_test_multiple' => [1, 3, 7], 'field_test_multiple_1' => [1, 3, 7], 'field_test_multiple_2' => [1, 3, 7]],
        ['id' => 1, 'field_test' => 2, 'revision_id' => 2, 'uid' => $this->testUsers[1]->id(), 'timezone' => $timezones[1], 'field_test_multiple' => [0, 3, 5], 'field_test_multiple_1' => [0, 3, 5], 'field_test_multiple_2' => [0, 3, 5]],
        ['id' => 1, 'field_test' => 3, 'revision_id' => 3, 'uid' => $this->testUsers[2]->id(), 'timezone' => $timezones[2], 'field_test_multiple' => [9, 9, 9], 'field_test_multiple_1' => [9, 9, 9], 'field_test_multiple_2' => [9, 9, 9]],
        ['id' => 2, 'field_test' => 4, 'revision_id' => 4, 'uid' => $this->testUsers[3]->id(), 'timezone' => $timezones[3], 'field_test_multiple' => [2, 9, 9], 'field_test_multiple_1' => [2, 9, 9], 'field_test_multiple_2' => [2, 9, 9]],
      ],
      ['entity_test_rev_revision_id' => 'id', 'revision_id' => 'revision_id', 'users_field_data_entity_test_rev_revision_uid' => 'uid', 'timezone' => 'timezone', 'field_test_multiple' => 'field_test_multiple', 'field_test_multiple_1' => 'field_test_multiple_1', 'field_test_multiple_2' => 'field_test_multiple_2']
    );
  }

  /**
   * Tests the output of a revision view with base fields and configurable fields.
   */
  public function testRevisionComplexRender() {
    $executable = Views::getView('test_field_field_revision_complex_test');
    $executable->execute();

    $this->assertEqual(1, $executable->getStyle()->getField(0, 'id'));
    $this->assertEqual(1, $executable->getStyle()->getField(0, 'revision_id'));
    $this->assertEqual($this->testUsers[0]->getTimeZone(), $executable->getStyle()->getField(0, 'timezone'));
    $this->assertEqual('1, 3, 7', $executable->getStyle()->getField(0, 'field_test_multiple'));
    $this->assertEqual('1', $executable->getStyle()->getField(0, 'field_test_multiple_1'));
    $this->assertEqual('3, 7', $executable->getStyle()->getField(0, 'field_test_multiple_2'));

    $this->assertEqual(1, $executable->getStyle()->getField(1, 'id'));
    $this->assertEqual(2, $executable->getStyle()->getField(1, 'revision_id'));
    $this->assertEqual($this->testUsers[1]->getTimeZone(), $executable->getStyle()->getField(1, 'timezone'));
    $this->assertEqual('0, 3, 5', $executable->getStyle()->getField(1, 'field_test_multiple'));
    $this->assertEqual('0', $executable->getStyle()->getField(1, 'field_test_multiple_1'));
    $this->assertEqual('3, 5', $executable->getStyle()->getField(1, 'field_test_multiple_2'));

    $this->assertEqual(1, $executable->getStyle()->getField(2, 'id'));
    $this->assertEqual(3, $executable->getStyle()->getField(2, 'revision_id'));
    $this->assertEqual($this->testUsers[2]->getTimeZone(), $executable->getStyle()->getField(2, 'timezone'));
    $this->assertEqual('9, 9, 9', $executable->getStyle()->getField(2, 'field_test_multiple'));
    $this->assertEqual('9', $executable->getStyle()->getField(2, 'field_test_multiple_1'));
    $this->assertEqual('9, 9', $executable->getStyle()->getField(2, 'field_test_multiple_2'));

    $this->assertEqual(2, $executable->getStyle()->getField(3, 'id'));
    $this->assertEqual(4, $executable->getStyle()->getField(3, 'revision_id'));
    $this->assertEqual($this->testUsers[3]->getTimeZone(), $executable->getStyle()->getField(3, 'timezone'));
    $this->assertEqual('2, 9, 9', $executable->getStyle()->getField(3, 'field_test_multiple'));
    $this->assertEqual('2', $executable->getStyle()->getField(3, 'field_test_multiple_1'));
    $this->assertEqual('9, 9', $executable->getStyle()->getField(3, 'field_test_multiple_2'));
  }

  /**
   * Tests that a field not available for every bundle is rendered as empty.
   */
  public function testMissingBundleFieldRender() {
    // Create a new bundle not having the test field attached.
    $bundle = $this->randomMachineName();
    entity_test_create_bundle($bundle);

    $entity = EntityTest::create([
      'type' => $bundle,
      'name' => $this->randomString(),
      'user_id' => $this->testUsers[0]->id(),
    ]);
    $entity->save();

    $executable = Views::getView('test_field_field_test');
    $executable->execute();

    // @todo Switch this assertion to assertIdentical('', ...) when
    //   https://www.drupal.org/node/2488006 gets fixed.
    $this->assertEqual(0, $executable->getStyle()->getField(1, 'field_test'));
  }

}
