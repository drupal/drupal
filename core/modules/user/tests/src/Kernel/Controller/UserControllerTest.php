<?php

namespace Drupal\Tests\user\Kernel\Controller;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Controller\UserController;
use Drupal\user\UserDataInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\user\Controller\UserController
 * @group user
 */
class UserControllerTest extends KernelTestBase {

  /**
   * @group legacy
   * @expectedDeprecation Calling Drupal\user\Controller\UserController::__construct without the $flood parameter is deprecated in drupal:8.8.0 and is required in drupal:9.0.0. See https://www.drupal.org/node/1681832
   */
  public function testConstructorDeprecations() {
    $date_formatter = $this->prophesize(DateFormatterInterface::class);
    $user_storage = $this->prophesize(UserStorageInterface::class);
    $user_data = $this->prophesize(UserDataInterface::class);
    $logger = $this->prophesize(LoggerInterface::class);
    $controller = new UserController(
      $date_formatter->reveal(),
      $user_storage->reveal(),
      $user_data->reveal(),
      $logger->reveal()
    );
    $this->assertNotNull($controller);
  }

}
