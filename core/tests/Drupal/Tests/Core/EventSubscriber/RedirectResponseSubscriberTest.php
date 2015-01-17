<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\EventSubscriber\RedirectResponseSubscriberTest.
 */

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\RedirectResponseSubscriber;
use Drupal\Core\Routing\RequestContext;
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
   * Test destination detection and redirection.
   *
   * @param Request $request
   *   The request object with destination query set.
   * @param string|bool $expected
   *   The expected target URL or FALSE.
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
        ->expects($this->any())
        ->method('generateFromPath')
          ->willReturnMap([
            ['test', ['query' => [], 'fragment' => '', 'absolute' => TRUE], 'http://example.com/drupal/test']
          ]);
    }

    $request_context = $this->getMockBuilder('Drupal\Core\Routing\RequestContext')
      ->disableOriginalConstructor()
      ->getMock();
    $request_context->expects($this->any())
      ->method('getCompleteBaseUrl')
      ->willReturn('http://example.com/drupal');

    $listener = new RedirectResponseSubscriber($url_generator, $request_context);
    $dispatcher->addListener(KernelEvents::RESPONSE, array($listener, 'checkRedirectUrl'));
    $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);
    $dispatcher->dispatch(KernelEvents::RESPONSE, $event);

    $target_url = $event->getResponse()->getTargetUrl();
    if ($expected) {
      $this->assertEquals($expected, $target_url);
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
      array(new Request(array('destination' => 'test')), 'http://example.com/drupal/test'),
      array(new Request(array('destination' => 'http://example.com/drupal/')), 'http://example.com/drupal/'),
      array(new Request(array('destination' => 'http://example.com/drupal/test')), 'http://example.com/drupal/test'),
    );
  }
}
