<?php

namespace Drupal\system\Tests\KeyValueStore;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\simpletest\KernelTestBase;
use Drupal\entity_test\Entity\EntityTestLabel;

/**
 * Tests KeyValueEntityStorage for content entities.
 *
 * @group KeyValueStore
 */
class KeyValueContentEntityStorageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user', 'entity_test', 'keyvalue_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests CRUD operations.
   */
  function testCRUD() {
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    // Verify default properties on a newly created empty entity.
    $empty = EntityTestLabel::create();
    $this->assertIdentical($empty->id->value, NULL);
    $this->assertIdentical($empty->name->value, NULL);
    $this->assertTrue($empty->uuid->value);
    $this->assertIdentical($empty->langcode->value, $default_langcode);

    // Verify ConfigEntity properties/methods on the newly created empty entity.
    $this->assertIdentical($empty->isNew(), TRUE);
    $this->assertIdentical($empty->bundle(), 'entity_test_label');
    $this->assertIdentical($empty->id(), NULL);
    $this->assertTrue($empty->uuid());
    $this->assertIdentical($empty->label(), NULL);

    // Verify Entity properties/methods on the newly created empty entity.
    $this->assertIdentical($empty->getEntityTypeId(), 'entity_test_label');
    // The URI can only be checked after saving.
    try {
      $empty->urlInfo();
      $this->fail('EntityMalformedException was thrown.');
    }
    catch (EntityMalformedException $e) {
      $this->pass('EntityMalformedException was thrown.');
    }

    // Verify that an empty entity cannot be saved.
    try {
      $empty->save();
      $this->fail('EntityMalformedException was thrown.');
    }
    catch (EntityMalformedException $e) {
      $this->pass('EntityMalformedException was thrown.');
    }

    // Verify that an entity with an empty ID string is considered empty, too.
    $empty_id = EntityTestLabel::create(array(
      'id' => '',
    ));
    $this->assertIdentical($empty_id->isNew(), TRUE);
    try {
      $empty_id->save();
      $this->fail('EntityMalformedException was thrown.');
    }
    catch (EntityMalformedException $e) {
      $this->pass('EntityMalformedException was thrown.');
    }

    // Verify properties on a newly created entity.
    $entity_test = EntityTestLabel::create($expected = array(
      'id' => $this->randomMachineName(),
      'name' => $this->randomString(),
    ));
    $this->assertIdentical($entity_test->id->value, $expected['id']);
    $this->assertTrue($entity_test->uuid->value);
    $this->assertNotEqual($entity_test->uuid->value, $empty->uuid->value);
    $this->assertIdentical($entity_test->name->value, $expected['name']);
    $this->assertIdentical($entity_test->langcode->value, $default_langcode);

    // Verify methods on the newly created entity.
    $this->assertIdentical($entity_test->isNew(), TRUE);
    $this->assertIdentical($entity_test->id(), $expected['id']);
    $this->assertTrue($entity_test->uuid());
    $expected['uuid'] = $entity_test->uuid();
    $this->assertIdentical($entity_test->label(), $expected['name']);

    // Verify that the entity can be saved.
    try {
      $status = $entity_test->save();
      $this->pass('EntityMalformedException was not thrown.');
    }
    catch (EntityMalformedException $e) {
      $this->fail('EntityMalformedException was not thrown.');
    }

    // Verify that the correct status is returned and properties did not change.
    $this->assertIdentical($status, SAVED_NEW);
    $this->assertIdentical($entity_test->id(), $expected['id']);
    $this->assertIdentical($entity_test->uuid(), $expected['uuid']);
    $this->assertIdentical($entity_test->label(), $expected['name']);
    $this->assertIdentical($entity_test->isNew(), FALSE);

    // Save again, and verify correct status and properties again.
    $status = $entity_test->save();
    $this->assertIdentical($status, SAVED_UPDATED);
    $this->assertIdentical($entity_test->id(), $expected['id']);
    $this->assertIdentical($entity_test->uuid(), $expected['uuid']);
    $this->assertIdentical($entity_test->label(), $expected['name']);
    $this->assertIdentical($entity_test->isNew(), FALSE);

    // Ensure that creating an entity with the same id as an existing one is not
    // possible.
    $same_id = EntityTestLabel::create(array(
      'id' => $entity_test->id(),
    ));
    $this->assertIdentical($same_id->isNew(), TRUE);
    try {
      $same_id->save();
      $this->fail('Not possible to overwrite an entity entity.');
    } catch (EntityStorageException $e) {
      $this->pass('Not possible to overwrite an entity entity.');
    }

    // Verify that renaming the ID returns correct status and properties.
    $ids = array($expected['id'], 'second_' . $this->randomMachineName(4), 'third_' . $this->randomMachineName(4));
    for ($i = 1; $i < 3; $i++) {
      $old_id = $ids[$i - 1];
      $new_id = $ids[$i];
      // Before renaming, everything should point to the current ID.
      $this->assertIdentical($entity_test->id(), $old_id);

      // Rename.
      $entity_test->id = $new_id;
      $this->assertIdentical($entity_test->id(), $new_id);
      $status = $entity_test->save();
      $this->assertIdentical($status, SAVED_UPDATED);
      $this->assertIdentical($entity_test->isNew(), FALSE);

      // Verify that originalID points to new ID directly after renaming.
      $this->assertIdentical($entity_test->id(), $new_id);
    }
  }

}
