<?php

/**
 * @file
 * Contains \Drupal\router_test\TestContent.
 */

namespace Drupal\router_test;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Test controllers that are intended to be wrapped in a main controller.
 */
class TestContent extends ControllerBase {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs a TestContent instance.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('http_kernel'));
  }

  /**
   * Provides example content for testing route enhancers.
   */
  public function test1() {
    return 'abcde';
  }

  /**
   * Provides example content for route specific authentication.
   *
   * @returns string
   *   The user name of the current logged in user.
   */
  public function test11() {
    $account = $this->currentUser();
    return $account->getUsername();
  }

  public function testAccount(UserInterface $user) {
    $current_user = $this->currentUser();
    \Drupal::getContainer()->set('current_user', $user);
    return $current_user->getUsername() . ':' . $user->getUsername();
  }

  /**
   * Uses a subrequest to determine the content.
   */
  public function subrequestTest(UserInterface $user) {
    $request = \Drupal::request();
    $request = Request::create('/router_test/test13/' . $user->id(), 'GET', $request->query->all(), $request->cookies->all(), array(), $request->server->all());

    return $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);
  }

}
