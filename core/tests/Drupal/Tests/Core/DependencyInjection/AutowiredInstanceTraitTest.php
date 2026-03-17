<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\AutowiredInstanceTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Tests \Drupal\Core\DependencyInjection\AutowiredInstanceTrait.
 */
#[CoversClass(AutowiredInstanceTrait::class)]
#[Group('DependencyInjection')]
class AutowiredInstanceTraitTest extends UnitTestCase {

  /**
   * Tests autowiring services and parameters.
   */
  public function testAutowire(): void {
    $services = [
      'stdClass' => new \stdClass(),
      'custom.service' => new \stdClass(),
      'setter.service' => new \stdClass(),
    ];
    $parameters = [
      'array.parameter' => ['provider1' => 'value1'],
      'string.parameter' => 'string_value',
      'setter.parameter' => 'setter_value',
    ];

    $container = $this->createStub(ContainerInterface::class);
    $container->method('has')
      ->willReturnCallback(fn(string $id): bool => array_key_exists($id, $services));
    $container->method('get')
      ->willReturnCallback(fn(string $id): object => $services[$id]);
    $container->method('hasParameter')
      ->willReturnCallback(fn(string $name): bool => array_key_exists($name, $parameters));
    $container->method('getParameter')
      ->willReturnCallback(fn(string $name): array|string => $parameters[$name]);

    $instance = AutowireTestClass::createInstanceAutowired($container, 'config', 'plugin_id', ['definition']);

    $this->assertSame('config', $instance->configuration);
    $this->assertSame('plugin_id', $instance->pluginId);
    $this->assertSame(['definition'], $instance->pluginDefinition);
    $this->assertSame($services['stdClass'], $instance->serviceByType);
    $this->assertSame($services['custom.service'], $instance->serviceById);
    $this->assertSame($services['custom.service'], $instance->serviceByShortId);
    $this->assertSame($parameters['array.parameter'], $instance->arrayParameter);
    $this->assertSame($parameters['string.parameter'], $instance->stringParameter);
    $this->assertNull($instance->nullableService);
    $this->assertNull($instance->nullableParameter);

    $this->assertSame($services['stdClass'], $instance->setterTypedService);
    $this->assertSame($services['setter.service'], $instance->setterNamedService);
    $this->assertSame($parameters['setter.parameter'], $instance->setterParameter);
  }

  /**
   * Tests exception for missing required service.
   */
  public function testAutowireMissingRequiredService(): void {
    $container = $this->createStub(ContainerInterface::class);
    $container->method('has')->willReturn(FALSE);

    $this->expectException(AutowiringFailedException::class);
    $this->expectExceptionMessage('Cannot autowire service "stdClass": argument "$service" of method "Drupal\Tests\Core\DependencyInjection\AutowireRequiredServiceTestClass::__construct()". Check that either the argument type is correct or the Autowire attribute is passed a valid identifier. Otherwise configure its value explicitly if possible.');
    AutowireRequiredServiceTestClass::createInstanceAutowired($container);
  }

  /**
   * Tests exception for missing required parameter.
   */
  public function testAutowireMissingRequiredParameter(): void {
    $container = $this->createStub(ContainerInterface::class);
    $container->method('hasParameter')->willReturn(FALSE);

    $this->expectException(AutowiringFailedException::class);
    $this->expectExceptionMessage('Cannot autowire parameter "missing.parameter": argument "$parameter" of method "Drupal\Tests\Core\DependencyInjection\AutowireRequiredParameterTestClass::__construct()". Check that either the argument type is correct or the Autowire attribute is passed a valid identifier. Otherwise configure its value explicitly if possible.');
    AutowireRequiredParameterTestClass::createInstanceAutowired($container);
  }

}

/**
 * Test class covering service and parameter autowiring scenarios.
 */
class AutowireTestClass {

  use AutowiredInstanceTrait;

  /**
   * An injected service.
   */
  public \stdClass $setterTypedService;

  /**
   * An injected service.
   */
  public \stdClass $setterNamedService;

  /**
   * An injected parameter.
   */
  public string $setterParameter;

  public function __construct(
    public readonly mixed $configuration,
    public readonly string $pluginId,
    public readonly array $pluginDefinition,
    public readonly \stdClass $serviceByType,
    #[Autowire(service: 'custom.service')]
    public readonly \stdClass $serviceById,
    #[Autowire('@custom.service')]
    public readonly \stdClass $serviceByShortId,
    #[Autowire(param: 'array.parameter')]
    public readonly array $arrayParameter,
    #[Autowire('%string.parameter%')]
    public readonly string $stringParameter,
    #[Autowire('@nullable.service')]
    public readonly ?object $nullableService = NULL,
    #[Autowire(param: 'nullable.parameter')]
    public readonly ?string $nullableParameter = NULL,
  ) {}

  #[Required]
  public function setTypedService(\stdClass $service): void {
    $this->setterTypedService = $service;
  }

  #[Required]
  public function setNamedService(#[Autowire(service: 'setter.service')] $service): void {
    $this->setterNamedService = $service;
  }

  #[Required]
  public function setParameter(#[Autowire('%setter.parameter%')] string $parameter): void {
    $this->setterParameter = $parameter;
  }

}

/**
 * Test class for autowiring a required service.
 */
class AutowireRequiredServiceTestClass {

  use AutowiredInstanceTrait;

  public function __construct(
    public readonly \stdClass $service,
  ) {}

}

/**
 * Test class for autowiring a required parameter.
 */
class AutowireRequiredParameterTestClass {

  use AutowiredInstanceTrait;

  public function __construct(
    #[Autowire(param: 'missing.parameter')]
    public readonly array $parameter,
  ) {}

}
