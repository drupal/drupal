<?php

namespace Drupal\Tests\rest\Functional;

use Psr\Http\Message\ResponseInterface;

/**
 * Trait for ResourceTestBase subclasses testing $auth=basic_auth + 'language'.
 *
 * @see \Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait
 */
trait BasicAuthResourceWithInterfaceTranslationTestTrait {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function assertResponseWhenMissingAuthentication($method, ResponseInterface $response) {
    // Because BasicAuth::challengeException() relies on the 'system.site'
    // configuration, and this test installs the 'language' module, all config
    // may be translated and therefore gets the 'languages:language_interface'
    // cache context.
    $expected_page_cache_header_value = $method === 'GET' ? 'MISS' : FALSE;
    $this->assertResourceErrorResponse(401, 'No authentication credentials provided.', $response, ['4xx-response', 'config:system.site', 'config:user.role.anonymous', 'http_response'], ['languages:language_interface', 'user.roles:anonymous'], $expected_page_cache_header_value, $expected_page_cache_header_value);
  }

}
