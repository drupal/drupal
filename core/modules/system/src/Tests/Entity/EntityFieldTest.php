<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityFieldTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\node\Entity\NodeType;

/**
 * Tests the Entity Field API.
 *
 * @group Entity
 */
class EntityFieldTest extends EntityUnitTestBase  {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'text', 'node', 'user', 'field_test');

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

  protected function setUp() {
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
    $this->installConfig(array('system', 'filter'));
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
    $entity = entity_create($entity_type);
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
    $this->assertTrue($entity->name instanceof FieldItemListInterface, format_string('%entity_type: Field implements interface', array('%entity_type' => $entity_type)));
    $this->assertTrue($entity->name[0] instanceof FieldItemInterface, format_string('%entity_type: Field item implements interface', array('%entity_type' => $entity_type)));

    $this->assertEqual($this->entityName, $entity->name->value, format_string('%entity_type: Name value can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($this->entityName, $entity->name[0]->value, format_string('%entity_type: Name value can be read through list access.', array('%entity_type' => $entity_type)));
    $this->assertEqual($entity->name->getValue(), array(0 => array('value' => $this->entityName)), format_string('%entity_type: Plain field value returned.', array('%entity_type' => $entity_type)));

    // Change the name.
    $new_name = $this->randomMachineName();
    $entity->name->value = $new_name;
    $this->assertEqual($new_name, $entity->name->value, format_string('%entity_type: Name can be updated and read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($entity->name->getValue(), array(0 => array('value' => $new_name)), format_string('%entity_type: Plain field value reflects the update.', array('%entity_type' => $entity_type)));

    $new_name = $this->randomMachineName();
    $entity->name[0]->value = $new_name;
    $this->assertEqual($new_name, $entity->name->value, format_string('%entity_type: Name can be updated and read through list access.', array('%entity_type' => $entity_type)));

    // Access the user field.
    $this->assertTrue($entity->user_id instanceof FieldItemListInterface, format_string('%entity_type: Field implements interface', array('%entity_type' => $entity_type)));
    $this->assertTrue($entity->user_id[0] instanceof FieldItemInterface, format_string('%entity_type: Field item implements interface', array('%entity_type' => $entity_type)));

    $this->assertEqual($this->entityUser->id(), $entity->user_id->target_id, format_string('%entity_type: User id can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($this->entityUser->getUsername(), $entity->user_id->entity->name->value, format_string('%entity_type: User name can be read.', array('%entity_type' => $entity_type)));

    // Change the assigned user by entity.
    $new_user1 = $this->createUser();
    $entity->user_id->entity = $new_user1;
    $this->assertEqual($new_user1->id(), $entity->user_id->target_id, format_string('%entity_type: Updated user id can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($new_user1->getUsername(), $entity->user_id->entity->name->value, format_string('%entity_type: Updated username value can be read.', array('%entity_type' => $entity_type)));

    // Change the assigned user by id.
    $new_user2 = $this->createUser();
    $entity->user_id->target_id = $new_user2->id();
    $this->assertEqual($new_user2->id(), $entity->user_id->target_id, format_string('%entity_type: Updated user id can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($new_user2->getUsername(), $entity->user_id->entity->name->value, format_string('%entity_type: Updated username value can be read.', array('%entity_type' => $entity_type)));

    // Try unsetting a field property.
    $entity->name->value = NULL;
    $entity->user_id->target_id = NULL;
    $this->assertNull($entity->name->value, format_string('%entity_type: Name field is not set.', array('%entity_type' => $entity_type)));
    $this->assertNull($entity->user_id->target_id, format_string('%entity_type: User ID field is not set.', array('%entity_type' => $entity_type)));
    $this->assertNull($entity->user_id->entity, format_string('%entity_type: User entity field is not set.', array('%entity_type' => $entity_type)));

    // Test setting the values via the typed data API works as well.
    // Change the assigned user by entity.
    $entity->user_id->first()->get('entity')->setValue($new_user2);
    $this->assertEqual($new_user2->id(), $entity->user_id->target_id, format_string('%entity_type: Updated user id can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($new_user2->getUsername(), $entity->user_id->entity->name->value, format_string('%entity_type: Updated user name value can be read.', array('%entity_type' => $entity_type)));

    // Change the assigned user by id.
    $entity->user_id->first()->get('target_id')->setValue($new_user2->id());
    $this->assertEqual($new_user2->id(), $entity->user_id->target_id, format_string('%entity_type: Updated user id can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($new_user2->getUsername(), $entity->user_id->entity->name->value, format_string('%entity_type: Updated user name value can be read.', array('%entity_type' => $entity_type)));

    // Try unsetting a field.
    $entity->name->first()->get('value')->setValue(NULL);
    $entity->user_id->first()->get('target_id')->setValue(NULL);
    $this->assertNull($entity->name->value, format_string('%entity_type: Name field is not set.', array('%entity_type' => $entity_type)));
    $this->assertNull($entity->user_id->target_id, format_string('%entity_type: User ID field is not set.', array('%entity_type' => $entity_type)));
    $this->assertNull($entity->user_id->entity, format_string('%entity_type: User entity field is not set.', array('%entity_type' => $entity_type)));

    // Create a fresh entity so target_id does not get its property object
    // instantiated, then verify setting a new value via typed data API works.
    $entity2 = entity_create($entity_type, array(
      'user_id' => array('target_id' => $new_user1->id()),
    ));
    // Access the property object, and set a value.
    $entity2->user_id->first()->get('target_id')->setValue($new_user2->id());
    $this->assertEqual($new_user2->id(), $entity2->user_id->target_id, format_string('%entity_type: Updated user id can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($new_user2->name->value, $entity2->user_id->entity->name->value, format_string('%entity_type: Updated user name value can be read.', array('%entity_type' => $entity_type)));

    // Test using isset(), empty() and unset().
    $entity->name->value = 'test unset';
    unset($entity->name->value);
    $this->assertFalse(isset($entity->name->value), format_string('%entity_type: Name is not set.', array('%entity_type' => $entity_type)));
    $this->assertFalse(isset($entity->name[0]->value), format_string('%entity_type: Name is not set.', array('%entity_type' => $entity_type)));
    $this->assertTrue(empty($entity->name->value), format_string('%entity_type: Name is empty.', array('%entity_type' => $entity_type)));
    $this->assertTrue(empty($entity->name[0]->value), format_string('%entity_type: Name is empty.', array('%entity_type' => $entity_type)));

    $entity->name->value = 'a value';
    $this->assertTrue(isset($entity->name->value), format_string('%entity_type: Name is set.', array('%entity_type' => $entity_type)));
    $this->assertTrue(isset($entity->name[0]->value), format_string('%entity_type: Name is set.', array('%entity_type' => $entity_type)));
    $this->assertFalse(empty($entity->name->value), format_string('%entity_type: Name is not empty.', array('%entity_type' => $entity_type)));
    $this->assertFalse(empty($entity->name[0]->value), format_string('%entity_type: Name is not empty.', array('%entity_type' => $entity_type)));
    $this->assertTrue(isset($entity->name[0]), format_string('%entity_type: Name string item is set.', array('%entity_type' => $entity_type)));
    $this->assertFalse(isset($entity->name[1]), format_string('%entity_type: Second name string item is not set as it does not exist', array('%entity_type' => $entity_type)));
    $this->assertTrue(isset($entity->name), format_string('%entity_type: Name field is set.', array('%entity_type' => $entity_type)));
    $this->assertFalse(isset($entity->nameInvalid), format_string('%entity_type: Not existing field is not set.', array('%entity_type' => $entity_type)));

    unset($entity->name[0]);
    $this->assertFalse(isset($entity->name[0]), format_string('%entity_type: Name field item is not set.', array('%entity_type' => $entity_type)));
    $this->assertFalse(isset($entity->name[0]->value), format_string('%entity_type: Name is not set.', array('%entity_type' => $entity_type)));
    $this->assertFalse(isset($entity->name->value), format_string('%entity_type: Name is not set.', array('%entity_type' => $entity_type)));

    // Test emptying a field by assigning an empty value. NULL and array()
    // behave the same.
    foreach ([NULL, array(), 'unset'] as $empty) {
      // Make sure a value is present
      $entity->name->value = 'a value';
      $this->assertTrue(isset($entity->name->value), format_string('%entity_type: Name is set.', array('%entity_type' => $entity_type)));
      // Now, empty the field.
      if ($empty === 'unset') {
        unset($entity->name);
      }
      else {
        $entity->name = $empty;
      }
      $this->assertTrue(isset($entity->name), format_string('%entity_type: Name field is set.', array('%entity_type' => $entity_type)));
      $this->assertTrue($entity->name->isEmpty(), format_string('%entity_type: Name field is set.', array('%entity_type' => $entity_type)));
      $this->assertIdentical(count($entity->name), 0, format_string('%entity_type: Name field contains no items.', array('%entity_type' => $entity_type)));
      $this->assertIdentical($entity->name->getValue(), array(), format_string('%entity_type: Name field value is an empty array.', array('%entity_type' => $entity_type)));
      $this->assertFalse(isset($entity->name[0]), format_string('%entity_type: Name field item is not set.', array('%entity_type' => $entity_type)));
      $this->assertFalse(isset($entity->name[0]->value), format_string('%entity_type: First name item value is not set.', array('%entity_type' => $entity_type)));
      $this->assertFalse(isset($entity->name->value), format_string('%entity_type: Name value is not set.', array('%entity_type' => $entity_type)));
    }

    // Access the language field.
    $langcode_key = $this->entityManager->getDefinition($entity_type)->getKey('langcode');
    $this->assertEqual($langcode, $entity->{$langcode_key}->value, format_string('%entity_type: Language code can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual(\Drupal::languageManager()->getLanguage($langcode), $entity->{$langcode_key}->language, format_string('%entity_type: Language object can be read.', array('%entity_type' => $entity_type)));

    // Change the language by code.
    $entity->{$langcode_key}->value = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $this->assertEqual(\Drupal::languageManager()->getDefaultLanguage()->getId(), $entity->{$langcode_key}->value, format_string('%entity_type: Language code can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual(\Drupal::languageManager()->getDefaultLanguage(), $entity->{$langcode_key}->language, format_string('%entity_type: Language object can be read.', array('%entity_type' => $entity_type)));

    // Revert language by code then try setting it by language object.
    $entity->{$langcode_key}->value = $langcode;
    $entity->{$langcode_key}->language = \Drupal::languageManager()->getDefaultLanguage();
    $this->assertEqual(\Drupal::languageManager()->getDefaultLanguage()->getId(), $entity->{$langcode_key}->value, format_string('%entity_type: Language code can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual(\Drupal::languageManager()->getDefaultLanguage(), $entity->{$langcode_key}->language, format_string('%entity_type: Language object can be read.', array('%entity_type' => $entity_type)));

    // Access the text field and test updating.
    $this->assertEqual($entity->field_test_text->value, $this->entityFieldText, format_string('%entity_type: Text field can be read.', array('%entity_type' => $entity_type)));
    $new_text = $this->randomMachineName();
    $entity->field_test_text->value = $new_text;
    $this->assertEqual($entity->field_test_text->value, $new_text, format_string('%entity_type: Updated text field can be read.', array('%entity_type' => $entity_type)));

    // Test creating the entity by passing in plain values.
    $this->entityName = $this->randomMachineName();
    $name_item[0]['value'] = $this->entityName;
    $this->entityUser = $this->createUser();
    $user_item[0]['target_id'] = $this->entityUser->id();
    $this->entityFieldText = $this->randomMachineName();
    $text_item[0]['value'] = $this->entityFieldText;

    $entity = entity_create($entity_type, array(
      'name' => $name_item,
      'user_id' => $user_item,
      'field_test_text' => $text_item,
    ));
    $this->assertEqual($this->entityName, $entity->name->value, format_string('%entity_type: Name value can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($this->entityUser->id(), $entity->user_id->target_id, format_string('%entity_type: User id can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($this->entityUser->getUsername(), $entity->user_id->entity->name->value, format_string('%entity_type: User name can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($this->entityFieldText, $entity->field_test_text->value, format_string('%entity_type: Text field can be read.', array('%entity_type' => $entity_type)));

    // Tests copying field values by assigning the TypedData objects.
    $entity2 = $this->createTestEntity($entity_type);
    $entity2->name = $entity->name;
    $entity2->user_id = $entity->user_id;
    $entity2->field_test_text = $entity->field_test_text;
    $this->assertFalse($entity->name === $entity2->name, format_string('%entity_type: Copying properties results in a different field object.', array('%entity_type' => $entity_type)));
    $this->assertEqual($entity->name->value, $entity2->name->value, format_string('%entity_type: Name field copied.', array('%entity_type' => $entity_type)));
    $this->assertEqual($entity->user_id->target_id, $entity2->user_id->target_id, format_string('%entity_type: User id field copied.', array('%entity_type' => $entity_type)));
    $this->assertEqual($entity->field_test_text->value, $entity2->field_test_text->value, format_string('%entity_type: Text field copied.', array('%entity_type' => $entity_type)));

    // Tests that assigning TypedData objects to non-field properties keeps the
    // assigned value as is.
    $entity2 = $this->createTestEntity($entity_type);
    $entity2->_not_a_field = $entity->name;
    $this->assertTrue($entity2->_not_a_field === $entity->name, format_string('%entity_type: Typed data objects can be copied to non-field properties as is.', array('%entity_type' => $entity_type)));

    // Tests adding a value to a field item list.
    $entity->name[] = 'Another name';
    $this->assertEqual($entity->name[1]->value, 'Another name', format_string('%entity_type: List item added via [] and the first property.', array('%entity_type' => $entity_type)));
    $entity->name[] = array('value' => 'Third name');
    $this->assertEqual($entity->name[2]->value, 'Third name', format_string('%entity_type: List item added via [] and an array of properties.', array('%entity_type' => $entity_type)));
    $entity->name[3] = array('value' => 'Fourth name');
    $this->assertEqual($entity->name[3]->value, 'Fourth name', format_string('%entity_type: List item added via offset and an array of properties.', array('%entity_type' => $entity_type)));
    unset($entity->name[3]);

    // Test removing and empty-ing list items.
    $this->assertEqual(count($entity->name), 3, format_string('%entity_type: List has 3 items.', array('%entity_type' => $entity_type)));
    unset($entity->name[1]);
    $this->assertEqual(count($entity->name), 2, format_string('%entity_type: Second list item has been removed.', array('%entity_type' => $entity_type)));
    $this->assertEqual($entity->name[1]->value, 'Third name', format_string('%entity_type: The subsequent items have been shifted up.', array('%entity_type' => $entity_type)));
    $this->assertEqual($entity->name[1]->getName(), 1, format_string('%entity_type: The items names have been updated to their new delta.', array('%entity_type' => $entity_type)));
    $entity->name[1] = NULL;
    $this->assertEqual(count($entity->name), 2, format_string('%entity_type: Assigning NULL does not reduce array count.', array('%entity_type' => $entity_type)));
    $this->assertTrue($entity->name[1]->isEmpty(), format_string('%entity_type: Assigning NULL empties the item.', array('%entity_type' => $entity_type)));

    // Test using isEmpty().
    unset($entity->name[1]);
    $this->assertFalse($entity->name[0]->isEmpty(), format_string('%entity_type: Name item is not empty.', array('%entity_type' => $entity_type)));
    $entity->name->value = NULL;
    $this->assertTrue($entity->name[0]->isEmpty(), format_string('%entity_type: Name item is empty.', array('%entity_type' => $entity_type)));
    $this->assertTrue($entity->name->isEmpty(), format_string('%entity_type: Name field is empty.', array('%entity_type' => $entity_type)));
    $this->assertEqual(count($entity->name), 1, format_string('%entity_type: Empty item is considered when counting.', array('%entity_type' => $entity_type)));
    $this->assertEqual(count(iterator_to_array($entity->name->getIterator())), count($entity->name), format_string('%entity_type: Count matches iterator count.', array('%entity_type' => $entity_type)));
    $this->assertTrue($entity->name->getValue() === array(0 => array('value' => NULL)), format_string('%entity_type: Name field value contains a NULL value.', array('%entity_type' => $entity_type)));

    // Test using filterEmptyItems().
    $entity->name = array(NULL, 'foo');
    $this->assertEqual(count($entity->name), 2, format_string('%entity_type: List has 2 items.', array('%entity_type' => $entity_type)));
    $entity->name->filterEmptyItems();
    $this->assertEqual(count($entity->name), 1, format_string('%entity_type: The empty item was removed.', array('%entity_type' => $entity_type)));
    $this->assertEqual($entity->name[0]->value, 'foo', format_string('%entity_type: The items were renumbered.', array('%entity_type' => $entity_type)));
    $this->assertEqual($entity->name[0]->getName(), 0, format_string('%entity_type: The deltas were updated in the items.', array('%entity_type' => $entity_type)));

    // Test get and set field values.
    $entity->name = 'foo';
    $this->assertEqual($entity->name[0]->toArray(), array('value' => 'foo'), format_string('%entity_type: Field value has been retrieved via toArray()', array('%entity_type' => $entity_type)));

    $values = $entity->toArray();
    $this->assertEqual($values['name'], array(0 => array('value' => 'foo')), format_string('%entity_type: Field value has been retrieved via toArray() from an entity.', array('%entity_type' => $entity_type)));

    // Make sure the user id can be set to zero.
    $user_item[0]['target_id'] = 0;
    $entity = entity_create($entity_type, array(
      'name' => $name_item,
      'user_id' => $user_item,
      'field_test_text' => $text_item,
    ));
    $this->assertNotNull($entity->user_id->target_id, format_string('%entity_type: User id is not NULL', array('%entity_type' => $entity_type)));
    $this->assertIdentical($entity->user_id->target_id, 0, format_string('%entity_type: User id has been set to 0', array('%entity_type' => $entity_type)));

    // Test setting the ID with the value only.
    $entity = entity_create($entity_type, array(
      'name' => $name_item,
      'user_id' => 0,
      'field_test_text' => $text_item,
    ));
    $this->assertNotNull($entity->user_id->target_id, format_string('%entity_type: User id is not NULL', array('%entity_type' => $entity_type)));
    $this->assertIdentical($entity->user_id->target_id, 0, format_string('%entity_type: User id has been set to 0', array('%entity_type' => $entity_type)));
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
    $langcode_key = $this->entityManager->getDefinition($entity_type)->getKey('langcode');
    $entity = $this->createTestEntity($entity_type);
    $entity->save();
    $this->assertTrue((bool) $entity->id(), format_string('%entity_type: Entity has received an id.', array('%entity_type' => $entity_type)));

    $entity = entity_load($entity_type, $entity->id());
    $this->assertTrue((bool) $entity->id(), format_string('%entity_type: Entity loaded.', array('%entity_type' => $entity_type)));

    // Access the name field.
    $this->assertEqual(1, $entity->id->value, format_string('%entity_type: ID value can be read.', array('%entity_type' => $entity_type)));
    $this->assertTrue(is_string($entity->uuid->value), format_string('%entity_type: UUID value can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual('en', $entity->{$langcode_key}->value, format_string('%entity_type: Language code can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual(\Drupal::languageManager()->getLanguage('en'), $entity->{$langcode_key}->language, format_string('%entity_type: Language object can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($this->entityUser->id(), $entity->user_id->target_id, format_string('%entity_type: User id can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($this->entityUser->getUsername(), $entity->user_id->entity->name->value, format_string('%entity_type: User name can be read.', array('%entity_type' => $entity_type)));
    $this->assertEqual($this->entityFieldText, $entity->field_test_text->value, format_string('%entity_type: Text field can be read.', array('%entity_type' => $entity_type)));
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
    $definitions = \Drupal::entityManager()->getFieldDefinitions($entity_type, $entity_type);
    $this->assertEqual($definitions['name']->getType(), 'string', $entity_type .': Name field found.');
    $this->assertEqual($definitions['user_id']->getType(), 'entity_reference', $entity_type .': User field found.');
    $this->assertEqual($definitions['field_test_text']->getType(), 'text', $entity_type .': Test-text-field field found.');

    // Test deriving further metadata.
    $this->assertTrue($definitions['name'] instanceof FieldDefinitionInterface);
    $field_item_definition = $definitions['name']->getItemDefinition();
    $this->assertTrue($field_item_definition instanceof ComplexDataDefinitionInterface);
    $this->assertEqual($field_item_definition->getDataType(), 'field_item:string');
    $value_definition = $field_item_definition->getPropertyDefinition('value');
    $this->assertTrue($value_definition instanceof DataDefinitionInterface);
    $this->assertEqual($value_definition->getDataType(), 'string');

    // Test deriving metadata from references.
    $entity_definition = \Drupal\Core\Entity\TypedData\EntityDataDefinition::create($entity_type);
    $langcode_key = $this->entityManager->getDefinition($entity_type)->getKey('langcode');
    $reference_definition = $entity_definition->getPropertyDefinition($langcode_key)
      ->getPropertyDefinition('language')
      ->getTargetDefinition();
    $this->assertEqual($reference_definition->getDataType(), 'language');

    $reference_definition = $entity_definition->getPropertyDefinition('user_id')
      ->getPropertyDefinition('entity')
      ->getTargetDefinition();

    $this->assertTrue($reference_definition instanceof \Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface, 'Definition of the referenced user retrieved.');
    $this->assertEqual($reference_definition->getEntityTypeId(), 'user', 'Referenced entity is of type "user".');

    // Test propagating down.
    $name_definition = $reference_definition->getPropertyDefinition('name');
    $this->assertTrue($name_definition instanceof FieldDefinitionInterface);
    $this->assertEqual($name_definition->getPropertyDefinition('value')->getDataType(), 'string');

    // Test introspecting an entity object.
    // @todo: Add bundles and test bundles as well.
    $entity = entity_create($entity_type);

    $definitions = $entity->getFieldDefinitions();
    $this->assertEqual($definitions['name']->getType(), 'string', $entity_type .': Name field found.');
    $this->assertEqual($definitions['user_id']->getType(), 'entity_reference', $entity_type .': User field found.');
    $this->assertEqual($definitions['field_test_text']->getType(), 'text', $entity_type .': Test-text-field field found.');

    $name_properties = $entity->name->getFieldDefinition()->getPropertyDefinitions();
    $this->assertEqual($name_properties['value']->getDataType(), 'string', $entity_type .': String value property of the name found.');

    $userref_properties = $entity->user_id->getFieldDefinition()->getPropertyDefinitions();
    $this->assertEqual($userref_properties['target_id']->getDataType(), 'integer', $entity_type .': Entity id property of the user found.');
    $this->assertEqual($userref_properties['entity']->getDataType(), 'entity_reference', $entity_type .': Entity reference property of the user found.');

    $textfield_properties = $entity->field_test_text->getFieldDefinition()->getFieldStorageDefinition()->getPropertyDefinitions();
    $this->assertEqual($textfield_properties['value']->getDataType(), 'string', $entity_type .': String value property of the test-text field found.');
    $this->assertEqual($textfield_properties['format']->getDataType(), 'filter_format', $entity_type .': String format field of the test-text field found.');
    $this->assertEqual($textfield_properties['processed']->getDataType(), 'string', $entity_type .': String processed property of the test-text field found.');

    // Make sure provided contextual information is right.
    $entity_adapter = $entity->getTypedData();
    $this->assertIdentical($entity_adapter->getRoot(), $entity_adapter, 'Entity is root object.');
    $this->assertEqual($entity_adapter->getPropertyPath(), '');
    $this->assertEqual($entity_adapter->getName(), '');
    $this->assertEqual($entity_adapter->getParent(), NULL);

    $field = $entity->user_id;
    $this->assertIdentical($field->getRoot()->getValue(), $entity, 'Entity is root object.');
    $this->assertIdentical($field->getEntity(), $entity, 'getEntity() returns the entity.');
    $this->assertEqual($field->getPropertyPath(), 'user_id');
    $this->assertEqual($field->getName(), 'user_id');
    $this->assertIdentical($field->getParent()->getValue(), $entity, 'Parent object matches.');

    $field_item = $field[0];
    $this->assertIdentical($field_item->getRoot()->getValue(), $entity, 'Entity is root object.');
    $this->assertIdentical($field_item->getEntity(), $entity, 'getEntity() returns the entity.');
    $this->assertEqual($field_item->getPropertyPath(), 'user_id.0');
    $this->assertEqual($field_item->getName(), '0');
    $this->assertIdentical($field_item->getParent(), $field, 'Parent object matches.');

    $item_value = $field_item->get('entity');
    $this->assertIdentical($item_value->getRoot()->getValue(), $entity, 'Entity is root object.');
    $this->assertEqual($item_value->getPropertyPath(), 'user_id.0.entity');
    $this->assertEqual($item_value->getName(), 'entity');
    $this->assertIdentical($item_value->getParent(), $field_item, 'Parent object matches.');
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
      $this->assertTrue($field instanceof FieldItemListInterface, $entity_type . ": Field $name implements interface.");

      foreach ($field as $delta => $item) {
        $this->assertTrue($field[0] instanceof FieldItemInterface, $entity_type . ": Item $delta of field $name implements interface.");

        foreach ($item as $value_name => $value_property) {
          $this->assertTrue($value_property instanceof TypedDataInterface, $entity_type . ": Value $value_name of item $delta of field $name implements interface.");

          $value = $value_property->getValue();
          $this->assertTrue(!isset($value) || is_scalar($value) || $value instanceof EntityInterface, $entity_type . ": Value $value_name of item $delta of field $name is a primitive or an entity.");
        }
      }
    }

    $fields = $entity->getFields();
    $this->assertEqual(array_keys($fields), array_keys($entity->getTypedData()->getDataDefinition()->getPropertyDefinitions()), format_string('%entity_type: All fields returned.', array('%entity_type' => $entity_type)));
    $this->assertEqual($fields, iterator_to_array($entity->getIterator()), format_string('%entity_type: Entity iterator iterates over all fields.', array('%entity_type' => $entity_type)));
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
    $strings = array();
    $this->getContainedStrings($entity->getTypedData(), 0, $strings);

    // @todo: Once the user entity has defined properties this should contain
    // the user name and other user entity strings as well.
    $target_strings = array(
      $entity->uuid->value,
      'en',
      $this->entityName,
      // Bundle name.
      $entity->bundle(),
      $this->entityFieldText,
      // Field format.
      NULL,
    );
    asort($strings);
    asort($target_strings);
    $this->assertEqual(array_values($strings), array_values($target_strings), format_string('%entity_type: All contained strings found.', array('%entity_type' => $entity_type)));
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
      if ($wrapper instanceof \Drupal\Core\TypedData\ListInterface) {
        foreach ($wrapper as $item) {
          $this->getContainedStrings($item, $depth + 1, $strings);
        }
      }
      elseif ($wrapper instanceof \Drupal\Core\TypedData\ComplexDataInterface) {
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
      $this->assertTrue($types['entity:' . $entity_type]['class'], 'Entity data type registered.');
    }
    // Check bundle types are provided as well.
    entity_test_create_bundle('bundle');
    $types = \Drupal::typedDataManager()->getDefinitions();
    $this->assertTrue($types['entity:entity_test:bundle']['class'], 'Entity bundle data type registered.');
  }

  /**
   * Tests a base field override on a non-existing base field.
   *
   * @see entity_test_entity_base_field_info_alter()
   */
  public function testBaseFieldNonExistingBaseField() {
    $this->entityManager->getStorage('node_type')->create(array(
      'type' => 'page',
      'name' => 'page',
    ))->save();
    $this->entityManager->clearCachedFieldDefinitions();
    $fields = $this->entityManager->getFieldDefinitions('node', 'page');
    $override = $fields['status']->getConfig('page');
    $override->setLabel($this->randomString())->save();
    \Drupal::state()->set('entity_test.node_remove_status_field', TRUE);
    $this->entityManager->clearCachedFieldDefinitions();
    $fields = $this->entityManager->getFieldDefinitions('node', 'page');
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
    $field_definitions = $this->entityManager->getFieldDefinitions('entity_test_field_override', 'entity_test_field_override');
    $this->assertEqual($field_definitions['name']->getDescription(), 'The default description.');
    $this->assertNull($field_definitions['name']->getTargetBundle());

    $field_definitions = $this->entityManager->getFieldDefinitions('entity_test_field_override', 'some_test_bundle');
    $this->assertEqual($field_definitions['name']->getDescription(), 'Custom description.');
    $this->assertEqual($field_definitions['name']->getTargetBundle(), 'some_test_bundle');

    // Now create a config override of the bundle field.
    $field_config = $field_definitions['name']->getConfig('some_test_bundle');
    $field_config->setTranslatable(FALSE);
    $field_config->save();

    // Make sure both overrides are present.
    $this->entityManager->clearCachedFieldDefinitions();
    $field_definitions = $this->entityManager->getFieldDefinitions('entity_test_field_override', 'some_test_bundle');
    $this->assertEqual($field_definitions['name']->getDescription(), 'Custom description.');
    $this->assertEqual($field_definitions['name']->getTargetBundle(), 'some_test_bundle');
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
    $reference = $reference_field->appendItem(array('entity' => $entity))->get('entity');

    // Test validation the typed data object.
    $violations = $reference->validate();
    $this->assertEqual($violations->count(), 0);

    // Test validating an entity of the wrong type.
    $user = $this->createUser();
    $user->save();
    $node = entity_create('node', array(
      'type' => 'page',
      'uid' => $user->id(),
      'title' => $this->randomString(),
    ));
    $reference->setValue($node);
    $violations = $reference->validate();
    $this->assertEqual($violations->count(), 1);

    // Test bundle validation.
    NodeType::create(array('type' => 'article'))
      ->save();
    $definition = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Test entity')
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', ['target_bundles' => ['article' => 'article']]);
    $reference_field = \Drupal::TypedDataManager()->create($definition);
    $reference = $reference_field->appendItem(array('entity' => $node));
    $violations = $reference->validate();
    $this->assertEqual($violations->count(), 1);

    $node = entity_create('node', array(
      'type' => 'article',
      'uid' => $user->id(),
      'title' => $this->randomString(),
    ));
    $node->save();
    $reference->setValue($node);
    $violations = $reference->validate();
    $this->assertEqual($violations->count(), 0);
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
    $this->assertEqual($entity->field_test_text->processed, $target, format_string('%entity_type: Text is processed with the default filter.', array('%entity_type' => $entity_type)));

    // Save and load entity and make sure it still works.
    $entity->save();
    $entity = entity_load($entity_type, $entity->id());
    $this->assertEqual($entity->field_test_text->processed, $target, format_string('%entity_type: Text is processed with the default filter.', array('%entity_type' => $entity_type)));
  }

}
