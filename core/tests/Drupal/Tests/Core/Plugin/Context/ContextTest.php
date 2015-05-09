<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\Context\ContextTest.
 */

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Context\Context
 * @group Plugin
 */
class ContextTest extends UnitTestCase {

  /**
   * The mocked context definition object.
   *
   * @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $contextDefinition;

  /**
   * The mocked Typed Data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedDataManager;

  /**
   * The mocked Typed Data object.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedData;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $mock_data_definition = $this->getMock('Drupal\Core\TypedData\DataDefinitionInterface');

    $this->contextDefinition = $this->getMockBuilder('Drupal\Core\Plugin\Context\ContextDefinitionInterface')
      ->setMethods(array('getDefaultValue', 'getDataDefinition'))
      ->getMockForAbstractClass();

    $this->contextDefinition->expects($this->once())
      ->method('getDefaultValue')
      ->willReturn('test');

    $this->contextDefinition->expects($this->once())
      ->method('getDataDefinition')
      ->willReturn($mock_data_definition);

    $this->typedData = $this->getMock('Drupal\Core\TypedData\TypedDataInterface');

    $this->typedDataManager = $this->getMockBuilder('Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->setMethods(array('create'))
      ->getMock();

    $this->typedDataManager->expects($this->once())
      ->method('create')
      ->with($mock_data_definition, 'test')
      ->willReturn($this->typedData);
  }

  /**
   * @covers ::getContextValue
   */
  public function testDefaultValue() {
    $context = new Context($this->contextDefinition);
    $context->setTypedDataManager($this->typedDataManager);
    $this->assertEquals('test', $context->getContextValue());
  }

  /**
   * @covers ::getContextData
   */
  public function testDefaultDataValue() {
    $context = new Context($this->contextDefinition);
    $context->setTypedDataManager($this->typedDataManager);
    $this->assertEquals($this->typedData, $context->getContextData());
  }

}
