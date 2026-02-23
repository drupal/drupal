<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\AutowiredInstanceTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Tests Drupal\Core\DependencyInjection\AutowiredInstanceTrait.
 */
#[CoversClass(AutowiredInstanceTrait::class)]
#[Group('DependencyInjection')]
class AutowiredInstanceTraitTest extends UnitTestCase {

  public function testSetterInjection(): void {
    $container = $this->createMock(ContainerInterface::class);
    $service = new \stdClass();

    $container->method('has')
      ->with('my_service')
      ->willReturn(TRUE);
    $container->method('get')
      ->with('my_service')
      ->willReturn($service);

    $instance = TestClassWithSetter::createInstanceAutowired($container);

    $this->assertSame($service, $instance->injectedService);
  }

}

/**
 * Test class with a setter method for dependency injection.
 */
class TestClassWithSetter {

  use AutowiredInstanceTrait;

  /**
   * The injected service.
   */
  public \stdClass $injectedService;

  #[Required]
  public function setService(#[Autowire(service: 'my_service')] $service): void {
    $this->injectedService = $service;
  }

}
