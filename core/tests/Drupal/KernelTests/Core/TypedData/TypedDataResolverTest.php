<?php

namespace Drupal\KernelTests\Core\TypedData;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\Core\TypedData\TypedDataResolver
 *
 * @group TypedData
 */
class TypedDataResolverTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system', 'entity_test'];

  /**
   * @var \Drupal\Core\TypedData\TypedDataResolver
   */
  protected $typedDataResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');

    $this->typedDataResolver = $this->container->get('typed_data_resolver');
  }

  /**
   * Tests context extraction from properties.
   */
  public function testGetContextFromProperty() {
    // Create a user and test entity to extract context from.
    $user = User::create(['uid' => 2, 'name' => 'username', 'mail' => 'mail@example.org']);
    $user->enforceIsNew(TRUE);
    $user->save();
    $entity_test = EntityTest::create(['user_id' => $user->id(), 'name' => 'Test name']);

    // Test the language property.
    $property_context = $this->assertPropertyPath($entity_test, 'langcode:language', 'language');
    $this->assertEquals('en', $property_context->getContextValue()->getId());

    // Test the reference to the user.
    $property_context = $this->assertPropertyPath($entity_test, 'user_id:entity', 'entity:user');
    $this->assertEquals($user->id(), $property_context->getContextValue()->id());

    // Test the reference to the name.
    $property_context = $this->assertPropertyPath($entity_test, 'name:value', 'string');
    $this->assertEquals('Test name', $property_context->getContextValue());

    // Test explicitly specifying the delta.
    $property_context = $this->assertPropertyPath($entity_test, 'name:0:value', 'string');
    $this->assertEquals('Test name', $property_context->getContextValue());

    // Test following the reference.
    $property_context = $this->assertPropertyPath($entity_test, 'user_id:entity:mail:value', 'email');
    $this->assertEquals('mail@example.org', $property_context->getContextValue());
  }

  /**
   * Asserts that a context for the given property path can be derived.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to test with.
   * @param $property_path
   *   The property path to look for.
   * @param $expected_data_type
   *   The expected data type.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface
   *   The context with a value.
   */
  protected function assertPropertyPath(ContentEntityInterface $entity, $property_path, $expected_data_type) {
    $typed_data_entity = $entity->getTypedData();
    if (strpos($typed_data_entity->getDataDefinition()->getDataType(), 'entity:') === 0) {
      $context_definition = new EntityContextDefinition($typed_data_entity->getDataDefinition()->getDataType());
    }
    else {
      $context_definition = new ContextDefinition($typed_data_entity->getDataDefinition()->getDataType());
    }
    $context_with_value = new Context($context_definition, $typed_data_entity);
    $context_without_value = new Context($context_definition);

    // Test the context without value.
    $property_context = $this->typedDataResolver->getContextFromProperty($property_path, $context_without_value);
    $this->assertEquals($expected_data_type, $property_context->getContextDefinition()->getDataType());

    // Test the context with value.
    $property_context = $this->typedDataResolver->getContextFromProperty($property_path, $context_with_value);
    $this->assertEquals($expected_data_type, $property_context->getContextDefinition()->getDataType());

    // Return the context with value so it can be asserted.
    return $property_context;
  }

}
