<?php

/**
 * @file
 * Contains \Drupal\overlay\Tests\Controller\OverlayControllerTest.
 */

namespace Drupal\overlay\Tests\Controller {

use Drupal\overlay\Controller\OverlayController;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the overlay controller.
 *
 * @see \Drupal\overlay\Controller\OverlayController
 */
class OverlayControllerTest extends UnitTestCase {

  /**
   * The mocked user data.
   *
   * @var \Drupal\user\UserDataInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $userData;

  /**
   * The mocked csrf token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $csrfGenerator;

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * The mocked URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * The tested overlay controller.
   *
   * @var \Drupal\overlay\Controller\OverlayController
   */
  protected $overlayController;

  public static function getInfo() {
    return array(
      'name' => 'Overlay controller tests',
      'description' => 'Tests the overlay controller.',
      'group' => 'Overlay',
    );
  }

  protected function setUp() {
    $this->userData = $this->getMock('Drupal\user\UserDataInterface');
    $this->csrfGenerator = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');

    $this->overlayController = new OverlayController($this->userData, $this->csrfGenerator, $this->account);

    $translation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');
    $container = new ContainerBuilder();
    $container->set('url_generator', $this->urlGenerator);
    $container->set('string_translation', $translation);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the overlayMessage method.
   *
   * @see \Drupal\overlay\Controller\OverlayController::overlayMessage()
   */
  public function testOverlayMessage() {
    $this->account->expects($this->any())
      ->method('id')
      ->will($this->returnValue(31));
    $this->userData->expects($this->once())
      ->method('set')
      ->with('overlay', 31, 'message_dismissed', 1);
    $this->urlGenerator->expects($this->once())
      ->method('generate')
      ->with('user_edit', array('user' => 31))
      ->will($this->returnValue('http://drupal/user/31/edit'));

    $token = $this->randomName();
    $this->csrfGenerator->expects($this->once())
      ->method('validate')
      ->with($token, 'overlay')
      ->will($this->returnValue(TRUE));

    $request = new Request();
    $request->attributes->set('token', $token);
    $result = $this->overlayController->overlayMessage($request);

    $this->assertTrue($result instanceof RedirectResponse);
    $this->assertEquals('http://drupal/user/31/edit', $result->getTargetUrl());
  }

  /**
   * Tests the overlayMessage method with non existing token.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function testOverlayMessageWithoutToken() {
    $request = new Request();
    $this->overlayController->overlayMessage($request);
  }

  /**
   * Tests the overlayMessage method with invalid token.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function testOverlayMessageWithInvalidToken() {
    $this->csrfGenerator->expects($this->once())
      ->method('validate')
      ->with('invalid_token', 'overlay')
      ->will($this->returnValue(FALSE));
    $request = new Request();
    $request->attributes->set('token', 'invalid_token');
    $this->overlayController->overlayMessage($request);
  }

}

}

namespace {
  // @todo Convert once drupal_set_message is an object
  if (!function_exists('drupal_set_message')) {
    function drupal_set_message($message) {
    }
  }
}
