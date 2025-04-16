<?php

declare(strict_types=1);

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
  protected function getAuthenticationRequestOptions($method): array {
    return [
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($this->account->getAccountName() . ':' . $this->account->passRaw),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function assertResponseWhenMissingAuthentication($method, ResponseInterface $response) {
    if ($method !== 'GET') {
      return $this->assertResourceErrorResponse(401, 'No authentication credentials provided.', $response);
    }

    $expected_page_cache_header_value = $method === 'GET' ? 'MISS' : FALSE;
    $expected_cacheability = $this->getExpectedUnauthorizedAccessCacheability()
      ->addCacheableDependency($this->getExpectedUnauthorizedEntityAccessCacheability(FALSE))
      // @see \Drupal\basic_auth\Authentication\Provider\BasicAuth::challengeException()
      ->addCacheableDependency($this->config('system.site'))
      // @see \Drupal\Core\EventSubscriber\AnonymousUserResponseSubscriber::onRespond()
      ->addCacheTags(['config:user.role.anonymous']);
    // Only add the 'user.roles:anonymous' cache context if its parent cache
    // context is not already present.
    if (!in_array('user.roles', $expected_cacheability->getCacheContexts(), TRUE)) {
      $expected_cacheability->addCacheContexts(['user.roles:anonymous']);
    }
    $this->assertResourceErrorResponse(401, 'No authentication credentials provided.', $response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), $expected_page_cache_header_value, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertAuthenticationEdgeCases($method, Url $url, array $request_options) {
  }

}
