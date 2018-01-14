<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests \Drupal\user\UserServiceProvider.
 *
 * @group user
 * @group legacy
 */
class UserServiceProviderFallbackTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user'];

  /**
   * Tests that user.tempstore.expire equals tempstore.expire if not customized.
   */
  public function testUserServiceProvider() {
    $this->assertEquals(1000, $this->container->getParameter('user.tempstore.expire'));
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->setParameter('tempstore.expire', 1000);
    parent::register($container);
  }

}
