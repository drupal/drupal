<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Routing\TrustedRedirectResponseTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\Core\Routing\TrustedRedirectResponse
 * @group Routing
 */
class TrustedRedirectResponseTest extends UnitTestCase {

  /**
   * @covers ::setTargetUrl
   */
  public function testSetTargetUrlWithInternalUrl() {
    $redirect_response = new TrustedRedirectResponse('/example');
    $redirect_response->setTargetUrl('/example2');

    $this->assertEquals('/example2', $redirect_response->getTargetUrl());
  }

  /**
   * @covers ::setTargetUrl
   * @expectedException \InvalidArgumentException
   */
  public function testSetTargetUrlWithUntrustedUrl() {
    $request_context = new RequestContext();
    $request_context->setCompleteBaseUrl('https://www.drupal.org');
    $container = new ContainerBuilder();
    $container->set('router.request_context', $request_context);
    \Drupal::setContainer($container);

    $redirect_response = new TrustedRedirectResponse('/example');

    $redirect_response->setTargetUrl('http://evil-url.com/example');
  }

  /**
   * @covers ::setTargetUrl
   */
  public function testSetTargetUrlWithTrustedUrl() {
    $redirect_response = new TrustedRedirectResponse('/example');

    $redirect_response->setTrustedTargetUrl('http://good-external-url.com/example');
    $this->assertEquals('http://good-external-url.com/example', $redirect_response->getTargetUrl());
  }

}
