<?php

namespace Drupal\Tests\Core\DependencyInjection\Compiler;

use Drupal\Core\DependencyInjection\Compiler\ProxyServicesPass;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\Compiler\ProxyServicesPass
 * @group DependencyInjection
 */
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
  protected function setUp() {
    parent::setUp();

    $this->proxyServicesPass = new ProxyServicesPass();
  }


  /**
   * @covers ::process
   */
  public function testContainerWithoutLazyServices() {
    $container = new ContainerBuilder();
    $container->register('plugin_cache_clearer', 'Drupal\Core\Plugin\CachedDiscoveryClearer');

    $this->proxyServicesPass->process($container);

    $this->assertCount(2, $container->getDefinitions());
    $this->assertEquals('Drupal\Core\Plugin\CachedDiscoveryClearer', $container->getDefinition('plugin_cache_clearer')->getClass());
  }

  /**
   * @covers ::process
   */
  public function testContainerWithLazyServices() {
    $container = new ContainerBuilder();
    $container->register('plugin_cache_clearer', 'Drupal\Core\Plugin\CachedDiscoveryClearer')
      ->setLazy(TRUE);

    $this->proxyServicesPass->process($container);

    $this->assertCount(3, $container->getDefinitions());

    $non_proxy_definition = $container->getDefinition('drupal.proxy_original_service.plugin_cache_clearer');
    $this->assertEquals('Drupal\Core\Plugin\CachedDiscoveryClearer', $non_proxy_definition->getClass());
    $this->assertFalse($non_proxy_definition->isLazy());
    $this->assertTrue($non_proxy_definition->isPublic());

    $this->assertEquals('Drupal\Core\ProxyClass\Plugin\CachedDiscoveryClearer', $container->getDefinition('plugin_cache_clearer')->getClass());
  }

  /**
   * @covers ::process
   */
  public function testContainerWithLazyServicesWithoutProxyClass() {
    $container = new ContainerBuilder();
    $container->register('alias_whitelist', 'Drupal\Core\Path\AliasWhitelist')
      ->setLazy(TRUE);

    $this->setExpectedException(InvalidArgumentException::class);
    $this->proxyServicesPass->process($container);
  }

}
