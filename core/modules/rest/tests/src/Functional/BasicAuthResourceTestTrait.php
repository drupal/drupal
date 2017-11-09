<?php

namespace Drupal\Tests\rest\Functional;

use Drupal\Core\Url;
use Psr\Http\Message\ResponseInterface;

/**
 * Trait for ResourceTestBase subclasses testing $auth=basic_auth.
 *
 * Characteristics:
 * - Every request must send an Authorization header.
 * - When accessing a URI that requires authentication without being
 *   authenticated, a 401 response must be sent.
 * - Because every request must send an authorization, there is no danger of
 *   CSRF attacks.
 */
trait BasicAuthResourceTestTrait {

  /**
   * {@inheritdoc}
   */
  protected function getAuthenticationRequestOptions($method) {
    return [
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($this->account->name->value . ':' . $this->account->passRaw),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function assertResponseWhenMissingAuthentication(ResponseInterface $response) {
    $this->assertResourceErrorResponse(401, 'No authentication credentials provided.', $response);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertAuthenticationEdgeCases($method, Url $url, array $request_options) {}

}
