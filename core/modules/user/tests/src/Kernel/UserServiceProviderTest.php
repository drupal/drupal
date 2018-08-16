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
class UserServiceProviderTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user'];

  /**
   * Tests that tempstore.expire is set to user.tempstore.expire.
   *
   * @expectedDeprecation The container parameter "user.tempstore.expire" is deprecated. Use "tempstore.expire" instead. See https://www.drupal.org/node/2935639.
   */
  public function testUserServiceProvider() {
    $this->assertEquals(1000, $this->container->getParameter('tempstore.expire'));
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->setParameter('user.tempstore.expire', 1000);
    parent::register($container);
  }

}
