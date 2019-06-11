<?php

/**
 * @file
 * Contains \Drupal\Tests\field\Unit\FieldStorageConfigEntityUnitTest.
 */

namespace Drupal\Tests\field\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\field\Entity\FieldStorageConfig
 *
 * @group field
 */
class FieldStorageConfigEntityUnitTest extends UnitTestCase {

  /**
   * The entity type manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $uuid;

  /**
   * The field type manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $fieldTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->uuid = $this->createMock('\Drupal\Component\Uuid\UuidInterface');
    $this->fieldTypeManager = $this->createMock(FieldTypePluginManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('uuid', $this->uuid);
    $container->set('plugin.manager.field.field_type', $this->fieldTypeManager);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    // Create a mock entity type for FieldStorageConfig.
    $fieldStorageConfigentityType = $this->createMock('\Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $fieldStorageConfigentityType->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('field'));

    // Create a mock entity type to attach the field to.
    $attached_entity_type_id = $this->randomMachineName();
    $attached_entity_type = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $attached_entity_type->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('entity_provider_module'));

    // Get definition is called three times. Twice in
    // ConfigEntityBase::addDependency() to get the provider of the field config
    // entity type and once in FieldStorageConfig::calculateDependencies() to
    // get the provider of the entity type that field is attached to.
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->willReturnMap([
        ['field_storage_config', TRUE, $fieldStorageConfigentityType],
        [$attached_entity_type_id, TRUE, $attached_entity_type],
      ]);

    $this->fieldTypeManager->expects($this->atLeastOnce())
      ->method('getDefinition')
      ->with('test_field_type', FALSE)
      ->willReturn([
        'class' => TestFieldType::class,
      ]);

    $field_storage = new FieldStorageConfig([
      'entity_type' => $attached_entity_type_id,
      'field_name' => 'test_field',
      'type' => 'test_field_type',
      'module' => 'test_module',
    ]);

    $dependencies = $field_storage->calculateDependencies()->getDependencies();
    $this->assertEquals(['entity_provider_module', 'entity_test', 'test_module'], $dependencies['module']);
    $this->assertEquals(['stark'], $dependencies['theme']);
  }

  /**
   * Tests stored cardinality.
   *
   * @covers ::getCardinality
   */
  public function testStoredCardinality() {
    $this->fieldTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_field_type')
      ->willReturn([
        'class' => TestFieldType::class,
        // The field type definition has no enforced cardinality.
        'cardinality' => NULL,
      ]);

    $field_storage = new FieldStorageConfig([
      'entity_type' => 'entity_test',
      'field_name' => 'test_field',
      'type' => 'test_field_type',
      'module' => 'test_module',
    ]);
    $field_storage->setCardinality(8);

    // Check that the stored cardinality is returned.
    $this->assertEquals(8, $field_storage->getCardinality());
  }

  /**
   * Tests enforced cardinality.
   *
   * @covers ::getCardinality
   */
  public function testEnforcedCardinality() {
    $this->fieldTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_field_type')
      ->willReturn([
        'class' => TestFieldType::class,
        // This field type defines an enforced cardinality.
        'cardinality' => 21,
      ]);

    $field_storage = new FieldStorageConfig([
      'entity_type' => 'entity_test',
      'field_name' => 'test_field',
      'type' => 'test_field_type',
      'module' => 'test_module',
    ]);
    // Custom cardinality tentative.
    $field_storage->setCardinality(8);

    // Check that the enforced cardinality is returned.
    $this->assertEquals(21, $field_storage->getCardinality());
  }

  /**
   * Tests invalid enforced cardinality.
   *
   * @covers ::getCardinality
   * @dataProvider providerInvalidEnforcedCardinality
   *
   * @param mixed $enforced_cardinality
   *   Enforced cardinality
   */
  public function testInvalidEnforcedCardinality($enforced_cardinality) {
    $this->fieldTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with('test_field_type')
      ->willReturn([
        'class' => TestFieldType::class,
        'cardinality' => $enforced_cardinality,
      ]);

    $field_storage = new FieldStorageConfig([
      'entity_type' => 'entity_test',
      'field_name' => 'test_field',
      'type' => 'test_field_type',
      'module' => 'test_module',
    ]);

    $this->expectException(FieldException::class);
    $this->expectExceptionMessage("Invalid enforced cardinality '$enforced_cardinality'. Allowed values: a positive integer or -1.");
    $field_storage->getCardinality();
  }

  /**
   * Data provider for ::testInvalidEnforcedCardinality()
   *
   * @return array
   *   Test cases.
   */
  public function providerInvalidEnforcedCardinality() {
    return [
      'zero' => [0],
      'negative_other_than_-1' => [-70],
      'non_numeric' => ['abc%$#@!'],
    ];
  }

}

/**
 * A test class to test field storage dependencies.
 *
 * @see \Drupal\Core\Field\FieldItemInterface::calculateStorageDependencies()
 */
class TestFieldType {

  /**
   * {@inheritdoc}
   */
  public static function calculateStorageDependencies(FieldStorageDefinitionInterface $field_definition) {
    $dependencies = [];
    $dependencies['module'] = ['entity_test'];
    $dependencies['theme'] = ['stark'];

    return $dependencies;
  }

}
