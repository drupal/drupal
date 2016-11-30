<?php

namespace Drupal\Tests\rest\Functional;

use Psr\Http\Message\ResponseInterface;

trait JsonBasicAuthWorkaroundFor2805281Trait {

  /**
   * {@inheritdoc}
   *
   * Note that strange 'A fatal error occurred: ' prefix, that should not exist.
   *
   * @todo Fix in https://www.drupal.org/node/2805281: remove this trait.
   */
  protected function assertResponseWhenMissingAuthentication(ResponseInterface $response) {
    $this->assertSame(401, $response->getStatusCode());
    $this->assertSame([static::$expectedErrorMimeType], $response->getHeader('Content-Type'));
    // Note that strange 'A fatal error occurred: ' prefix, that should not
    // exist.
    // @todo Fix in https://www.drupal.org/node/2805281.
    $this->assertSame('{"message":"A fatal error occurred: No authentication credentials provided."}', (string) $response->getBody());
  }

}
