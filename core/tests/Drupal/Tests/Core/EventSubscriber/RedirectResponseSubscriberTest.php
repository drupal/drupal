<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\EventSubscriber\RedirectResponseSubscriberTest.
 */

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\RedirectResponseSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\RedirectResponseSubscriber
 * @group EventSubscriber
 */
class RedirectResponseSubscriberTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $GLOBALS['base_url'] = 'http://example.com/drupal';
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    unset($GLOBALS['base_url']);
    parent::tearDown();
  }

  /**
   * Test destination detection and redirection.
   *
   * @param Request $request
   *   The request object with destination query set.
   * @param bool $expected
   *   Whether or not a redirect will occur.
   *
   * @covers ::checkRedirectUrl
   * @dataProvider providerTestDestinationRedirect
   */
  public function testDestinationRedirect(Request $request, $expected) {
    $dispatcher = new EventDispatcher();
    $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $response = new RedirectResponse('http://example.com/drupal');
    $url_generator = $this->getMockBuilder('Drupal\Core\Routing\UrlGenerator')
      ->disableOriginalConstructor()
      ->setMethods(array('generateFromPath'))
      ->getMock();

    if ($expected) {
      $url_generator
        ->expects($this->once())
        ->method('generateFromPath')
          ->will($this->returnValue('success'));
    }

    $listener = new RedirectResponseSubscriber($url_generator);
    $dispatcher->addListener(KernelEvents::RESPONSE, array($listener, 'checkRedirectUrl'));
    $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);
    $dispatcher->dispatch(KernelEvents::RESPONSE, $event);

    $target_url = $event->getResponse()->getTargetUrl();
    if ($expected) {
      $this->assertEquals('success', $target_url);
    }
    else {
      $this->assertEquals('http://example.com/drupal', $target_url);
    }
  }

  /**
   * Data provider for testDestinationRedirect().
   *
   * @see \Drupal\Tests\Core\EventSubscriber\RedirectResponseSubscriberTest::testDestinationRedirect()
   */
  public static function providerTestDestinationRedirect() {
    return array(
      array(new Request(), FALSE),
      array(new Request(array('destination' => 'http://example.com')), FALSE),
      array(new Request(array('destination' => 'http://example.com/foobar')), FALSE),
      array(new Request(array('destination' => 'http://example.ca/drupal')), FALSE),
      array(new Request(array('destination' => 'test')), TRUE),
      array(new Request(array('destination' => 'http://example.com/drupal/')), TRUE),
      array(new Request(array('destination' => 'http://example.com/drupal/test')), TRUE),
    );
  }
}
