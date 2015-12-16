<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Field\FieldDefinitionListenerTest.
 */

namespace Drupal\Tests\Core\Field;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\DynamicallyFieldableEntityStorageInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldDefinitionListener;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Field\FieldDefinitionListener
 * @group Field
 */
class FieldDefinitionListenerTest extends UnitTestCase {

  /**
   * The key-value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $keyValueFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityFieldManager;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cacheBackend;

  /**
   * The field definition listener under test.
   *
   * @var \Drupal\Core\Field\FieldDefinitionListener
   */
  protected $fieldDefinitionListener;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->keyValueFactory = $this->prophesize(KeyValueFactoryInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);

    $this->fieldDefinitionListener = new FieldDefinitionListener($this->entityTypeManager->reveal(), $this->entityFieldManager->reveal(), $this->keyValueFactory->reveal(), $this->cacheBackend->reveal());
  }

  /**
   * Sets up the entity manager to be tested.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[]|\Prophecy\Prophecy\ProphecyInterface[] $definitions
   *   (optional) An array of entity type definitions.
   */
  protected function setUpEntityManager($definitions = array()) {
    $class = $this->getMockClass(EntityInterface::class);
    foreach ($definitions as $key => $entity_type) {
      // \Drupal\Core\Entity\EntityTypeInterface::getLinkTemplates() is called
      // by \Drupal\Core\Entity\EntityManager::processDefinition() so it must
      // always be mocked.
      $entity_type->getLinkTemplates()->willReturn([]);

      // Give the entity type a legitimate class to return.
      $entity_type->getClass()->willReturn($class);

      $definitions[$key] = $entity_type->reveal();
    }

    $this->entityTypeManager->getDefinition(Argument::cetera())
      ->will(function ($args) use ($definitions) {
        $entity_type_id = $args[0];
        $exception_on_invalid = $args[1];
        if (isset($definitions[$entity_type_id])) {
          return $definitions[$entity_type_id];
        }
        elseif (!$exception_on_invalid) {
          return NULL;
        }
        else throw new PluginNotFoundException($entity_type_id);
      });
    $this->entityTypeManager->getDefinitions()->willReturn($definitions);
  }

  /**
   * @covers ::onFieldDefinitionCreate
   */
  public function testOnFieldDefinitionCreateNewField() {
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getTargetEntityTypeId()->willReturn('test_entity_type');
    $field_definition->getTargetBundle()->willReturn('test_bundle');
    $field_definition->getName()->willReturn('test_field');
    $field_definition->getType()->willReturn('test_type');

    $storage = $this->prophesize(DynamicallyFieldableEntityStorageInterface::class);
    $storage->onFieldDefinitionCreate($field_definition->reveal())->shouldBeCalledTimes(1);
    $this->entityTypeManager->getStorage('test_entity_type')->willReturn($storage->reveal());

    $entity = $this->prophesize(EntityTypeInterface::class);
    $this->setUpEntityManager(['test_entity_type' => $entity]);

    // Set up the stored bundle field map.
    $key_value_store = $this->prophesize(KeyValueStoreInterface::class);
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->willReturn($key_value_store->reveal());
    $key_value_store->get('test_entity_type')->willReturn([]);
    $key_value_store->set('test_entity_type', [
      'test_field' => [
        'type' => 'test_type',
        'bundles' => ['test_bundle' => 'test_bundle'],
      ],
    ])->shouldBeCalled();

    $this->fieldDefinitionListener->onFieldDefinitionCreate($field_definition->reveal());
  }

  /**
   * @covers ::onFieldDefinitionCreate
   */
  public function testOnFieldDefinitionCreateExistingField() {
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getTargetEntityTypeId()->willReturn('test_entity_type');
    $field_definition->getTargetBundle()->willReturn('test_bundle');
    $field_definition->getName()->willReturn('test_field');

    $storage = $this->prophesize(DynamicallyFieldableEntityStorageInterface::class);
    $storage->onFieldDefinitionCreate($field_definition->reveal())->shouldBeCalledTimes(1);
    $this->entityTypeManager->getStorage('test_entity_type')->willReturn($storage->reveal());

    $entity = $this->prophesize(EntityTypeInterface::class);
    $this->setUpEntityManager(['test_entity_type' => $entity]);

    // Set up the stored bundle field map.
    $key_value_store = $this->prophesize(KeyValueStoreInterface::class);
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->willReturn($key_value_store->reveal());
    $key_value_store->get('test_entity_type')->willReturn([
      'test_field' => [
        'type' => 'test_type',
        'bundles' => ['existing_bundle' => 'existing_bundle'],
      ],
    ]);
    $key_value_store->set('test_entity_type', [
      'test_field' => [
        'type' => 'test_type',
        'bundles' => ['existing_bundle' => 'existing_bundle', 'test_bundle' => 'test_bundle'],
      ],
    ])
      ->shouldBeCalled();

    $this->fieldDefinitionListener->onFieldDefinitionCreate($field_definition->reveal());
  }

  /**
   * @covers ::onFieldDefinitionUpdate
   */
  public function testOnFieldDefinitionUpdate() {
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getTargetEntityTypeId()->willReturn('test_entity_type');

    $storage = $this->prophesize(DynamicallyFieldableEntityStorageInterface::class);
    $storage->onFieldDefinitionUpdate($field_definition->reveal(), $field_definition->reveal())->shouldBeCalledTimes(1);
    $this->entityTypeManager->getStorage('test_entity_type')->willReturn($storage->reveal());

    $entity = $this->prophesize(EntityTypeInterface::class);
    $this->setUpEntityManager(['test_entity_type' => $entity]);

    $this->fieldDefinitionListener->onFieldDefinitionUpdate($field_definition->reveal(), $field_definition->reveal());
  }

  /**
   * @covers ::onFieldDefinitionDelete
   */
  public function testOnFieldDefinitionDeleteMultipleBundles() {
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getTargetEntityTypeId()->willReturn('test_entity_type');
    $field_definition->getTargetBundle()->willReturn('test_bundle');
    $field_definition->getName()->willReturn('test_field');

    $storage = $this->prophesize(DynamicallyFieldableEntityStorageInterface::class);
    $storage->onFieldDefinitionDelete($field_definition->reveal())->shouldBeCalledTimes(1);
    $this->entityTypeManager->getStorage('test_entity_type')->willReturn($storage->reveal());

    $entity = $this->prophesize(EntityTypeInterface::class);
    $this->setUpEntityManager(['test_entity_type' => $entity]);

    // Set up the stored bundle field map.
    $key_value_store = $this->prophesize(KeyValueStoreInterface::class);
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->willReturn($key_value_store->reveal());
    $key_value_store->get('test_entity_type')->willReturn([
      'test_field' => [
        'type' => 'test_type',
        'bundles' => ['test_bundle' => 'test_bundle'],
      ],
      'second_field' => [
        'type' => 'test_type',
        'bundles' => ['test_bundle' => 'test_bundle'],
      ],
    ]);
    $key_value_store->set('test_entity_type', [
      'second_field' => [
        'type' => 'test_type',
        'bundles' => ['test_bundle' => 'test_bundle'],
      ],
    ])
      ->shouldBeCalled();

    $this->fieldDefinitionListener->onFieldDefinitionDelete($field_definition->reveal());
  }


  /**
   * @covers ::onFieldDefinitionDelete
   */
  public function testOnFieldDefinitionDeleteSingleBundles() {
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getTargetEntityTypeId()->willReturn('test_entity_type');
    $field_definition->getTargetBundle()->willReturn('test_bundle');
    $field_definition->getName()->willReturn('test_field');

    $storage = $this->prophesize(DynamicallyFieldableEntityStorageInterface::class);
    $storage->onFieldDefinitionDelete($field_definition->reveal())->shouldBeCalledTimes(1);
    $this->entityTypeManager->getStorage('test_entity_type')->willReturn($storage->reveal());

    $entity = $this->prophesize(EntityTypeInterface::class);
    $this->setUpEntityManager(['test_entity_type' => $entity]);

    // Set up the stored bundle field map.
    $key_value_store = $this->prophesize(KeyValueStoreInterface::class);
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->willReturn($key_value_store->reveal());
    $key_value_store->get('test_entity_type')->willReturn([
      'test_field' => [
        'type' => 'test_type',
        'bundles' => ['test_bundle' => 'test_bundle', 'second_bundle' => 'second_bundle'],
      ],
    ]);
    $key_value_store->set('test_entity_type', [
      'test_field' => [
        'type' => 'test_type',
        'bundles' => ['second_bundle' => 'second_bundle'],
      ],
    ])
      ->shouldBeCalled();

    $this->fieldDefinitionListener->onFieldDefinitionDelete($field_definition->reveal());
  }

}
