<?php

/**
 * @file
 * Definition of Drupal\system\Tests\TypedData\TypedDataTest.
 */

namespace Drupal\system\Tests\TypedData;

use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Tests primitive data types.
 */
class TypedDataTest extends DrupalUnitTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'entity', 'field', 'file', 'user');

  public static function getInfo() {
    return array(
      'name' => 'Test typed data objects',
      'description' => 'Tests the functionality of all core data types.',
      'group' => 'Typed Data API',
    );
  }

  public function setUp() {
    parent::setup();

    $this->installSchema('file', array('file_managed', "file_usage"));
    $this->typedDataManager = $this->container->get('typed_data_manager');
  }

  /**
   * Creates a typed data object and ensures it implements TypedDataInterface.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create().
   */
  protected function createTypedData($definition, $value = NULL, $name = NULL) {
    if (is_array($definition)) {
      $definition = DataDefinition::create($definition['type']);
    }
    $data = $this->typedDataManager->create($definition, $value, $name);
    $this->assertTrue($data instanceof \Drupal\Core\TypedData\TypedDataInterface, 'Typed data object is an instance of the typed data interface.');
    return $data;
  }

  /**
   * Tests the basics around constructing and working with typed data objects.
   */
  public function testGetAndSet() {
    // Boolean type.
    $typed_data = $this->createTypedData(array('type' => 'boolean'), TRUE);
    $this->assertTrue($typed_data instanceof \Drupal\Core\TypedData\Type\BooleanInterface, 'Typed data object is an instance of BooleanInterface.');
    $this->assertTrue($typed_data->getValue() === TRUE, 'Boolean value was fetched.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(FALSE);
    $this->assertTrue($typed_data->getValue() === FALSE, 'Boolean value was changed.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $this->assertTrue(is_string($typed_data->getString()), 'Boolean value was converted to string');
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Boolean wrapper is null-able.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue('invalid');
    $this->assertEqual($typed_data->validate()->count(), 1, 'Validation detected invalid value.');

    // String type.
    $value = $this->randomString();
    $typed_data = $this->createTypedData(array('type' => 'string'), $value);
    $this->assertTrue($typed_data instanceof \Drupal\Core\TypedData\Type\StringInterface, 'Typed data object is an instance of StringInterface.');
    $this->assertTrue($typed_data->getValue() === $value, 'String value was fetched.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $new_value = $this->randomString();
    $typed_data->setValue($new_value);
    $this->assertTrue($typed_data->getValue() === $new_value, 'String value was changed.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    // Funky test.
    $this->assertTrue(is_string($typed_data->getString()), 'String value was converted to string');
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'String wrapper is null-able.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(array('no string'));
    $this->assertEqual($typed_data->validate()->count(), 1, 'Validation detected invalid value.');

    // Integer type.
    $value = rand();
    $typed_data = $this->createTypedData(array('type' => 'integer'), $value);
    $this->assertTrue($typed_data instanceof \Drupal\Core\TypedData\Type\IntegerInterface, 'Typed data object is an instance of IntegerInterface.');
    $this->assertTrue($typed_data->getValue() === $value, 'Integer value was fetched.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $new_value = rand();
    $typed_data->setValue($new_value);
    $this->assertTrue($typed_data->getValue() === $new_value, 'Integer value was changed.');
    $this->assertTrue(is_string($typed_data->getString()), 'Integer value was converted to string');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Integer wrapper is null-able.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue('invalid');
    $this->assertEqual($typed_data->validate()->count(), 1, 'Validation detected invalid value.');

    // Float type.
    $value = 123.45;
    $typed_data = $this->createTypedData(array('type' => 'float'), $value);
    $this->assertTrue($typed_data instanceof \Drupal\Core\TypedData\Type\FloatInterface, 'Typed data object is an instance of FloatInterface.');
    $this->assertTrue($typed_data->getValue() === $value, 'Float value was fetched.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $new_value = 678.90;
    $typed_data->setValue($new_value);
    $this->assertTrue($typed_data->getValue() === $new_value, 'Float value was changed.');
    $this->assertTrue(is_string($typed_data->getString()), 'Float value was converted to string');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Float wrapper is null-able.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue('invalid');
    $this->assertEqual($typed_data->validate()->count(), 1, 'Validation detected invalid value.');

    // Date Time type.
    $value = '2014-01-01T20:00:00+00:00';
    $typed_data = $this->createTypedData(array('type' => 'datetime_iso8601'), $value);
    $this->assertTrue($typed_data instanceof \Drupal\Core\TypedData\Type\DateTimeInterface, 'Typed data object is an instance of DateTimeInterface.');
    $this->assertTrue($typed_data->getValue() == $value, 'Date value was fetched.');
    $this->assertEqual($typed_data->getValue(), $typed_data->getDateTime()->format('c'), 'Value representation of a date is ISO 8601');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $new_value = '2014-01-02T20:00:00+00:00';
    $typed_data->setValue($new_value);
    $this->assertTrue($typed_data->getDateTime()->format('c') === $new_value, 'Date value was changed and set by an ISO8601 date.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $this->assertTrue($typed_data->getDateTime()->format('Y-m-d') == '2014-01-02', 'Date value was changed and set by date string.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDateTime(), 'Date wrapper is null-able.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue('invalid');
    $this->assertEqual($typed_data->validate()->count(), 1, 'Validation detected invalid value.');
    // Check implementation of DateTimeInterface.
    $typed_data = $this->createTypedData(array('type' => 'datetime_iso8601'), '2014-01-01T20:00:00+00:00');
    $this->assertTrue($typed_data->getDateTime() instanceof DrupalDateTime);
    $typed_data->setDateTime(new DrupalDateTime('2014-01-02T20:00:00+00:00'));
    $this->assertEqual($typed_data->getValue(), '2014-01-02T20:00:00+00:00');
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDateTime());

    // Timestamp type.
    $value = REQUEST_TIME;
    $typed_data = $this->createTypedData(array('type' => 'timestamp'), $value);
    $this->assertTrue($typed_data instanceof \Drupal\Core\TypedData\Type\DateTimeInterface, 'Typed data object is an instance of DateTimeInterface.');
    $this->assertTrue($typed_data->getValue() == $value, 'Timestamp value was fetched.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $new_value = REQUEST_TIME + 1;
    $typed_data->setValue($new_value);
    $this->assertTrue($typed_data->getValue() === $new_value, 'Timestamp value was changed and set.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDateTime(), 'Timestamp wrapper is null-able.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue('invalid');
    $this->assertEqual($typed_data->validate()->count(), 1, 'Validation detected invalid value.');
    // Check implementation of DateTimeInterface.
    $typed_data = $this->createTypedData(array('type' => 'timestamp'), REQUEST_TIME);
    $this->assertTrue($typed_data->getDateTime() instanceof DrupalDateTime);
    $typed_data->setDateTime(DrupalDateTime::createFromTimestamp(REQUEST_TIME + 1));
    $this->assertEqual($typed_data->getValue(), REQUEST_TIME + 1);
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDateTime());

    // DurationIso8601 type.
    $value = 'PT20S';
    $typed_data = $this->createTypedData(array('type' => 'duration_iso8601'), $value);
    $this->assertTrue($typed_data instanceof \Drupal\Core\TypedData\Type\DurationInterface, 'Typed data object is an instance of DurationInterface.');
    $this->assertIdentical($typed_data->getValue(), $value, 'DurationIso8601 value was fetched.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue('P40D');
    $this->assertEqual($typed_data->getDuration()->d, 40, 'DurationIso8601 value was changed and set by duration string.');
    $this->assertTrue(is_string($typed_data->getString()), 'DurationIso8601 value was converted to string');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'DurationIso8601 wrapper is null-able.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue('invalid');
    $this->assertEqual($typed_data->validate()->count(), 1, 'Validation detected invalid value.');
    // Check implementation of DurationInterface.
    $typed_data = $this->createTypedData(array('type' => 'duration_iso8601'), 'PT20S');
    $this->assertTrue($typed_data->getDuration() instanceof \DateInterval);
    $typed_data->setDuration(new \DateInterval('P40D'));
    // @todo: Should we make this "nicer"?
    $this->assertEqual($typed_data->getValue(), 'P0Y0M40DT0H0M0S');
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDuration());

    // Time span type.
    $value = 20;
    $typed_data = $this->createTypedData(array('type' => 'timespan'), $value);
    $this->assertTrue($typed_data instanceof \Drupal\Core\TypedData\Type\DurationInterface, 'Typed data object is an instance of DurationInterface.');
    $this->assertIdentical($typed_data->getValue(), $value, 'Time span value was fetched.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(60 * 60 * 4);
    $this->assertEqual($typed_data->getDuration()->s, 14400, 'Time span was changed');
    $this->assertTrue(is_string($typed_data->getString()), 'Time span value was converted to string');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Time span wrapper is null-able.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue('invalid');
    $this->assertEqual($typed_data->validate()->count(), 1, 'Validation detected invalid value.');
    // Check implementation of DurationInterface.
    $typed_data = $this->createTypedData(array('type' => 'timespan'), 20);
    $this->assertTrue($typed_data->getDuration() instanceof \DateInterval);
    $typed_data->setDuration(new \DateInterval('PT4H'));
    $this->assertEqual($typed_data->getValue(), 60 * 60 * 4);
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDuration());

    // URI type.
    $uri = 'http://example.com/foo/';
    $typed_data = $this->createTypedData(array('type' => 'uri'), $uri);
    $this->assertTrue($typed_data instanceof \Drupal\Core\TypedData\Type\UriInterface, 'Typed data object is an instance of UriInterface.');
    $this->assertTrue($typed_data->getValue() === $uri, 'URI value was fetched.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue($uri . 'bar.txt');
    $this->assertTrue($typed_data->getValue() === $uri . 'bar.txt', 'URI value was changed.');
    $this->assertTrue(is_string($typed_data->getString()), 'URI value was converted to string');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'URI wrapper is null-able.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue('invalid');
    $this->assertEqual($typed_data->validate()->count(), 1, 'Validation detected invalid value.');


    // Generate some files that will be used to test the binary data type.
    $files = array();
    for ($i = 0; $i < 3; $i++){
      $path = "public://example_$i.png";
      file_unmanaged_copy(DRUPAL_ROOT . '/core/misc/druplicon.png', $path);
      $image = entity_create('file', array('uri' => $path));
      $image->save();
      $files[] = $image;
    }

    // Email type.
    $value = $this->randomString();
    $typed_data = $this->createTypedData(array('type' => 'email'), $value);
    $this->assertTrue($typed_data instanceof \Drupal\Core\TypedData\Type\StringInterface, 'Typed data object is an instance of StringInterface.');
    $this->assertIdentical($typed_data->getValue(), $value, 'E-mail value was fetched.');
    $new_value = 'test@example.com';
    $typed_data->setValue($new_value);
    $this->assertIdentical($typed_data->getValue(), $new_value, 'E-mail value was changed.');
    $this->assertTrue(is_string($typed_data->getString()), 'E-mail value was converted to string');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'E-mail wrapper is null-able.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue('invalidATexample.com');
    $this->assertEqual($typed_data->validate()->count(), 1, 'Validation detected invalid value.');

    // Binary type.
    $typed_data = $this->createTypedData(array('type' => 'binary'), $files[0]->getFileUri());
    $this->assertTrue($typed_data instanceof \Drupal\Core\TypedData\Type\BinaryInterface, 'Typed data object is an instance of BinaryInterface.');
    $this->assertTrue(is_resource($typed_data->getValue()), 'Binary value was fetched.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    // Try setting by URI.
    $typed_data->setValue($files[1]->getFileUri());
    $this->assertEqual(is_resource($typed_data->getValue()), fopen($files[1]->getFileUri(), 'r'), 'Binary value was changed.');
    $this->assertTrue(is_string($typed_data->getString()), 'Binary value was converted to string');
    $this->assertEqual($typed_data->validate()->count(), 0);
    // Try setting by resource.
    $typed_data->setValue(fopen($files[2]->getFileUri(), 'r'));
    $this->assertEqual(is_resource($typed_data->getValue()), fopen($files[2]->getFileUri(), 'r'), 'Binary value was changed.');
    $this->assertTrue(is_string($typed_data->getString()), 'Binary value was converted to string');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Binary wrapper is null-able.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue('invalid');
    $this->assertEqual($typed_data->validate()->count(), 1, 'Validation detected invalid value.');

    // Any type.
    $value = array('foo');
    $typed_data = $this->createTypedData(array('type' => 'any'), $value);
    $this->assertIdentical($typed_data->getValue(), $value, 'Any value was fetched.');
    $new_value = 'test@example.com';
    $typed_data->setValue($new_value);
    $this->assertIdentical($typed_data->getValue(), $new_value, 'Any value was changed.');
    $this->assertTrue(is_string($typed_data->getString()), 'Any value was converted to string');
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Any wrapper is null-able.');
    $this->assertEqual($typed_data->validate()->count(), 0);
    // We cannot test invalid values as everything is valid for the any type,
    // but make sure an array or object value passes validation also.
    $typed_data->setValue(array('entry'));
    $this->assertEqual($typed_data->validate()->count(), 0);
    $typed_data->setValue((object) array('entry'));
    $this->assertEqual($typed_data->validate()->count(), 0);
  }

  /**
   * Tests using typed data lists.
   */
  public function testTypedDataLists() {
    // Test working with an existing list of strings.
    $value = array('one', 'two', 'three');
    $typed_data = $this->createTypedData(ListDataDefinition::create('string'), $value);
    $this->assertEqual($typed_data->getValue(), $value, 'List value has been set.');
    // Test iterating.
    $count = 0;
    foreach ($typed_data as $item) {
      $this->assertTrue($item instanceof \Drupal\Core\TypedData\TypedDataInterface);
      $count++;
    }
    $this->assertEqual($count, 3);

    // Test getting the string representation.
    $this->assertEqual($typed_data->getString(), 'one, two, three');
    $typed_data[1] = '';
    $this->assertEqual($typed_data->getString(), 'one, three');

    // Test using array access.
    $this->assertEqual($typed_data[0]->getValue(), 'one');
    $typed_data[4] = 'four';
    $this->assertEqual($typed_data[4]->getValue(), 'four');
    $typed_data[] = 'five';
    $this->assertEqual($typed_data[5]->getValue(), 'five');
    $this->assertEqual($typed_data->count(), 5);
    $this->assertTrue(isset($typed_data[0]));
    $this->assertTrue(!isset($typed_data[6]));

    // Test isEmpty and cloning.
    $this->assertFalse($typed_data->isEmpty());
    $clone = clone $typed_data;
    $this->assertTrue($typed_data->getValue() === $clone->getValue());
    $this->assertTrue($typed_data[0] !== $clone[0]);
    $clone->setValue(array());
    $this->assertTrue($clone->isEmpty());

    // Make sure that resetting the value using NULL results in an empty array.
    $clone->setValue(array());
    $typed_data->setValue(NULL);
    $this->assertIdentical($typed_data->getValue(), array());
    $this->assertIdentical($clone->getValue(), array());

    // Test dealing with NULL items.
    $typed_data[] = NULL;
    $this->assertTrue($typed_data->isEmpty());
    $this->assertEqual(count($typed_data), 1);
    $typed_data[] = '';
    $this->assertFalse($typed_data->isEmpty());
    $this->assertEqual(count($typed_data), 2);
    $typed_data[] = 'three';
    $this->assertFalse($typed_data->isEmpty());
    $this->assertEqual(count($typed_data), 3);

    $this->assertEqual($typed_data->getValue(), array(NULL, '', 'three'));
    // Test unsetting.
    unset($typed_data[2]);
    $this->assertEqual(count($typed_data), 2);
    $this->assertNull($typed_data[3]->getValue());

    // Getting a not set list item sets it.
    $this->assertNull($typed_data[4]->getValue());
    $this->assertEqual(count($typed_data), 4);

    // Test setting the list with less values.
    $typed_data->setValue(array('one'));
    $this->assertEqual($typed_data->count(), 1);

    // Test setting invalid values.
    try {
      $typed_data->setValue(array('not a list' => 'one'));
      $this->fail('No exception has been thrown when setting an invalid value.');
    }
    catch (\Exception $e) {
      $this->pass('Exception thrown:' . $e->getMessage());
    }
    try {
      $typed_data->setValue('string');
      $this->fail('No exception has been thrown when setting an invalid value.');
    }
    catch (\Exception $e) {
      $this->pass('Exception thrown:' . $e->getMessage());
    }
  }

  /**
   * Tests using a typed data map.
   */
  public function testTypedDataMaps() {
    // Test working with a simple map.
    $value = array(
      'one' => 'eins',
      'two' => 'zwei',
      'three' => 'drei',
    );
    $definition = MapDataDefinition::create()
      ->setPropertyDefinition('one', DataDefinition::create('string'))
      ->setPropertyDefinition('two', DataDefinition::create('string'))
      ->setPropertyDefinition('three', DataDefinition::create('string'));

    $typed_data = $this->createTypedData($definition, $value);

    // Test iterating.
    $count = 0;
    foreach ($typed_data as $item) {
      $this->assertTrue($item instanceof \Drupal\Core\TypedData\TypedDataInterface);
      $count++;
    }
    $this->assertEqual($count, 3);

    // Test retrieving metadata.
    $this->assertEqual(array_keys($typed_data->getDataDefinition()->getPropertyDefinitions()), array_keys($value));
    $definition = $typed_data->getDataDefinition()->getPropertyDefinition('one');
    $this->assertEqual($definition->getDataType(), 'string');
    $this->assertNull($typed_data->getDataDefinition()->getPropertyDefinition('invalid'));

    // Test getting and setting properties.
    $this->assertEqual($typed_data->get('one')->getValue(), 'eins');
    $this->assertEqual($typed_data->toArray(), $value);
    $typed_data->set('one', 'uno');
    $this->assertEqual($typed_data->get('one')->getValue(), 'uno');
    // Make sure the update is reflected in the value of the map also.
    $value = $typed_data->getValue();
    $this->assertEqual($value, array(
      'one' => 'uno',
      'two' => 'zwei',
      'three' => 'drei'
    ));

    $properties = $typed_data->getProperties();
    $this->assertEqual(array_keys($properties), array_keys($value));
    $this->assertIdentical($properties['one'], $typed_data->get('one'), 'Properties are identical.');

    // Test setting a not defined property. It shouldn't show up in the
    // properties, but be kept in the values.
    $typed_data->setValue(array('foo' => 'bar'));
    $this->assertEqual(array_keys($typed_data->getProperties()), array('one', 'two', 'three'));
    $this->assertEqual(array_keys($typed_data->getValue()), array('foo', 'one', 'two', 'three'));

    // Test getting the string representation.
    $typed_data->setValue(array('one' => 'eins', 'two' => '', 'three' => 'drei'));
    $this->assertEqual($typed_data->getString(), 'eins, drei');

    // Test isEmpty and cloning.
    $this->assertFalse($typed_data->isEmpty());
    $clone = clone $typed_data;
    $this->assertTrue($typed_data->getValue() === $clone->getValue());
    $this->assertTrue($typed_data->get('one') !== $clone->get('one'));
    $clone->setValue(array());
    $this->assertTrue($clone->isEmpty());

    // Make sure the difference between NULL (not set) and an empty array is
    // kept.
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue());
    $typed_data->setValue(array());
    $value = $typed_data->getValue();
    $this->assertTrue(isset($value) && is_array($value));

    // Test accessing invalid properties.
    $typed_data->setValue($value);
    try {
      $typed_data->get('invalid');
      $this->fail('No exception has been thrown when getting an invalid value.');
    }
    catch (\Exception $e) {
      $this->pass('Exception thrown:' . $e->getMessage());
    }

    // Test setting invalid values.
    try {
      $typed_data->setValue('invalid');
      $this->fail('No exception has been thrown when setting an invalid value.');
    }
    catch (\Exception $e) {
      $this->pass('Exception thrown:' . $e->getMessage());
    }

    // Test adding a new property to the map.
    $typed_data->getDataDefinition()->setPropertyDefinition('zero', DataDefinition::create('any'));
    $typed_data->set('zero', 'null');
    $this->assertEqual($typed_data->get('zero')->getValue(), 'null');
    $definition = $typed_data->get('zero')->getDataDefinition();
    $this->assertEqual($definition->getDataType(), 'any', 'Definition for a new map entry returned.');
  }

  /**
   * Tests typed data validation.
   */
  public function testTypedDataValidation() {
    $definition = DataDefinition::create('integer')
      ->setConstraints(array(
        'Range' => array('min' => 5),
      ));
    $violations = $this->typedDataManager->create($definition, 10)->validate();
    $this->assertEqual($violations->count(), 0);

    $integer = $this->typedDataManager->create($definition, 1);
    $violations = $integer->validate();
    $this->assertEqual($violations->count(), 1);

    // Test translating violation messages.
    $message = t('This value should be %limit or more.', array('%limit' => 5));
    $this->assertEqual($violations[0]->getMessage(), $message, 'Translated violation message retrieved.');
    $this->assertEqual($violations[0]->getPropertyPath(), '');
    $this->assertIdentical($violations[0]->getRoot(), $integer, 'Root object returned.');

    // Test translating violation messages when pluralization is used.
    $definition = DataDefinition::create('string')
      ->setConstraints(array(
        'Length' => array('min' => 10),
      ));
    $violations = $this->typedDataManager->create($definition, "short")->validate();
    $this->assertEqual($violations->count(), 1);
    $message = t('This value is too short. It should have %limit characters or more.', array('%limit' => 10));
    $this->assertEqual($violations[0]->getMessage(), $message, 'Translated violation message retrieved.');

    // Test having multiple violations.
    $definition = DataDefinition::create('integer')
      ->setConstraints(array(
        'Range' => array('min' => 5),
        'Null' => array(),
      ));
    $violations = $this->typedDataManager->create($definition, 10)->validate();
    $this->assertEqual($violations->count(), 1);
    $violations = $this->typedDataManager->create($definition, 1)->validate();
    $this->assertEqual($violations->count(), 2);

    // Test validating property containers and make sure the NotNull and Null
    // constraints work with typed data containers.
    $definition = FieldDefinition::create('integer')
      ->setConstraints(array('NotNull' => array()));
    $field_item = $this->typedDataManager->create($definition, array('value' => 10));
    $violations = $field_item->validate();
    $this->assertEqual($violations->count(), 0);

    $field_item = $this->typedDataManager->create($definition, array('value' => 'no integer'));
    $violations = $field_item->validate();
    $this->assertEqual($violations->count(), 1);
    $this->assertEqual($violations[0]->getPropertyPath(), '0.value');

    // Test that the field item may not be empty.
    $field_item = $this->typedDataManager->create($definition);
    $violations = $field_item->validate();
    $this->assertEqual($violations->count(), 1);

    // Test the Null constraint with typed data containers.
    $definition = FieldDefinition::create('float')
      ->setConstraints(array('Null' => array()));
    $field_item = $this->typedDataManager->create($definition, array('value' => 11.5));
    $violations = $field_item->validate();
    $this->assertEqual($violations->count(), 1);
    $field_item = $this->typedDataManager->create($definition);
    $violations = $field_item->validate();
    $this->assertEqual($violations->count(), 0);

    // Test getting constraint definitions by type.
    $definitions = $this->typedDataManager->getValidationConstraintManager()->getDefinitionsByType('entity');
    $this->assertTrue(isset($definitions['EntityType']), 'Constraint plugin found for type entity.');
    $this->assertTrue(isset($definitions['Null']), 'Constraint plugin found for type entity.');
    $this->assertTrue(isset($definitions['NotNull']), 'Constraint plugin found for type entity.');

    $definitions = $this->typedDataManager->getValidationConstraintManager()->getDefinitionsByType('string');
    $this->assertFalse(isset($definitions['EntityType']), 'Constraint plugin not found for type string.');
    $this->assertTrue(isset($definitions['Null']), 'Constraint plugin found for type string.');
    $this->assertTrue(isset($definitions['NotNull']), 'Constraint plugin found for type string.');

    // Test automatic 'required' validation.
    $definition = DataDefinition::create('integer')
      ->setRequired(TRUE);
    $violations = $this->typedDataManager->create($definition)->validate();
    $this->assertEqual($violations->count(), 1);
    $violations = $this->typedDataManager->create($definition, 0)->validate();
    $this->assertEqual($violations->count(), 0);

    // Test validating a list of a values and make sure property paths starting
    // with "0" are created.
    $definition = FieldDefinition::create('integer');
    $violations = $this->typedDataManager->create($definition, array(array('value' => 10)))->validate();
    $this->assertEqual($violations->count(), 0);
    $violations = $this->typedDataManager->create($definition, array(array('value' => 'string')))->validate();
    $this->assertEqual($violations->count(), 1);

    $this->assertEqual($violations[0]->getInvalidValue(), 'string');
    $this->assertIdentical($violations[0]->getPropertyPath(), '0.value');
  }
}
