<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\HttpFoundation\SecuredRedirectResponseTest.
 */

namespace Drupal\Tests\Component\HttpFoundation;

use Drupal\Component\HttpFoundation\SecuredRedirectResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Test secure redirect base class.
 *
 * @group Routing
 * @coversDefaultClass \Drupal\Component\HttpFoundation\SecuredRedirectResponse
 */
class SecuredRedirectResponseTest extends TestCase {

  /**
   * Test copying of redirect response.
   *
   * @covers ::createFromRedirectResponse
   * @covers ::fromResponse
   */
  public function testRedirectCopy() {
    $redirect = new RedirectResponse('/magic_redirect_url', 301, ['x-cache-foobar' => 123]);
    $redirect->setProtocolVersion('2.0');
    $redirect->setCharset('ibm-943_P14A-2000');
    $redirect->headers->setCookie(new Cookie('name', 'value', 0, '/', NULL, FALSE, TRUE, FALSE, NULL));

    // Make a cloned redirect.
    $secureRedirect = SecuredRedirectStub::createFromRedirectResponse($redirect);
    $this->assertEquals('/magic_redirect_url', $secureRedirect->getTargetUrl());
    $this->assertEquals(301, $secureRedirect->getStatusCode());
    // We pull the headers from the original redirect because there are default headers applied.
    $headers1 = $redirect->headers->allPreserveCase();
    $headers2 = $secureRedirect->headers->allPreserveCase();
    // We unset cache headers so we don't test arcane Symfony weirdness.
    // https://github.com/symfony/symfony/issues/16171
    unset($headers1['Cache-Control'], $headers2['Cache-Control']);
    $this->assertEquals($headers1, $headers2);
    $this->assertEquals('2.0', $secureRedirect->getProtocolVersion());
    $this->assertEquals('ibm-943_P14A-2000', $secureRedirect->getCharset());
    $this->assertEquals($redirect->headers->getCookies(), $secureRedirect->headers->getCookies());
  }

}

class SecuredRedirectStub extends SecuredRedirectResponse {

  /**
   * {@inheritdoc}
   */
  protected function isSafe($url) {
    // Empty implementation for testing.
    return TRUE;
  }

}
