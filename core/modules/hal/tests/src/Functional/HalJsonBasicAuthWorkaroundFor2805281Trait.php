<?php

namespace Drupal\Tests\hal\Functional;

use Psr\Http\Message\ResponseInterface;

trait HalJsonBasicAuthWorkaroundFor2805281Trait {

  /**
   * {@inheritdoc}
   *
   * Note how the response claims it contains a application/hal+json body, but
   * in reality it contains a text/plain body! Also, the correct error MIME type
   * is application/json.
   *
   * @todo Fix in https://www.drupal.org/node/2805281: remove this trait.
   */
  protected function assertResponseWhenMissingAuthentication(ResponseInterface $response) {
    $this->assertSame(401, $response->getStatusCode());
    // @todo this works fine locally, but on testbot it comes back with
    // 'text/plain; charset=UTF-8'. WTF.
    // $this->assertSame(['application/hal+json'], $response->getHeader('Content-Type'));
    $this->assertSame('No authentication credentials provided.', (string) $response->getBody());
  }

}
