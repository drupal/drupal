<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GuzzleHttp\RequestOptions;

/**
 * Makes assertions about the JSON:API behavior for internal entities.
 *
 * @group jsonapi
 *
 * @internal
 */
class EntryPointTest extends BrowserTestBase {

  use JsonApiRequestTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'jsonapi',
    'basic_auth',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test GET to the entry point.
   */
  public function testEntryPoint() {
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $response = $this->request('GET', Url::fromUri('base://jsonapi'), $request_options);
    $document = Json::decode((string) $response->getBody());
    $expected_cache_contexts = [
      'url.site',
      'user.roles:authenticated',
    ];
    $this->assertTrue($response->hasHeader('X-Drupal-Cache-Contexts'));
    $optimized_expected_cache_contexts = \Drupal::service('cache_contexts_manager')->optimizeTokens($expected_cache_contexts);
    $this->assertSame($optimized_expected_cache_contexts, explode(' ', $response->getHeader('X-Drupal-Cache-Contexts')[0]));
    $links = $document['links'];
    $this->assertRegExp('/.*\/jsonapi/', $links['self']['href']);
    $this->assertRegExp('/.*\/jsonapi\/user\/user/', $links['user--user']['href']);
    $this->assertRegExp('/.*\/jsonapi\/node_type\/node_type/', $links['node_type--node_type']['href']);
    $this->assertArrayNotHasKey('meta', $document);

    // A `me` link must be present for authenticated users.
    $user = $this->createUser();
    $request_options[RequestOptions::HEADERS]['Authorization'] = 'Basic ' . base64_encode($user->name->value . ':' . $user->passRaw);
    $response = $this->request('GET', Url::fromUri('base://jsonapi'), $request_options);
    $document = Json::decode((string) $response->getBody());
    $this->assertArrayHasKey('meta', $document);
    $this->assertStringEndsWith('/jsonapi/user/user/' . $user->uuid(), $document['meta']['links']['me']['href']);
  }

}
