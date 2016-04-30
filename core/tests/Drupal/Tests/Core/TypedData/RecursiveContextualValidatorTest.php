<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\TypedData\RecursiveContextualValidatorTest.
 */

namespace Drupal\Tests\Core\TypedData;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Core\TypedData\Validation\ExecutionContextFactory;
use Drupal\Core\TypedData\Validation\RecursiveValidator;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @coversDefaultClass \Drupal\Core\TypedData\Validation\RecursiveContextualValidator
 * @group typedData
 */
class RecursiveContextualValidatorTest extends UnitTestCase {

  /**
   * The type data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * The recursive validator.
   *
   * @var \Drupal\Core\TypedData\Validation\RecursiveValidator
   */
  protected $recursiveValidator;

  /**
   * The validator factory.
   *
   * @var \Symfony\Component\Validator\ConstraintValidatorFactoryInterface
   */
  protected $validatorFactory;

  /**
   * The execution context factory.
   *
   * @var \Drupal\Core\TypedData\Validation\ExecutionContextFactory
   */
  protected $contextFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $cache_backend = new NullBackend('cache');
    $namespaces = new \ArrayObject([
      'Drupal\\Core\\TypedData' => $this->root . '/core/lib/Drupal/Core/TypedData',
      'Drupal\\Core\\Validation' => $this->root . '/core/lib/Drupal/Core/Validation',
    ]);
    $module_handler = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandlerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $class_resolver = $this->getMockBuilder('Drupal\Core\DependencyInjection\ClassResolverInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->typedDataManager = new TypedDataManager($namespaces, $cache_backend, $module_handler, $class_resolver);
    $this->typedDataManager->setValidationConstraintManager(
      new ConstraintManager($namespaces, $cache_backend, $module_handler)
    );
    // Typed data definitions access the manager in the container.
    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $this->typedDataManager);
    \Drupal::setContainer($container);

