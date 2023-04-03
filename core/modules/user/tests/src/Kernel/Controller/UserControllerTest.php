<?php

namespace Drupal\Tests\user\Kernel\Controller;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Controller\UserController;

/**
 * Tests for the User controller.
 *
 * @group user
 *
 * @coversDefaultClass \Drupal\user\Controller\UserController
 */
class UserControllerTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * The user controller.
   *
   * @var \Drupal\user\Controller\UserController
   */
  protected $userController;

  /**
   * The logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->userController = UserController::create(\Drupal::getContainer());

    // Create and log in a user.
    $this->user = $this->setUpCurrentUser();

  }

  /**
   * Tests the redirection to a user edit page.
   *
   * @covers ::userEditPage
   */
  public function testUserEditPage() {

    $response = $this->userController->userEditPage();

    // Ensure the response is directed to the correct user edit page.
    $edit_url = Url::fromRoute('entity.user.edit_form', [
      'user' => $this->user->id(),
    ])->setAbsolute()
      ->toString();
    $this->assertEquals($edit_url, $response->getTargetUrl());

    $this->assertEquals(302, $response->getStatusCode());

  }

}
