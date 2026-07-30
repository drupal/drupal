<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\DependencyInjection\Container;

/**
 * Tests Drupal\Core\Plugin\Context\Context.
 */
#[CoversClass(Context::class)]
#[Group('Plugin')]
class ContextTest extends UnitTestCase {

  /**
   * The mocked context definition object.
   */
  protected ContextDefinitionInterface&Stub $contextDefinition;

  /**
   * The mocked Typed Data manager.
   */
  protected TypedDataManagerInterface&Stub $typedDataManager;

  /**
   * The mocked Typed Data object.
   */
  protected TypedDataInterface&Stub $typedData;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->typedDataManager = $this->createStub(TypedDataManagerInterface::class);
  }

  /**
   * Tests default value.
   *
   * @legacy-covers ::getContextValue
   */
  public function testDefaultValue(): void {
    $this->setUpDefaultValue('test');

    $context = new Context($this->contextDefinition);
    $context->setTypedDataManager($this->typedDataManager);
    $this->assertEquals('test', $context->getContextValue());
  }

  /**
   * Tests default data value.
   *
   * @legacy-covers ::getContextData
   */
  public function testDefaultDataValue(): void {
    $this->setUpDefaultValue('test');

    $context = new Context($this->contextDefinition);
    $context->setTypedDataManager($this->typedDataManager);
    $this->assertEquals($this->typedData, $context->getContextData());
  }

  /**
   * Tests null data value.
   *
   * @legacy-covers ::getContextData
   */
  public function testNullDataValue(): void {
    $this->setUpDefaultValue();

    $context = new Context($this->contextDefinition);
    $context->setTypedDataManager($this->typedDataManager);
    $this->assertEquals($this->typedData, $context->getContextData());
  }

  /**
   * Tests set context value typed data.
   */
  public function testSetContextValueTypedData(): void {

    $this->contextDefinition = $this->createStub(ContextDefinitionInterface::class);

    $typed_data = $this->createStub(TypedDataInterface::class);
    $context = new Context($this->contextDefinition, $typed_data);
    $this->assertSame($typed_data, $context->getContextData());
  }

  /**
   * Tests set context value cacheable dependency.
   */
  public function testSetContextValueCacheableDependency(): void {
    $container = new Container();
    $cache_context_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->onlyMethods(['validateTokens'])
      ->getMock();
    $container->set('cache_contexts_manager', $cache_context_manager);
    $cache_context_manager
      ->method('validateTokens')
      ->with(['route'])
      ->willReturn(['route']);
    \Drupal::setContainer($container);

    $this->contextDefinition = $this->createStub(ContextDefinitionInterface::class);

    $context = new Context($this->contextDefinition);
    $context->setTypedDataManager($this->createStub(TypedDataManagerInterface::class));
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
  protected function setUpDefaultValue($default_value = NULL): void {
    $mock_data_definition = $this->createStub(DataDefinitionInterface::class);

    $this->contextDefinition = $this->createMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');

    $this->contextDefinition->expects($this->once())
      ->method('getDefaultValue')
      ->willReturn($default_value);

    $this->contextDefinition->expects($this->once())
      ->method('getDataDefinition')
      ->willReturn($mock_data_definition);

    $this->typedData = $this->createStub(TypedDataInterface::class);

    $this->typedDataManager = $this->createMock(TypedDataManagerInterface::class);
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
