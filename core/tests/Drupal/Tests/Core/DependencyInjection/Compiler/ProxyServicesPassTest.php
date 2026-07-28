<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DependencyInjection\Compiler;

use Drupal\Core\DependencyInjection\Compiler\ProxyServicesPass;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Tests\Core\DependencyInjection\Fixture\LazyService;
use Drupal\Tests\ProxyClass\Core\DependencyInjection\Fixture\LazyService as LazyServiceProxy;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Tests Drupal\Core\DependencyInjection\Compiler\ProxyServicesPass.
 */
#[CoversClass(ProxyServicesPass::class)]
#[Group('DependencyInjection')]
class ProxyServicesPassTest extends UnitTestCase {

  /**
   * The tested proxy services pass.
   *
   * @var \Drupal\Core\DependencyInjection\Compiler\ProxyServicesPass
   */
  protected $proxyServicesPass;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->proxyServicesPass = new ProxyServicesPass();
  }

  /**
   * Tests container without lazy services.
   *
   * @legacy-covers ::process
   */
  public function testContainerWithoutLazyServices(): void {
    $container = new ContainerBuilder();
    $container->register('test_lazy_service', LazyService::class);

    $this->proxyServicesPass->process($container);

    $this->assertCount(2, $container->getDefinitions());
    $this->assertEquals(LazyService::class, $container->getDefinition('test_lazy_service')->getClass());
  }

  /**
   * Tests container with lazy services.
   *
   * @legacy-covers ::process
   */
  public function testContainerWithLazyServices(): void {
    $container = new ContainerBuilder();
    $container->register('test_lazy_service', LazyService::class)
      ->setLazy(TRUE);

    $this->proxyServicesPass->process($container);

    $this->assertCount(3, $container->getDefinitions());

    $non_proxy_definition = $container->getDefinition('drupal.proxy_original_service.test_lazy_service');
    $this->assertEquals(LazyService::class, $non_proxy_definition->getClass());
    $this->assertFalse($non_proxy_definition->isLazy());
    $this->assertTrue($non_proxy_definition->isPublic());

    $this->assertEquals(LazyServiceProxy::class, $container->getDefinition('test_lazy_service')->getClass());
  }

  /**
   * Tests container with lazy services without proxy class.
   *
   * @legacy-covers ::process
   */
  public function testContainerWithLazyServicesWithoutProxyClass(): void {
    $container = new ContainerBuilder();
    $container->register('path.current', CurrentPathStack::class)
      ->setLazy(TRUE);

    $this->expectException(InvalidArgumentException::class);
    $this->proxyServicesPass->process($container);
  }

}
