<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\Context\ContextTypedDataTest.
 */

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Tests\UnitTestCase;

/**
 * Tests that contexts work properly with the typed data manager.
 *
 * @coversDefaultClass \Drupal\Core\Plugin\Context\Context
 * @group Context
 */
class ContextTypedDataTest extends UnitTestCase {

  /**
   * The typed data object used during testing.
   *
   * @var \Drupal\Core\TypedData\Plugin\DataType\StringData
   */
  protected $typedData;

  /**
   * Tests that getting a context value does not throw fatal errors.
   *
   * This test ensures that the typed data manager is set correctly on the
   * Context class.
   *
   * @covers ::getContextValue
   */
  public function testGetContextValue() {
    // Prepare a container that holds the typed data manager mock.
    $typed_data_manager = $this->getMockBuilder('Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();
    $typed_data_manager->expects($this->once())
      ->method('getCanonicalRepresentation')
      ->will($this->returnCallback(array($this, 'getCanonicalRepresentation')));
    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $typed_data_manager);
    \Drupal::setContainer($container);

    $definition = new ContextDefinition('any');
    $context = new Context($definition);
    $data_definition = DataDefinition::create('string');
    $this->typedData = new StringData($data_definition);
    $this->typedData->setValue('example string');
    $context->setContextData($this->typedData);
    $value = $context->getContextValue();
    $this->assertSame($value, $this->typedData->getValue());
  }

  /**
   * Helper mock callback to return the typed data value.
   */
  public function getCanonicalRepresentation() {
    return $this->typedData->getValue();
  }

}
