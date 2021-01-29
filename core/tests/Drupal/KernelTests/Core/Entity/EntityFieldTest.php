<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestComputedField;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the Entity Field API.
 *
 * @group Entity
 */
class EntityFieldTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['filter', 'text', 'node', 'user', 'field_test'];

  /**
   * @var string
   */
  protected $entityName;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $entityUser;

  /**
   * @var string
   */
  protected $entityFieldText;

  protected function setUp(): void {
    parent::setUp();

    foreach (entity_test_entity_types() as $entity_type_id) {
      // The entity_test schema is installed by the parent.
      if ($entity_type_id != 'entity_test') {
        $this->installEntitySchema($entity_type_id);
      }
    }

    // Create the test field.
    module_load_install('entity_test');
    entity_test_install();

    // Install required default configuration for filter module.
    $this->installConfig(['system', 'filter']);
  }

  /**
   * Creates a test entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function createTestEntity($entity_type) {
    $this->entityName = $this->randomMachineName();
    $this->entityUser = $this->createUser();
    $this->entityFieldText = $this->randomMachineName();

    // Pass in the value of the name field when creating. With the user
    // field we test setting a field after creation.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create();
    $entity->user_id->target_id = $this->entityUser->id();
    $entity->name->value = $this->entityName;

    // Set a value for the test field.
    $entity->field_test_text->value = $this->entityFieldText;

    return $entity;
  }

  /**
   * Tests reading and writing properties and field items.
   */
  public function testReadWrite() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->doTestReadWrite($entity_type);
    }
  }

  /**
   * Executes the read write test set for a defined entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function doTestReadWrite($entity_type) {
    $entity = $this->createTestEntity($entity_type);

    $langcode = 'en';

    // Access the name field.
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->name);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->name[0]);

    $this->assertEqual($this->entityName, $entity->name->value, new FormattableMarkup('%entity_type: Name value can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($this->entityName, $entity->name[0]->value, new FormattableMarkup('%entity_type: Name value can be read through list access.', ['%entity_type' => $entity_type]));
    $this->assertEqual([0 => ['value' => $this->entityName]], $entity->name->getValue(), new FormattableMarkup('%entity_type: Plain field value returned.', ['%entity_type' => $entity_type]));

    // Change the name.
    $new_name = $this->randomMachineName();
    $entity->name->value = $new_name;
    $this->assertEqual($new_name, $entity->name->value, new FormattableMarkup('%entity_type: Name can be updated and read.', ['%entity_type' => $entity_type]));
    $this->assertEqual([0 => ['value' => $new_name]], $entity->name->getValue(), new FormattableMarkup('%entity_type: Plain field value reflects the update.', ['%entity_type' => $entity_type]));

    $new_name = $this->randomMachineName();
    $entity->name[0]->value = $new_name;
    $this->assertEqual($new_name, $entity->name->value, new FormattableMarkup('%entity_type: Name can be updated and read through list access.', ['%entity_type' => $entity_type]));

    // Access the user field.
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->user_id);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->user_id[0]);

    $this->assertEqual($this->entityUser->id(), $entity->user_id->target_id, new FormattableMarkup('%entity_type: User id can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($this->entityUser->getAccountName(), $entity->user_id->entity->name->value, new FormattableMarkup('%entity_type: User name can be read.', ['%entity_type' => $entity_type]));

    // Change the assigned user by entity.
    $new_user1 = $this->createUser();
    $entity->user_id->entity = $new_user1;
    $this->assertEqual($new_user1->id(), $entity->user_id->target_id, new FormattableMarkup('%entity_type: Updated user id can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($new_user1->getAccountName(), $entity->user_id->entity->name->value, new FormattableMarkup('%entity_type: Updated username value can be read.', ['%entity_type' => $entity_type]));

    // Change the assigned user by id.
    $new_user2 = $this->createUser();
    $entity->user_id->target_id = $new_user2->id();
    $this->assertEqual($new_user2->id(), $entity->user_id->target_id, new FormattableMarkup('%entity_type: Updated user id can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($new_user2->getAccountName(), $entity->user_id->entity->name->value, new FormattableMarkup('%entity_type: Updated username value can be read.', ['%entity_type' => $entity_type]));

    // Try unsetting a field property.
    $entity->name->value = NULL;
    $entity->user_id->target_id = NULL;
    $this->assertNull($entity->name->value, new FormattableMarkup('%entity_type: Name field is not set.', ['%entity_type' => $entity_type]));
    $this->assertNull($entity->user_id->target_id, new FormattableMarkup('%entity_type: User ID field is not set.', ['%entity_type' => $entity_type]));
    $this->assertNull($entity->user_id->entity, new FormattableMarkup('%entity_type: User entity field is not set.', ['%entity_type' => $entity_type]));

    // Test setting the values via the typed data API works as well.
    // Change the assigned user by entity.
    $entity->user_id->first()->get('entity')->setValue($new_user2);
    $this->assertEqual($new_user2->id(), $entity->user_id->target_id, new FormattableMarkup('%entity_type: Updated user id can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($new_user2->getAccountName(), $entity->user_id->entity->name->value, new FormattableMarkup('%entity_type: Updated user name value can be read.', ['%entity_type' => $entity_type]));

    // Change the assigned user by id.
    $entity->user_id->first()->get('target_id')->setValue($new_user2->id());
    $this->assertEqual($new_user2->id(), $entity->user_id->target_id, new FormattableMarkup('%entity_type: Updated user id can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($new_user2->getAccountName(), $entity->user_id->entity->name->value, new FormattableMarkup('%entity_type: Updated user name value can be read.', ['%entity_type' => $entity_type]));

    // Try unsetting a field.
    $entity->name->first()->get('value')->setValue(NULL);
    $entity->user_id->first()->get('target_id')->setValue(NULL);
    $this->assertNull($entity->name->value, new FormattableMarkup('%entity_type: Name field is not set.', ['%entity_type' => $entity_type]));
    $this->assertNull($entity->user_id->target_id, new FormattableMarkup('%entity_type: User ID field is not set.', ['%entity_type' => $entity_type]));
    $this->assertNull($entity->user_id->entity, new FormattableMarkup('%entity_type: User entity field is not set.', ['%entity_type' => $entity_type]));

    // Create a fresh entity so target_id does not get its property object
    // instantiated, then verify setting a new value via typed data API works.
    $entity2 = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        'user_id' => ['target_id' => $new_user1->id()],
      ]);
    // Access the property object, and set a value.
    $entity2->user_id->first()->get('target_id')->setValue($new_user2->id());
    $this->assertEqual($new_user2->id(), $entity2->user_id->target_id, new FormattableMarkup('%entity_type: Updated user id can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($new_user2->name->value, $entity2->user_id->entity->name->value, new FormattableMarkup('%entity_type: Updated user name value can be read.', ['%entity_type' => $entity_type]));

    // Test using isset(), empty() and unset().
    $entity->name->value = 'test unset';
    unset($entity->name->value);
    $this->assertFalse(isset($entity->name->value), new FormattableMarkup('%entity_type: Name is not set.', ['%entity_type' => $entity_type]));
    $this->assertFalse(isset($entity->name[0]->value), new FormattableMarkup('%entity_type: Name is not set.', ['%entity_type' => $entity_type]));
    $this->assertTrue(empty($entity->name->value), new FormattableMarkup('%entity_type: Name is empty.', ['%entity_type' => $entity_type]));
    $this->assertTrue(empty($entity->name[0]->value), new FormattableMarkup('%entity_type: Name is empty.', ['%entity_type' => $entity_type]));

    $entity->name->value = 'a value';
    $this->assertTrue(isset($entity->name->value), new FormattableMarkup('%entity_type: Name is set.', ['%entity_type' => $entity_type]));
    $this->assertTrue(isset($entity->name[0]->value), new FormattableMarkup('%entity_type: Name is set.', ['%entity_type' => $entity_type]));
    $this->assertFalse(empty($entity->name->value), new FormattableMarkup('%entity_type: Name is not empty.', ['%entity_type' => $entity_type]));
    $this->assertFalse(empty($entity->name[0]->value), new FormattableMarkup('%entity_type: Name is not empty.', ['%entity_type' => $entity_type]));
    $this->assertTrue(isset($entity->name[0]), new FormattableMarkup('%entity_type: Name string item is set.', ['%entity_type' => $entity_type]));
    $this->assertFalse(isset($entity->name[1]), new FormattableMarkup('%entity_type: Second name string item is not set as it does not exist', ['%entity_type' => $entity_type]));
    $this->assertTrue(isset($entity->name), new FormattableMarkup('%entity_type: Name field is set.', ['%entity_type' => $entity_type]));
    $this->assertFalse(isset($entity->nameInvalid), new FormattableMarkup('%entity_type: Non-existent field is not set.', ['%entity_type' => $entity_type]));

    unset($entity->name[0]);
    $this->assertFalse(isset($entity->name[0]), new FormattableMarkup('%entity_type: Name field item is not set.', ['%entity_type' => $entity_type]));
    $this->assertFalse(isset($entity->name[0]->value), new FormattableMarkup('%entity_type: Name is not set.', ['%entity_type' => $entity_type]));
    $this->assertFalse(isset($entity->name->value), new FormattableMarkup('%entity_type: Name is not set.', ['%entity_type' => $entity_type]));

    // Test emptying a field by assigning an empty value. NULL and array()
    // behave the same.
    foreach ([NULL, [], 'unset'] as $empty) {
      // Make sure a value is present
      $entity->name->value = 'a value';
      $this->assertTrue(isset($entity->name->value), new FormattableMarkup('%entity_type: Name is set.', ['%entity_type' => $entity_type]));
      // Now, empty the field.
      if ($empty === 'unset') {
        unset($entity->name);
      }
      else {
        $entity->name = $empty;
      }
      $this->assertTrue(isset($entity->name), new FormattableMarkup('%entity_type: Name field is set.', ['%entity_type' => $entity_type]));
      $this->assertTrue($entity->name->isEmpty(), new FormattableMarkup('%entity_type: Name field is set.', ['%entity_type' => $entity_type]));
      $this->assertCount(0, $entity->name, new FormattableMarkup('%entity_type: Name field contains no items.', ['%entity_type' => $entity_type]));
      $this->assertSame([], $entity->name->getValue(), new FormattableMarkup('%entity_type: Name field value is an empty array.', ['%entity_type' => $entity_type]));
      $this->assertFalse(isset($entity->name[0]), new FormattableMarkup('%entity_type: Name field item is not set.', ['%entity_type' => $entity_type]));
      $this->assertFalse(isset($entity->name[0]->value), new FormattableMarkup('%entity_type: First name item value is not set.', ['%entity_type' => $entity_type]));
      $this->assertFalse(isset($entity->name->value), new FormattableMarkup('%entity_type: Name value is not set.', ['%entity_type' => $entity_type]));
    }

    // Access the language field.
    $langcode_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('langcode');
    $this->assertEqual($langcode, $entity->{$langcode_key}->value, new FormattableMarkup('%entity_type: Language code can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual(\Drupal::languageManager()->getLanguage($langcode), $entity->{$langcode_key}->language, new FormattableMarkup('%entity_type: Language object can be read.', ['%entity_type' => $entity_type]));

    // Change the language by code.
    $entity->{$langcode_key}->value = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $this->assertEqual(\Drupal::languageManager()->getDefaultLanguage()->getId(), $entity->{$langcode_key}->value, new FormattableMarkup('%entity_type: Language code can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual(\Drupal::languageManager()->getDefaultLanguage(), $entity->{$langcode_key}->language, new FormattableMarkup('%entity_type: Language object can be read.', ['%entity_type' => $entity_type]));

    // Revert language by code then try setting it by language object.
    $entity->{$langcode_key}->value = $langcode;
    $entity->{$langcode_key}->language = \Drupal::languageManager()->getDefaultLanguage();
    $this->assertEqual(\Drupal::languageManager()->getDefaultLanguage()->getId(), $entity->{$langcode_key}->value, new FormattableMarkup('%entity_type: Language code can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual(\Drupal::languageManager()->getDefaultLanguage(), $entity->{$langcode_key}->language, new FormattableMarkup('%entity_type: Language object can be read.', ['%entity_type' => $entity_type]));

    // Access the text field and test updating.
    $this->assertEqual($this->entityFieldText, $entity->field_test_text->value, new FormattableMarkup('%entity_type: Text field can be read.', ['%entity_type' => $entity_type]));
    $new_text = $this->randomMachineName();
    $entity->field_test_text->value = $new_text;
    $this->assertEqual($new_text, $entity->field_test_text->value, new FormattableMarkup('%entity_type: Updated text field can be read.', ['%entity_type' => $entity_type]));

    // Test creating the entity by passing in plain values.
    $this->entityName = $this->randomMachineName();
    $name_item[0]['value'] = $this->entityName;
    $this->entityUser = $this->createUser();
    $user_item[0]['target_id'] = $this->entityUser->id();
    $this->entityFieldText = $this->randomMachineName();
    $text_item[0]['value'] = $this->entityFieldText;

    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        'name' => $name_item,
        'user_id' => $user_item,
        'field_test_text' => $text_item,
      ]);
    $this->assertEqual($this->entityName, $entity->name->value, new FormattableMarkup('%entity_type: Name value can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($this->entityUser->id(), $entity->user_id->target_id, new FormattableMarkup('%entity_type: User id can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($this->entityUser->getAccountName(), $entity->user_id->entity->name->value, new FormattableMarkup('%entity_type: User name can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($this->entityFieldText, $entity->field_test_text->value, new FormattableMarkup('%entity_type: Text field can be read.', ['%entity_type' => $entity_type]));

    // Tests copying field values by assigning the TypedData objects.
    $entity2 = $this->createTestEntity($entity_type);
    $entity2->name = $entity->name;
    $entity2->user_id = $entity->user_id;
    $entity2->field_test_text = $entity->field_test_text;
    $this->assertFalse($entity->name === $entity2->name, new FormattableMarkup('%entity_type: Copying properties results in a different field object.', ['%entity_type' => $entity_type]));
    $this->assertEqual($entity->name->value, $entity2->name->value, new FormattableMarkup('%entity_type: Name field copied.', ['%entity_type' => $entity_type]));
    $this->assertEqual($entity->user_id->target_id, $entity2->user_id->target_id, new FormattableMarkup('%entity_type: User id field copied.', ['%entity_type' => $entity_type]));
    $this->assertEqual($entity->field_test_text->value, $entity2->field_test_text->value, new FormattableMarkup('%entity_type: Text field copied.', ['%entity_type' => $entity_type]));

    // Tests that assigning TypedData objects to non-field properties keeps the
    // assigned value as is.
    $entity2 = $this->createTestEntity($entity_type);
    $entity2->_not_a_field = $entity->name;
    $this->assertTrue($entity2->_not_a_field === $entity->name, new FormattableMarkup('%entity_type: Typed data objects can be copied to non-field properties as is.', ['%entity_type' => $entity_type]));

    // Tests adding a value to a field item list.
    $entity->name[] = 'Another name';
    $this->assertEqual('Another name', $entity->name[1]->value, new FormattableMarkup('%entity_type: List item added via [] and the first property.', ['%entity_type' => $entity_type]));
    $entity->name[] = ['value' => 'Third name'];
    $this->assertEqual('Third name', $entity->name[2]->value, new FormattableMarkup('%entity_type: List item added via [] and an array of properties.', ['%entity_type' => $entity_type]));
    $entity->name[3] = ['value' => 'Fourth name'];
    $this->assertEqual('Fourth name', $entity->name[3]->value, new FormattableMarkup('%entity_type: List item added via offset and an array of properties.', ['%entity_type' => $entity_type]));
    unset($entity->name[3]);

    // Test removing and empty-ing list items.
    $this->assertCount(3, $entity->name, new FormattableMarkup('%entity_type: List has 3 items.', ['%entity_type' => $entity_type]));
    unset($entity->name[1]);
    $this->assertCount(2, $entity->name, new FormattableMarkup('%entity_type: Second list item has been removed.', ['%entity_type' => $entity_type]));
    $this->assertEqual('Third name', $entity->name[1]->value, new FormattableMarkup('%entity_type: The subsequent items have been shifted up.', ['%entity_type' => $entity_type]));
    $this->assertEqual(1, $entity->name[1]->getName(), new FormattableMarkup('%entity_type: The items names have been updated to their new delta.', ['%entity_type' => $entity_type]));
    $entity->name[1] = NULL;
    $this->assertCount(2, $entity->name, new FormattableMarkup('%entity_type: Assigning NULL does not reduce array count.', ['%entity_type' => $entity_type]));
    $this->assertTrue($entity->name[1]->isEmpty(), new FormattableMarkup('%entity_type: Assigning NULL empties the item.', ['%entity_type' => $entity_type]));

    // Test using isEmpty().
    unset($entity->name[1]);
    $this->assertFalse($entity->name[0]->isEmpty(), new FormattableMarkup('%entity_type: Name item is not empty.', ['%entity_type' => $entity_type]));
    $entity->name->value = NULL;
    $this->assertTrue($entity->name[0]->isEmpty(), new FormattableMarkup('%entity_type: Name item is empty.', ['%entity_type' => $entity_type]));
    $this->assertTrue($entity->name->isEmpty(), new FormattableMarkup('%entity_type: Name field is empty.', ['%entity_type' => $entity_type]));
    $this->assertCount(1, $entity->name, new FormattableMarkup('%entity_type: Empty item is considered when counting.', ['%entity_type' => $entity_type]));
    $this->assertCount(1, iterator_to_array($entity->name->getIterator()), new FormattableMarkup('%entity_type: Count matches iterator count.', ['%entity_type' => $entity_type]));
    $this->assertTrue($entity->name->getValue() === [0 => ['value' => NULL]], new FormattableMarkup('%entity_type: Name field value contains a NULL value.', ['%entity_type' => $entity_type]));

    // Test using filterEmptyItems().
    $entity->name = [NULL, 'foo'];
    $this->assertCount(2, $entity->name, new FormattableMarkup('%entity_type: List has 2 items.', ['%entity_type' => $entity_type]));
    $entity->name->filterEmptyItems();
    $this->assertCount(1, $entity->name, new FormattableMarkup('%entity_type: The empty item was removed.', ['%entity_type' => $entity_type]));
    $this->assertEqual('foo', $entity->name[0]->value, new FormattableMarkup('%entity_type: The items were renumbered.', ['%entity_type' => $entity_type]));
    $this->assertEqual(0, $entity->name[0]->getName(), new FormattableMarkup('%entity_type: The deltas were updated in the items.', ['%entity_type' => $entity_type]));

    // Test get and set field values.
    $entity->name = 'foo';
    $this->assertEqual(['value' => 'foo'], $entity->name[0]->toArray(), new FormattableMarkup('%entity_type: Field value has been retrieved via toArray()', ['%entity_type' => $entity_type]));

    $values = $entity->toArray();
    $this->assertEqual([0 => ['value' => 'foo']], $values['name'], new FormattableMarkup('%entity_type: Field value has been retrieved via toArray() from an entity.', ['%entity_type' => $entity_type]));

    // Make sure the user id can be set to zero.
    $user_item[0]['target_id'] = 0;
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        'name' => $name_item,
        'user_id' => $user_item,
        'field_test_text' => $text_item,
      ]);
    $this->assertNotNull($entity->user_id->target_id, new FormattableMarkup('%entity_type: User id is not NULL', ['%entity_type' => $entity_type]));
    $this->assertSame(0, $entity->user_id->target_id, new FormattableMarkup('%entity_type: User id has been set to 0', ['%entity_type' => $entity_type]));

    // Test setting the ID with the value only.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        'name' => $name_item,
        'user_id' => 0,
        'field_test_text' => $text_item,
      ]);
    $this->assertNotNull($entity->user_id->target_id, new FormattableMarkup('%entity_type: User id is not NULL', ['%entity_type' => $entity_type]));
    $this->assertSame(0, $entity->user_id->target_id, new FormattableMarkup('%entity_type: User id has been set to 0', ['%entity_type' => $entity_type]));
  }

  /**
   * Tries to save and load an entity again.
   */
  public function testSave() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->doTestSave($entity_type);
    }
  }

  /**
   * Executes the save tests for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function doTestSave($entity_type) {
    $langcode_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('langcode');
    $entity = $this->createTestEntity($entity_type);
    $entity->save();
    $this->assertTrue((bool) $entity->id(), new FormattableMarkup('%entity_type: Entity has received an id.', ['%entity_type' => $entity_type]));

    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->load($entity->id());
    $this->assertTrue((bool) $entity->id(), new FormattableMarkup('%entity_type: Entity loaded.', ['%entity_type' => $entity_type]));

    // Access the name field.
    $this->assertEqual(1, $entity->id->value, new FormattableMarkup('%entity_type: ID value can be read.', ['%entity_type' => $entity_type]));
    $this->assertIsString($entity->uuid->value);
    $this->assertEqual('en', $entity->{$langcode_key}->value, new FormattableMarkup('%entity_type: Language code can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual(\Drupal::languageManager()->getLanguage('en'), $entity->{$langcode_key}->language, new FormattableMarkup('%entity_type: Language object can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($this->entityUser->id(), $entity->user_id->target_id, new FormattableMarkup('%entity_type: User id can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($this->entityUser->getAccountName(), $entity->user_id->entity->name->value, new FormattableMarkup('%entity_type: User name can be read.', ['%entity_type' => $entity_type]));
    $this->assertEqual($this->entityFieldText, $entity->field_test_text->value, new FormattableMarkup('%entity_type: Text field can be read.', ['%entity_type' => $entity_type]));
  }

  /**
   * Tests introspection and getting metadata upfront.
   */
  public function testIntrospection() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->doTestIntrospection($entity_type);
    }
  }

  /**
   * Executes the introspection tests for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function doTestIntrospection($entity_type) {
    // Test getting metadata upfront. The entity types used for this test have
    // a default bundle that is the same as the entity type.
    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $entity_type);
    $this->assertEqual('string', $definitions['name']->getType(), $entity_type . ': Name field found.');
    $this->assertEqual('entity_reference', $definitions['user_id']->getType(), $entity_type . ': User field found.');
    $this->assertEqual('text', $definitions['field_test_text']->getType(), $entity_type . ': Test-text-field field found.');

    // Test deriving further metadata.
    $this->assertInstanceOf(FieldDefinitionInterface::class, $definitions['name']);
    $field_item_definition = $definitions['name']->getItemDefinition();
    $this->assertInstanceOf(ComplexDataDefinitionInterface::class, $field_item_definition);
    $this->assertEqual('field_item:string', $field_item_definition->getDataType());
    $value_definition = $field_item_definition->getPropertyDefinition('value');
    $this->assertInstanceOf(DataDefinitionInterface::class, $value_definition);
    $this->assertEqual('string', $value_definition->getDataType());

    // Test deriving metadata from references.
    $entity_definition = EntityDataDefinition::create($entity_type);
    $langcode_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('langcode');
    $reference_definition = $entity_definition->getPropertyDefinition($langcode_key)
      ->getPropertyDefinition('language')
      ->getTargetDefinition();
    $this->assertEqual('language', $reference_definition->getDataType());

    $reference_definition = $entity_definition->getPropertyDefinition('user_id')
      ->getPropertyDefinition('entity')
      ->getTargetDefinition();

    $this->assertInstanceOf(EntityDataDefinitionInterface::class, $reference_definition);
    $this->assertEqual('user', $reference_definition->getEntityTypeId(), 'Referenced entity is of type "user".');

    // Test propagating down.
    $name_definition = $reference_definition->getPropertyDefinition('name');
    $this->assertInstanceOf(FieldDefinitionInterface::class, $name_definition);
    $this->assertEqual('string', $name_definition->getPropertyDefinition('value')->getDataType());

    // Test introspecting an entity object.
    // @todo: Add bundles and test bundles as well.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create();

    $definitions = $entity->getFieldDefinitions();
    $this->assertEqual('string', $definitions['name']->getType(), $entity_type . ': Name field found.');
    $this->assertEqual('entity_reference', $definitions['user_id']->getType(), $entity_type . ': User field found.');
    $this->assertEqual('text', $definitions['field_test_text']->getType(), $entity_type . ': Test-text-field field found.');

    $name_properties = $entity->name->getFieldDefinition()->getPropertyDefinitions();
    $this->assertEqual('string', $name_properties['value']->getDataType(), $entity_type . ': String value property of the name found.');

    $userref_properties = $entity->user_id->getFieldDefinition()->getPropertyDefinitions();
    $this->assertEqual('integer', $userref_properties['target_id']->getDataType(), $entity_type . ': Entity id property of the user found.');
    $this->assertEqual('entity_reference', $userref_properties['entity']->getDataType(), $entity_type . ': Entity reference property of the user found.');

    $textfield_properties = $entity->field_test_text->getFieldDefinition()->getFieldStorageDefinition()->getPropertyDefinitions();
    $this->assertEqual('string', $textfield_properties['value']->getDataType(), $entity_type . ': String value property of the test-text field found.');
    $this->assertEqual('filter_format', $textfield_properties['format']->getDataType(), $entity_type . ': String format field of the test-text field found.');
    $this->assertEqual('string', $textfield_properties['processed']->getDataType(), $entity_type . ': String processed property of the test-text field found.');

    // Make sure provided contextual information is right.
    $entity_adapter = $entity->getTypedData();
    $this->assertSame($entity_adapter->getRoot(), $entity_adapter, 'Entity is root object.');
    $this->assertEqual('', $entity_adapter->getPropertyPath());
    $this->assertEqual('', $entity_adapter->getName());
    $this->assertNull($entity_adapter->getParent());

    $field = $entity->user_id;
    $this->assertSame($field->getRoot()->getValue(), $entity, 'Entity is root object.');
    $this->assertSame($field->getEntity(), $entity, 'getEntity() returns the entity.');
    $this->assertEqual('user_id', $field->getPropertyPath());
    $this->assertEqual('user_id', $field->getName());
    $this->assertSame($field->getParent()->getValue(), $entity, 'Parent object matches.');

    $field_item = $field[0];
    $this->assertSame($field_item->getRoot()->getValue(), $entity, 'Entity is root object.');
    $this->assertSame($field_item->getEntity(), $entity, 'getEntity() returns the entity.');
    $this->assertEqual('user_id.0', $field_item->getPropertyPath());
    $this->assertEqual('0', $field_item->getName());
    $this->assertSame($field_item->getParent(), $field, 'Parent object matches.');

    $item_value = $field_item->get('entity');
    $this->assertSame($item_value->getRoot()->getValue(), $entity, 'Entity is root object.');
    $this->assertEqual('user_id.0.entity', $item_value->getPropertyPath());
    $this->assertEqual('entity', $item_value->getName());
    $this->assertSame($item_value->getParent(), $field_item, 'Parent object matches.');
  }

  /**
   * Tests iterating over properties.
   */
  public function testIterator() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->doTestIterator($entity_type);
    }
  }

  /**
   * Executes the iterator tests for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function doTestIterator($entity_type) {
    $entity = $this->createTestEntity($entity_type);

    foreach ($entity as $name => $field) {
      $this->assertInstanceOf(FieldItemListInterface::class, $field);

      foreach ($field as $delta => $item) {
        $this->assertInstanceOf(FieldItemInterface::class, $field[0]);

        foreach ($item as $value_name => $value_property) {
          $this->assertInstanceOf(TypedDataInterface::class, $value_property);

          $value = $value_property->getValue();
          $this->assertTrue(!isset($value) || is_scalar($value) || $value instanceof EntityInterface, $entity_type . ": Value $value_name of item $delta of field $name is a primitive or an entity.");
        }
      }
    }

    $fields = $entity->getFields();
    $this->assertEqual(array_keys($entity->getTypedData()->getDataDefinition()->getPropertyDefinitions()), array_keys($fields), new FormattableMarkup('%entity_type: All fields returned.', ['%entity_type' => $entity_type]));
    $this->assertEqual(iterator_to_array($entity->getIterator()), $fields, new FormattableMarkup('%entity_type: Entity iterator iterates over all fields.', ['%entity_type' => $entity_type]));
  }

  /**
   * Tests working with the entity based upon the TypedData API.
   */
  public function testDataStructureInterfaces() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->doTestDataStructureInterfaces($entity_type);
    }
  }

  /**
   * Executes the data structure interfaces tests for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function doTestDataStructureInterfaces($entity_type) {
    $entity = $this->createTestEntity($entity_type);

    // Test using the whole tree of typed data by navigating through the tree of
    // contained properties and getting all contained strings, limited by a
    // certain depth.
    $strings = [];
    $this->getContainedStrings($entity->getTypedData(), 0, $strings);

    // @todo: Once the user entity has defined properties this should contain
    // the user name and other user entity strings as well.
    $target_strings = [
      $entity->uuid->value,
      'en',
      $this->entityName,
      // Bundle name.
      $entity->bundle(),
      $this->entityFieldText,
      // Field format.
      NULL,
    ];

    if ($entity instanceof RevisionLogInterface) {
      // Adding empty string for revision message.
      $target_strings[] = '';
    }

    asort($strings);
    asort($target_strings);
    $this->assertEqual(array_values($target_strings), array_values($strings), new FormattableMarkup('%entity_type: All contained strings found.', ['%entity_type' => $entity_type]));
  }

  /**
   * Recursive helper for getting all contained strings,
   * i.e. properties of type string.
   */
  public function getContainedStrings(TypedDataInterface $wrapper, $depth, array &$strings) {

    if ($wrapper instanceof StringInterface) {
      $strings[] = $wrapper->getValue();
    }

    // Recurse until a certain depth is reached if possible.
    if ($depth < 7) {
      if ($wrapper instanceof ListInterface) {
        foreach ($wrapper as $item) {
          $this->getContainedStrings($item, $depth + 1, $strings);
        }
      }
      elseif ($wrapper instanceof ComplexDataInterface) {
        foreach ($wrapper as $property) {
          $this->getContainedStrings($property, $depth + 1, $strings);
        }
      }
    }
  }

  /**
   * Makes sure data types are correctly derived for all entity types.
   */
  public function testDataTypes() {
    $types = \Drupal::typedDataManager()->getDefinitions();
    foreach (entity_test_entity_types() as $entity_type) {
      $this->assertNotEmpty($types['entity:' . $entity_type]['class'], 'Entity data type registered.');
    }
    // Check bundle types are provided as well.
    entity_test_create_bundle('bundle');
    $types = \Drupal::typedDataManager()->getDefinitions();
    $this->assertNotEmpty($types['entity:entity_test:bundle']['class'], 'Entity bundle data type registered.');
  }

  /**
   * Tests a base field override on a non-existing base field.
   *
   * @see entity_test_entity_base_field_info_alter()
   */
  public function testBaseFieldNonExistingBaseField() {
    $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'page',
      'name' => 'page',
    ])->save();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'page');
    $override = $fields['status']->getConfig('page');
    $override->setLabel($this->randomString())->save();
    \Drupal::state()->set('entity_test.node_remove_status_field', TRUE);
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'page');
    // A base field override on a non-existing base field should not cause a
    // field definition to come into existence.
    $this->assertFalse(isset($fields['status']), 'Node\'s status base field does not exist.');
  }

  /**
   * Tests creating a field override config for a bundle field.
   *
   * @see entity_test_entity_base_field_info_alter()
   */
  public function testFieldOverrideBundleField() {
    // First make sure the bundle field override in code, which is provided by
    // the test entity works.
    entity_test_create_bundle('some_test_bundle', 'Some test bundle', 'entity_test_field_override');
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('entity_test_field_override', 'entity_test_field_override');
    $this->assertEqual('The default description.', $field_definitions['name']->getDescription());
    $this->assertNull($field_definitions['name']->getTargetBundle());

    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('entity_test_field_override', 'some_test_bundle');
    $this->assertEqual('Custom description.', $field_definitions['name']->getDescription());
    $this->assertEqual('some_test_bundle', $field_definitions['name']->getTargetBundle());

    // Now create a config override of the bundle field.
    $field_config = $field_definitions['name']->getConfig('some_test_bundle');
    $field_config->setTranslatable(FALSE);
    $field_config->save();

    // Make sure both overrides are present.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('entity_test_field_override', 'some_test_bundle');
    $this->assertEqual('Custom description.', $field_definitions['name']->getDescription());
    $this->assertEqual('some_test_bundle', $field_definitions['name']->getTargetBundle());
    $this->assertFalse($field_definitions['name']->isTranslatable());
  }

  /**
   * Tests validation constraints provided by the Entity API.
   */
  public function testEntityConstraintValidation() {
    $entity = $this->createTestEntity('entity_test');
    $entity->save();
    // Create a reference field item and let it reference the entity.
    $definition = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Test entity')
      ->setSetting('target_type', 'entity_test');
    $reference_field = \Drupal::typedDataManager()->create($definition);
    $reference = $reference_field->appendItem(['entity' => $entity])->get('entity');

    // Test validation the typed data object.
    $violations = $reference->validate();
    $this->assertEqual(0, $violations->count());

    // Test validating an entity of the wrong type.
    $user = $this->createUser();
    $user->save();
    $node = $node = Node::create([
      'type' => 'page',
      'uid' => $user->id(),
      'title' => $this->randomString(),
    ]);
    $reference->setValue($node);
    $violations = $reference->validate();
    $this->assertEqual(1, $violations->count());

    // Test bundle validation.
    NodeType::create(['type' => 'article'])
      ->save();
    $definition = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Test entity')
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', ['target_bundles' => ['article' => 'article']]);
    $reference_field = \Drupal::TypedDataManager()->create($definition);
    $reference_field->appendItem(['entity' => $node]);
    $violations = $reference_field->validate();
    $this->assertEqual(1, $violations->count());

    $node = Node::create([
      'type' => 'article',
      'uid' => $user->id(),
      'title' => $this->randomString(),
    ]);
    $node->save();
    $reference_field->entity = $node;
    $violations = $reference_field->validate();
    $this->assertEqual(0, $violations->count());
  }

  /**
   * Tests getting processed property values via a computed property.
   */
  public function testComputedProperties() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->doTestComputedProperties($entity_type);
    }
  }

  /**
   * Tests all the interaction points of a computed field.
   */
  public function testComputedFields() {
    $this->installEntitySchema('entity_test_computed_field');

    \Drupal::state()->set('entity_test_computed_field_item_list_value', ['foo computed']);

    // Check that the values are not computed unnecessarily during the lifecycle
    // of an entity when the field is not interacted with directly.
    \Drupal::state()->set('computed_test_field_execution', 0);
    $entity = EntityTestComputedField::create([]);
    $this->assertSame(0, \Drupal::state()->get('computed_test_field_execution', 0));

    $entity->name->value = $this->randomString();
    $this->assertSame(0, \Drupal::state()->get('computed_test_field_execution', 0));

    $entity->save();
    $this->assertSame(0, \Drupal::state()->get('computed_test_field_execution', 0));

    // Test \Drupal\Core\TypedData\ComputedItemListTrait::getValue().
    \Drupal::state()->set('computed_test_field_execution', 0);
    $entity = EntityTestComputedField::create([]);
    $this->assertSame([['value' => 'foo computed']], $entity->computed_string_field->getValue());

    // Check that the values are only computed once.
    $this->assertSame(1, \Drupal::state()->get('computed_test_field_execution', 0));

    // Test \Drupal\Core\TypedData\ComputedItemListTrait::setValue(). This also
    // checks that a subsequent getter does not try to re-compute the value.
    \Drupal::state()->set('computed_test_field_execution', 0);
    $entity = EntityTestComputedField::create([]);
    $entity->computed_string_field->setValue([
      ['value' => 'foo computed 1'],
      ['value' => 'foo computed 2'],
    ]);
    $this->assertSame([['value' => 'foo computed 1'], ['value' => 'foo computed 2']], $entity->computed_string_field->getValue());

    // Check that the values have not been computed when they were explicitly
    // set.
    $this->assertSame(0, \Drupal::state()->get('computed_test_field_execution', 0));

    // Test \Drupal\Core\TypedData\ComputedItemListTrait::getString().
    $entity = EntityTestComputedField::create([]);
    $this->assertSame('foo computed', $entity->computed_string_field->getString());

    // Test \Drupal\Core\TypedData\ComputedItemListTrait::get().
    $entity = EntityTestComputedField::create([]);
    $this->assertSame('foo computed', $entity->computed_string_field->get(0)->value);
    $this->assertEmpty($entity->computed_string_field->get(1));

    // Test \Drupal\Core\TypedData\ComputedItemListTrait::set().
    $entity = EntityTestComputedField::create([]);
    $entity->computed_string_field->set(1, 'foo computed 1');
    $this->assertSame('foo computed', $entity->computed_string_field[0]->value);
    $this->assertSame('foo computed 1', $entity->computed_string_field[1]->value);
    $entity->computed_string_field->set(0, 'foo computed 0');
    $this->assertSame('foo computed 0', $entity->computed_string_field[0]->value);
    $this->assertSame('foo computed 1', $entity->computed_string_field[1]->value);

    // Test \Drupal\Core\TypedData\ComputedItemListTrait::appendItem().
    $entity = EntityTestComputedField::create([]);
    $entity->computed_string_field->appendItem('foo computed 1');
    $this->assertSame('foo computed', $entity->computed_string_field[0]->value);
    $this->assertSame('foo computed 1', $entity->computed_string_field[1]->value);

    // Test \Drupal\Core\TypedData\ComputedItemListTrait::removeItem().
    $entity = EntityTestComputedField::create([]);
    $entity->computed_string_field->removeItem(0);
    $this->assertTrue($entity->computed_string_field->isEmpty());

    // Test \Drupal\Core\TypedData\ComputedItemListTrait::isEmpty().
    \Drupal::state()->set('entity_test_computed_field_item_list_value', []);
    $entity = EntityTestComputedField::create([]);
    $this->assertTrue($entity->computed_string_field->isEmpty());

    \Drupal::state()->set('entity_test_computed_field_item_list_value', ['foo computed']);
    $entity = EntityTestComputedField::create([]);
    $this->assertFalse($entity->computed_string_field->isEmpty());

    // Test \Drupal\Core\TypedData\ComputedItemListTrait::filter().
    $filter_callback = function ($item) {
      return !$item->isEmpty();
    };
    $entity = EntityTestComputedField::create([]);
    $entity->computed_string_field->filter($filter_callback);
    $this->assertCount(1, $entity->computed_string_field);

    // Add an empty item to the list and check that it is filtered out.
    $entity->computed_string_field->appendItem();
    $entity->computed_string_field->filter($filter_callback);
    $this->assertCount(1, $entity->computed_string_field);

    // Add a non-empty item to the list and check that it is not filtered out.
    $entity->computed_string_field->appendItem('foo computed 1');
    $entity->computed_string_field->filter($filter_callback);
    $this->assertCount(2, $entity->computed_string_field);

    // Test \Drupal\Core\TypedData\ComputedItemListTrait::offsetExists().
    $entity = EntityTestComputedField::create([]);
    $this->assertTrue($entity->computed_string_field->offsetExists(0));
    $this->assertFalse($entity->computed_string_field->offsetExists(1));

    // Test \Drupal\Core\TypedData\ComputedItemListTrait::getIterator().
    $entity = EntityTestComputedField::create([]);
    foreach ($entity->computed_string_field as $delta => $item) {
      $this->assertSame('foo computed', $item->value);
    }

    // Test \Drupal\Core\TypedData\ComputedItemListTrait::count().
    $entity = EntityTestComputedField::create([]);
    $this->assertCount(1, $entity->computed_string_field);

    // Check that computed items are not auto-created when they have no values.
    \Drupal::state()->set('entity_test_computed_field_item_list_value', []);
    $entity = EntityTestComputedField::create([]);
    $this->assertCount(0, $entity->computed_string_field);

    // Test \Drupal\Core\Field\FieldItemList::equals() for a computed field.
    \Drupal::state()->set('entity_test_computed_field_item_list_value', ['foo computed']);
    $entity = EntityTestComputedField::create([]);
    $computed_item_list1 = $entity->computed_string_field;

    $entity = EntityTestComputedField::create([]);
    $computed_item_list2 = $entity->computed_string_field;

    $this->assertTrue($computed_item_list1->equals($computed_item_list2));

    $computed_item_list2->value = 'foo computed 2';
    $this->assertFalse($computed_item_list1->equals($computed_item_list2));
  }

  /**
   * Tests an entity reference computed field.
   */
  public function testEntityReferenceComputedField() {
    $this->installEntitySchema('entity_test_computed_field');

    // Create 2 entities to be referenced.
    $ref1 = EntityTest::create(['name' => 'foo', 'type' => 'bar']);
    $ref1->save();
    $ref2 = EntityTest::create(['name' => 'baz', 'type' => 'bar']);
    $ref2->save();
    \Drupal::state()->set('entity_test_reference_computed_target_ids', [$ref1->id(), $ref2->id()]);

    $entity = EntityTestComputedField::create([]);
    $entity->save();

    /** @var \Drupal\entity_test\Plugin\Field\ComputedReferenceTestFieldItemList $field */
    $field = $entity->get('computed_reference_field');
    /** @var \Drupal\Core\Entity\EntityInterface[] $referenced_entities */
    $referenced_entities = $field->referencedEntities();

    // Check that ::referencedEntities() is working with computed fields.
    $this->assertEquals($ref1->id(), $referenced_entities[0]->id());
    $this->assertEquals($ref2->id(), $referenced_entities[1]->id());
  }

  /**
   * Executes the computed properties tests for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function doTestComputedProperties($entity_type) {
    $entity = $this->createTestEntity($entity_type);
    $entity->field_test_text->value = "The <strong>text</strong> text to filter.";
    $entity->field_test_text->format = filter_default_format();

    $target = "<p>The &lt;strong&gt;text&lt;/strong&gt; text to filter.</p>\n";
    $this->assertEqual($target, $entity->field_test_text->processed, new FormattableMarkup('%entity_type: Text is processed with the default filter.', ['%entity_type' => $entity_type]));

    // Save and load entity and make sure it still works.
    $entity->save();
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->load($entity->id());
    $this->assertEqual($target, $entity->field_test_text->processed, new FormattableMarkup('%entity_type: Text is processed with the default filter.', ['%entity_type' => $entity_type]));
  }

  /**
   * Tests explicit entity ID assignment.
   */
  public function testEntityIdAssignment() {
    $entity_type = 'entity_test';
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage($entity_type);

    // Check that an ID can be explicitly assigned on creation.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->createTestEntity($entity_type);
    $entity_id = 3;
    $entity->set('id', $entity_id);
    $this->assertSame($entity_id, $entity->id());
    $storage->save($entity);
    $entity = $storage->loadUnchanged($entity->id());
    $this->assertInstanceOf(ContentEntityInterface::class, $entity);

    // Check that an explicitly-assigned ID is preserved on update.
    $storage->save($entity);
    $entity = $storage->loadUnchanged($entity->id());
    $this->assertInstanceOf(ContentEntityInterface::class, $entity);

    // Check that an ID cannot be explicitly assigned on update.
    $this->expectException(EntityStorageException::class);
    $entity->set('id', $entity_id + 1);
    $storage->save($entity);
  }

}
