<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Entity\Plugin\DataType\ConfigEntityAdapter;
use Drupal\Core\TypedData\Plugin\DataType\BooleanData;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests entity adapter for configuration entities.
 *
 * @see \Drupal\Core\Entity\Plugin\DataType\ConfigEntityAdapter
 *
 * @group Entity
 *
 * @coversDefaultClass \Drupal\Core\Entity\Plugin\DataType\ConfigEntityAdapter
 */
class ConfigEntityAdapterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['config_test'];

  /**
   * The config entity.
   *
   * @var \Drupal\config_test\Entity\ConfigTest
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);

    // ConfigTest::create doesn't work with the following exception:
    // "Multiple entity types found for Drupal\config_test\Entity\ConfigTest."
    $this->entity = \Drupal::entityTypeManager()->getStorage('config_test')->create([
      'id' => 'system',
      'label' => 'foobar',
      'weight' => 1,
    ]);
  }

  /**
   * @covers \Drupal\Core\Entity\Plugin\DataType\Deriver\EntityDeriver::getDerivativeDefinitions
   */
  public function testEntityDeriver() {
    $definition = \Drupal::typedDataManager()->getDefinition('entity:config_test');
    $this->assertEquals(ConfigEntityAdapter::class, $definition['class']);
  }

  /**
   * @covers ::validate
   */
  public function testValidate() {
    $adapter = ConfigEntityAdapter::createFromEntity($this->entity);
    $violations = $adapter->validate();
    $this->assertEmpty($violations);
    $this->entity = \Drupal::entityTypeManager()->getStorage('config_test')->create([
      'id' => 'system',
      'label' => 'foobar',
      // Set weight to be a string which should not validate.
      'weight' => 'very heavy',
    ]);
    $adapter = ConfigEntityAdapter::createFromEntity($this->entity);
    $violations = $adapter->validate();
    $this->assertCount(1, $violations);
    $violation = $violations->get(0);
    $this->assertEquals('This value should be of the correct primitive type.', $violation->getMessage());
    $this->assertEquals('weight', $violation->getPropertyPath());
  }

  /**
   * @covers ::getProperties
   */
  public function testGetProperties() {
    $expected_properties = [
      'uuid' => StringData::class,
      'langcode' => StringData::class,
      'status' => BooleanData::class,
      'dependencies' => Mapping::class,
      'id' => StringData::class,
      'label' => StringData::class,
      'weight' => IntegerData::class,
      'style' => StringData::class,
      'size' => StringData::class,
      'size_value' => StringData::class,
      'protected_property' => StringData::class,
    ];
    $properties = ConfigEntityAdapter::createFromEntity($this->entity)->getProperties();
    $keys = [];
    foreach ($properties as $key => $property) {
      $keys[] = $key;
      $this->assertInstanceOf($expected_properties[$key], $property);
    }
    $this->assertSame(array_keys($expected_properties), $keys);
  }

  /**
   * @covers ::getValue
   */
  public function testGetValue() {
    $adapter = ConfigEntityAdapter::createFromEntity($this->entity);
    $this->assertEquals($this->entity->weight, $adapter->get('weight')->getValue());
    $this->assertEquals($this->entity->id(), $adapter->get('id')->getValue());
    $this->assertEquals($this->entity->label, $adapter->get('label')->getValue());
  }

  /**
   * @covers ::set
   */
  public function testSet() {
    $adapter = ConfigEntityAdapter::createFromEntity($this->entity);
    // Get the value via typed data to ensure that the typed representation is
    // updated correctly when the value is set.
    $this->assertEquals(1, $adapter->get('weight')->getValue());

    $return = $adapter->set('weight', 2);
    $this->assertSame($adapter, $return);
    $this->assertEquals(2, $this->entity->weight);
    // Ensure the typed data is updated via the set too.
    $this->assertEquals(2, $adapter->get('weight')->getValue());
  }

  /**
   * @covers ::getString
   */
  public function testGetString() {
    $adapter = ConfigEntityAdapter::createFromEntity($this->entity);
    $this->assertEquals('foobar', $adapter->getString());
  }

  /**
   * @covers ::applyDefaultValue
   */
  public function testApplyDefaultValue() {
    $this->setExpectedException(\BadMethodCallException::class, 'Method not supported');
    $adapter = ConfigEntityAdapter::createFromEntity($this->entity);
    $adapter->applyDefaultValue();
  }

  /**
   * @covers ::getIterator
   */
  public function testGetIterator() {
    $adapter = ConfigEntityAdapter::createFromEntity($this->entity);
    $iterator = $adapter->getIterator();
    $fields = iterator_to_array($iterator);
    $expected_fields = [
      'uuid',
      'langcode',
      'status',
      'dependencies',
      'id',
      'label',
      'weight',
      'style',
      'size',
      'size_value',
      'protected_property',
    ];
    $this->assertEquals($expected_fields, array_keys($fields));
    $this->assertEquals($this->entity->id(), $fields['id']->getValue());

    $adapter->setValue(NULL);
    $this->assertEquals(new \ArrayIterator([]), $adapter->getIterator());
  }

}
