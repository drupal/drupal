<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\Context\ContextTest.
 */

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Context\Context
 * @group Plugin
 */
class ContextTest extends UnitTestCase {

  /**
   * The mocked context definition object.
   *
   * @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $contextDefinition;

  /**
   * The mocked Typed Data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $typedDataManager;

  /**
   * The mocked Typed Data object.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $typedData;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->typedDataManager = $this->createMock(TypedDataManagerInterface::class);
  }

  /**
   * @covers ::getContextValue
   */
  public function testDefaultValue() {
    $this->setUpDefaultValue('test');

    $context = new Context($this->contextDefinition);
    $context->setTypedDataManager($this->typedDataManager);
    $this->assertEquals('test', $context->getContextValue());
  }

  /**
   * @covers ::getContextData
   */
  public function testDefaultDataValue() {
    $this->setUpDefaultValue('test');

    $context = new Context($this->contextDefinition);
    $context->setTypedDataManager($this->typedDataManager);
    $this->assertEquals($this->typedData, $context->getContextData());
  }

  /**
   * @covers ::getContextData
   */
  public function testNullDataValue() {
    $this->setUpDefaultValue(NULL);

    $context = new Context($this->contextDefinition);
    $context->setTypedDataManager($this->typedDataManager);
    $this->assertEquals($this->typedData, $context->getContextData());
  }

  /**
   * @covers ::setContextValue
   */
  public function testSetContextValueTypedData() {

    $this->contextDefinition = $this->getMockBuilder('Drupal\Core\Plugin\Context\ContextDefinitionInterface')
      ->setMethods(['getDefaultValue', 'getDataDefinition'])
      ->getMockForAbstractClass();

    $typed_data = $this->createMock('Drupal\Core\TypedData\TypedDataInterface');
    $context = new Context($this->contextDefinition, $typed_data);
    $this->assertSame($typed_data, $context->getContextData());
  }

  /**
   * @covers ::setContextValue
   */
  public function testSetContextValueCacheableDependency() {
    $container = new Container();
    $cache_context_manager = $this->getMockBuilder('Drupal\Core\Cache\CacheContextsManager')
      ->disableOriginalConstructor()
      ->setMethods(['validateTokens'])
      ->getMock();
    $container->set('cache_contexts_manager', $cache_context_manager);
    $cache_context_manager->expects($this->any())
      ->method('validateTokens')
      ->with(['route'])
      ->willReturn(['route']);
    \Drupal::setContainer($container);

    $this->contextDefinition = $this->createMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');

    $context = new Context($this->contextDefinition);
    $context->setTypedDataManager($this->typedDataManager);
    $cacheable_dependency = $this->createMock('Drupal\Tests\Core\Plugin\Context\TypedDataCacheableDependencyInterface');
    $cacheable_dependency->expects($this->once())
      ->method('getCacheTags')
      ->willReturn(['node:1']);
    $cacheable_dependency->expects($this->once())
      ->method('getCacheContexts')
      ->willReturn(['route']);
    $cacheable_dependency->expects($this->once())
      ->method('getCacheMaxAge')
      ->willReturn(60);

    $context = Context::createFromContext($context, $cacheable_dependency);
    $this->assertSame($cacheable_dependency, $context->getContextData());
    $this->assertEquals(['node:1'], $context->getCacheTags());
    $this->assertEquals(['route'], $context->getCacheContexts());
    $this->assertEquals(60, $context->getCacheMaxAge());
  }

  /**
   * Set up mocks for the getDefaultValue() method call.
   *
   * @param mixed $default_value
   *   The default value to assign to the mock context definition.
   */
  protected function setUpDefaultValue($default_value = NULL) {
    $mock_data_definition = $this->createMock('Drupal\Core\TypedData\DataDefinitionInterface');

    $this->contextDefinition = $this->getMockBuilder('Drupal\Core\Plugin\Context\ContextDefinitionInterface')
      ->setMethods(['getDefaultValue', 'getDataDefinition'])
      ->getMockForAbstractClass();

    $this->contextDefinition->expects($this->once())
      ->method('getDefaultValue')
      ->willReturn($default_value);

    $this->contextDefinition->expects($this->once())
      ->method('getDataDefinition')
      ->willReturn($mock_data_definition);

    $this->typedData = $this->createMock('Drupal\Core\TypedData\TypedDataInterface');

    $this->typedDataManager->expects($this->once())
      ->method('create')
      ->with($mock_data_definition, $default_value)
      ->willReturn($this->typedData);
  }

}

/**
 * Test interface used for mocking.
 */
interface TypedDataCacheableDependencyInterface extends CacheableDependencyInterface, TypedDataInterface {}
