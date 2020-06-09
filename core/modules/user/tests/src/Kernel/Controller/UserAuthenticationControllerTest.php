<?php

namespace Drupal\Tests\user\Kernel\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Controller\UserAuthenticationController;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @coversDefaultClass \Drupal\user\Controller\UserController
 * @group user
 */
class UserAuthenticationControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * @group legacy
   * @expectedDeprecation Passing the flood service to Drupal\user\Controller\UserAuthenticationController::__construct is deprecated in drupal:9.1.0 and is replaced by user.flood_control in drupal:10.0.0. See https://www.drupal.org/node/3067148
   */
  public function testConstructorDeprecations() {
    $flood = $this->prophesize(FloodInterface::class);
    $user_storage = $this->prophesize(UserStorageInterface::class);
    $csrf_token = $this->prophesize(CsrfTokenGenerator::class);
    $user_auth = $this->prophesize(UserAuthInterface::class);
    $route_provider = $this->prophesize(RouteProviderInterface::class);
    $serializer = $this->prophesize(Serializer::class);
    $serializer_formats = [];
    $logger = $this->prophesize(LoggerInterface::class);
    $controller = new UserAuthenticationController(
      $flood->reveal(),
      $user_storage->reveal(),
      $csrf_token->reveal(),
      $user_auth->reveal(),
      $route_provider->reveal(),
      $serializer->reveal(),
      $serializer_formats,
      $logger->reveal()
    );
    $this->assertNotNull($controller);
  }

}
