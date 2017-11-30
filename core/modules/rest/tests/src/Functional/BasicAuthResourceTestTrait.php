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
 *
 * @see \Drupal\Tests\rest\Functional\BasicAuthResourceWithInterfaceTranslationTestTrait
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
  protected function assertResponseWhenMissingAuthentication($method, ResponseInterface $response) {
    $expected_page_cache_header_value = $method === 'GET' ? 'MISS' : FALSE;
    // @see \Drupal\basic_auth\Authentication\Provider\BasicAuth::challengeException()
    $expected_dynamic_page_cache_header_value = $expected_page_cache_header_value;
    $this->assertResourceErrorResponse(401, 'No authentication credentials provided.', $response, ['4xx-response', 'config:system.site', 'config:user.role.anonymous', 'http_response'], ['user.roles:anonymous'], $expected_page_cache_header_value, $expected_dynamic_page_cache_header_value);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertAuthenticationEdgeCases($method, Url $url, array $request_options) {
  }

}