    $translator = $this->getMock('Drupal\Core\Validation\TranslatorInterface');
    $translator->expects($this->any())
      ->method('trans')
      ->willReturnCallback(function($id) {
        return $id;
      });
    $this->contextFactory = new ExecutionContextFactory($translator);
    $this->validatorFactory = new ConstraintValidatorFactory();
    $this->recursiveValidator = new RecursiveValidator($this->contextFactory, $this->validatorFactory, $this->typedDataManager);
  }

  /**
   * Ensures that passing an explicit group is not supported.
   *
   * @covers ::validate
   *
   * @expectedException \LogicException
   */
  public function testValidateWithGroups() {
    $this->recursiveValidator->validate('test', NULL, 'test group');
  }

  /**
   * Ensures that passing a non typed data value is not supported.
   *
   * @covers ::validate
   *
   * @expectedException \InvalidArgumentException
   */
  public function testValidateWithoutTypedData() {
    $this->recursiveValidator->validate('test');
  }

  /**
   * @covers ::validate
   */
  public function testBasicValidateWithoutConstraints() {
    $typed_data = $this->typedDataManager->create(DataDefinition::create('string'));
    $violations = $this->recursiveValidator->validate($typed_data);
    $this->assertCount(0, $violations);
  }

  /**
   * @covers ::validate
   */
  public function testBasicValidateWithConstraint() {
    $typed_data = $this->typedDataManager->create(
      DataDefinition::create('string')
        ->addConstraint('Callback', [
          'callback' => function ($value, ExecutionContextInterface $context) {
            $context->addViolation('test violation: ' . $value);
          }
        ])
    );
    $typed_data->setValue('foo');

    $violations = $this->recursiveValidator->validate($typed_data);
    $this->assertCount(1, $violations);
    // Ensure that the right value is passed into the validator.
    $this->assertEquals('test violation: foo', $violations->get(0)->getMessage());
  }

  /**
   * @covers ::validate
   */
  public function testBasicValidateWithMultipleConstraints() {
    $options = [
      'callback' => function ($value, ExecutionContextInterface $context) {
        $context->addViolation('test violation');
      }
    ];
    $typed_data = $this->typedDataManager->create(
      DataDefinition::create('string')
        ->addConstraint('Callback', $options)
        ->addConstraint('NotNull')
    );
    $violations = $this->recursiveValidator->validate($typed_data);
    $this->assertCount(2, $violations);
  }

  /**
   * @covers ::validate
   */
  public function testPropertiesValidateWithMultipleLevels() {

    $typed_data = $this->buildExampleTypedDataWithProperties();

    $violations = $this->recursiveValidator->validate($typed_data);
    $this->assertCount(6, $violations);

    $this->assertEquals('violation: 3', $violations->get(0)->getMessage());
    $this->assertEquals('violation: value1', $violations->get(1)->getMessage());
    $this->assertEquals('violation: value2', $violations->get(2)->getMessage());
    $this->assertEquals('violation: 2', $violations->get(3)->getMessage());
    $this->assertEquals('violation: subvalue1', $violations->get(4)->getMessage());
    $this->assertEquals('violation: subvalue2', $violations->get(5)->getMessage());

    $this->assertEquals('', $violations->get(0)->getPropertyPath());
    $this->assertEquals('key1', $violations->get(1)->getPropertyPath());
    $this->assertEquals('key2', $violations->get(2)->getPropertyPath());
    $this->assertEquals('key_with_properties', $violations->get(3)->getPropertyPath());
    $this->assertEquals('key_with_properties.subkey1', $violations->get(4)->getPropertyPath());
    $this->assertEquals('key_with_properties.subkey2', $violations->get(5)->getPropertyPath());
  }

  /**
   * Setups a typed data object used for test purposes.
   *
   * @param array $tree
   *   An array of value, constraints and properties.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function setupTypedData(array $tree, $name = '') {
    $callback = function ($value, ExecutionContextInterface $context) {
      $context->addViolation('violation: ' . (is_array($value) ? count($value) : $value));
    };

    $tree += ['constraints' => []];

    if (isset($tree['properties'])) {
      $map_data_definition = MapDataDefinition::create();
      $map_data_definition->addConstraint('Callback', ['callback' => $callback]);
      foreach ($tree['properties'] as $property_name => $property) {
        $sub_typed_data = $this->setupTypedData($property, $property_name);
        $map_data_definition->setPropertyDefinition($property_name, $sub_typed_data->getDataDefinition());
      }
      $typed_data = $this->typedDataManager->create(
        $map_data_definition,
        $tree['value'],
        $name
      );
    }
    else {
      /** @var \Drupal\Core\TypedData\TypedDataInterface $typed_data */
      $typed_data = $this->typedDataManager->create(
        DataDefinition::create('string')
          ->addConstraint('Callback', ['callback' => $callback]),
        $tree['value'],
        $name
      );
    }

    return $typed_data;
  }

  /**
   * @covers ::validateProperty
   *
   * @expectedException \LogicException
   */
  public function testValidatePropertyWithCustomGroup() {
    $tree = [
      'value' => [],
      'properties' => [
        'key1' => ['value' => 'value1'],
      ],
    ];
    $typed_data = $this->setupTypedData($tree, 'test_name');
    $this->recursiveValidator->validateProperty($typed_data, 'key1', 'test group');
  }

  /**
   * @covers ::validateProperty
   *
   * @dataProvider providerTestValidatePropertyWithInvalidObjects
   *
   * @expectedException \InvalidArgumentException
   */
  public function testValidatePropertyWithInvalidObjects($object) {
    $this->recursiveValidator->validateProperty($object, 'key1', NULL);
  }

  /**
   * Provides data for testValidatePropertyWithInvalidObjects.
   * @return array
   */
  public function providerTestValidatePropertyWithInvalidObjects() {
    $data = [];
    $data[] = [new \stdClass()];
    $data[] = [new TestClass()];

    $data[] = [$this->getMock('Drupal\Core\TypedData\TypedDataInterface')];

    return $data;
  }

  /**
   * @covers ::validateProperty
   */
  public function testValidateProperty() {
    $typed_data = $this->buildExampleTypedDataWithProperties();

    $violations = $this->recursiveValidator->validateProperty($typed_data, 'key_with_properties');
    $this->assertCount(3, $violations);

    $this->assertEquals('violation: 2', $violations->get(0)->getMessage());
    $this->assertEquals('violation: subvalue1', $violations->get(1)->getMessage());
    $this->assertEquals('violation: subvalue2', $violations->get(2)->getMessage());

    $this->assertEquals('', $violations->get(0)->getPropertyPath());
    $this->assertEquals('subkey1', $violations->get(1)->getPropertyPath());
    $this->assertEquals('subkey2', $violations->get(2)->getPropertyPath());
  }

  /**
   * @covers ::validatePropertyValue
   *
   * @dataProvider providerTestValidatePropertyWithInvalidObjects
   *
   * @expectedException \InvalidArgumentException
   */
  public function testValidatePropertyValueWithInvalidObjects($object) {
    $this->recursiveValidator->validatePropertyValue($object, 'key1', [], NULL);
  }

  /**
   * @covers ::validatePropertyValue
   */
  public function testValidatePropertyValue() {
    $typed_data = $this->buildExampleTypedDataWithProperties(['subkey1' => 'subvalue11', 'subkey2' => 'subvalue22']);

    $violations = $this->recursiveValidator->validatePropertyValue($typed_data, 'key_with_properties', $typed_data->get('key_with_properties'));
    $this->assertCount(3, $violations);

    $this->assertEquals('violation: 2', $violations->get(0)->getMessage());
    $this->assertEquals('violation: subvalue11', $violations->get(1)->getMessage());
    $this->assertEquals('violation: subvalue22', $violations->get(2)->getMessage());

    $this->assertEquals('', $violations->get(0)->getPropertyPath());
    $this->assertEquals('subkey1', $violations->get(1)->getPropertyPath());
    $this->assertEquals('subkey2', $violations->get(2)->getPropertyPath());
  }

  /**
   * Builds some example type data object.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function buildExampleTypedDataWithProperties($subkey_value = NULL) {
    $subkey_value = $subkey_value ?: ['subkey1' => 'subvalue1', 'subkey2' => 'subvalue2'];
    $tree = [
      'value' => [
        'key1' => 'value1',
        'key2' => 'value2',
        'key_with_properties' => $subkey_value
      ],
    ];
    $tree['properties'] = [
      'key1' => [
        'value' => 'value1',
      ],
      'key2' => [
        'value' => 'value2',
      ],
      'key_with_properties' => [
        'value' => $subkey_value ?: ['subkey1' => 'subvalue1', 'subkey2' => 'subvalue2'],
        ],
    ];
    $tree['properties']['key_with_properties']['properties']['subkey1'] = ['value' => $tree['properties']['key_with_properties']['value']['subkey1']];
    $tree['properties']['key_with_properties']['properties']['subkey2'] = ['value' => $tree['properties']['key_with_properties']['value']['subkey2']];

    return $this->setupTypedData($tree, 'test_name');
  }

}

class TestClass {

}
