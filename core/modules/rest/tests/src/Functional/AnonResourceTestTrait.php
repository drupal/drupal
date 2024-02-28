<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Functional;

use Drupal\Core\Url;
use Psr\Http\Message\ResponseInterface;

/**
 * Defines a trait for testing with no authentication provider.
 *
 * This is intended to be used with
 * \Drupal\Tests\rest\Functional\ResourceTestBase.
 *
 * Characteristics:
 * - When no authentication provider is being used, there also cannot be any
 *   particular error response for missing authentication, since by definition
 *   there is not any authentication.
 * - For the same reason, there are no authentication edge cases to test.
 * - Because no authentication is required, this is vulnerable to CSRF attacks
 *   by design. Hence a REST resource should probably only allow for anonymous
 *   for safe (GET/HEAD) HTTP methods, and only with extreme care should unsafe
 *   (POST/PATCH/DELETE) HTTP methods be allowed for a REST resource that allows
 *   anonymous access.
 */
trait AnonResourceTestTrait {

  /**
   * {@inheritdoc}
   */
  protected function assertResponseWhenMissingAuthentication($method, ResponseInterface $response) {
    throw new \LogicException('When testing for anonymous users, authentication cannot be missing.');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertAuthenticationEdgeCases($method, Url $url, array $request_options) {
  }

}
