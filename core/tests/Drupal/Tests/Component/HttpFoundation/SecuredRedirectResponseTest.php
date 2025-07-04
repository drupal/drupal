<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\HttpFoundation;

use Drupal\Component\HttpFoundation\SecuredRedirectResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Test secure redirect base class.
 */
#[CoversClass(SecuredRedirectResponse::class)]
#[Group('Routing')]
class SecuredRedirectResponseTest extends TestCase {

  /**
   * Tests copying of redirect response.
   *
   * @legacy-covers ::createFromRedirectResponse
   * @legacy-covers ::fromResponse
   */
  public function testRedirectCopy(): void {
    $redirect = new RedirectResponse('/magic_redirect_url', 301, ['x-cache-foobar' => 123]);
    $redirect->setProtocolVersion('2.0');
    $redirect->setCharset('ibm-943_P14A-2000');
    $redirect->headers->setCookie(new Cookie('name', 'value', 0, '/', NULL, FALSE, TRUE, FALSE, NULL));

    // Make a cloned redirect.
    $secureRedirect = SecuredRedirectStub::createFromRedirectResponse($redirect);
    $this->assertEquals('/magic_redirect_url', $secureRedirect->getTargetUrl());
    $this->assertEquals(301, $secureRedirect->getStatusCode());
    // We pull the headers from the original redirect because there are default
    // headers applied.
    $headers1 = $redirect->headers->all();
    $headers2 = $secureRedirect->headers->all();
    $this->assertEquals($headers1, $headers2);
    $this->assertEquals('2.0', $secureRedirect->getProtocolVersion());
    $this->assertEquals('ibm-943_P14A-2000', $secureRedirect->getCharset());
    $this->assertEquals($redirect->headers->getCookies(), $secureRedirect->headers->getCookies());
  }

}

/**
 * Test class for safe redirects.
 */
class SecuredRedirectStub extends SecuredRedirectResponse {

  /**
   * {@inheritdoc}
   */
  protected function isSafe($url): bool {
    // Empty implementation for testing.
    return TRUE;
  }

}
