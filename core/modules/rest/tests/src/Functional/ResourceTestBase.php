<?php

namespace Drupal\Tests\rest\Functional;

use Behat\Mink\Driver\BrowserKitDriver;
use Drupal\Core\Url;
use Drupal\rest\RestResourceConfigInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Subclass this for every REST resource, every format and every auth provider.
 *
 * For more guidance see
 * \Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase
 * which has recommendations for testing the
 * \Drupal\rest\Plugin\rest\resource\EntityResource REST resource for every
 * format and every auth provider. It's a special case (because that single REST
 * resource generates supports not just one thing, but many things — multiple
 * entity types), but the same principles apply.
 */
abstract class ResourceTestBase extends BrowserTestBase {

  /**
   * The format to use in this test.
   *
   * A format is the combination of a certain normalizer and a certain
   * serializer.
   *
   * @see https://www.drupal.org/developing/api/8/serialization
   *
   * (The default is 'json' because that doesn't depend on any module.)
   *
   * @var string
   */
  protected static $format = 'json';

  /**
   * The MIME type that corresponds to $format.
   *
   * (Sadly this cannot be computed automatically yet.)
   *
   * @var string
   */
  protected static $mimeType = 'application/json';

  /**
   * The authentication mechanism to use in this test.
   *
   * (The default is 'cookie' because that doesn't depend on any module.)
   *
   * @var string
   */
  protected static $auth = FALSE;

  /**
   * The REST Resource Config entity ID under test (i.e. a resource type).
   *
   * The REST Resource plugin ID can be calculated from this.
   *
   * @var string
   *
   * @see \Drupal\rest\Entity\RestResourceConfig::__construct()
   */
  protected static $resourceConfigId = NULL;

  /**
   * The account to use for authentication, if any.
   *
   * @var null|\Drupal\Core\Session\AccountInterface
   */
  protected $account = NULL;

  /**
   * The REST resource config entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $resourceConfigStorage;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['rest'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->serializer = $this->container->get('serializer');

    // Ensure the anonymous user role has no permissions at all.
    $user_role = Role::load(RoleInterface::ANONYMOUS_ID);
    foreach ($user_role->getPermissions() as $permission) {
      $user_role->revokePermission($permission);
    }
    $user_role->save();
    assert([] === $user_role->getPermissions(), 'The anonymous user role has no permissions at all.');

    if (static::$auth !== FALSE) {
      // Ensure the authenticated user role has no permissions at all.
      $user_role = Role::load(RoleInterface::AUTHENTICATED_ID);
      foreach ($user_role->getPermissions() as $permission) {
        $user_role->revokePermission($permission);
      }
      $user_role->save();
      assert([] === $user_role->getPermissions(), 'The authenticated user role has no permissions at all.');

      // Create an account.
      $this->account = $this->createUser();
    }
    else {
      // Otherwise, also create an account, so that any test involving User
      // entities will have the same user IDs regardless of authentication.
      $this->createUser();
    }

    $this->resourceConfigStorage = $this->container->get('entity_type.manager')->getStorage('rest_resource_config');

    // Ensure there's a clean slate: delete all REST resource config entities.
    $this->resourceConfigStorage->delete($this->resourceConfigStorage->loadMultiple());
    $this->refreshTestStateAfterRestConfigChange();
  }

  /**
   * Provisions the REST resource under test.
   *
   * @param string[] $formats
   *   The allowed formats for this resource.
   * @param string[] $authentication
   *   The allowed authentication providers for this resource.
   * @param string[] $methods
   *   The allowed methods for this resource.
   */
  protected function provisionResource($formats = [], $authentication = [], array $methods = ['GET', 'POST', 'PATCH', 'DELETE']) {
    $this->resourceConfigStorage->create([
      'id' => static::$resourceConfigId,
      'granularity' => RestResourceConfigInterface::RESOURCE_GRANULARITY,
      'configuration' => [
        'methods' => $methods,
        'formats' => $formats,
        'authentication' => $authentication,
      ],
      'status' => TRUE,
    ])->save();
    $this->refreshTestStateAfterRestConfigChange();
  }

  /**
   * Refreshes the state of the tester to be in sync with the testee.
   *
   * Should be called after every change made to:
   * - RestResourceConfig entities
   */
  protected function refreshTestStateAfterRestConfigChange() {
    // Ensure that the cache tags invalidator has its internal values reset.
    // Otherwise the http_response cache tag invalidation won't work.
    $this->refreshVariables();

    // Tests using this base class may trigger route rebuilds due to changes to
    // RestResourceConfig entities. Ensure the test generates routes using an
    // up-to-date router.
    \Drupal::service('router.builder')->rebuildIfNeeded();
  }

  /**
   * Return the expected error message.
   *
   * @param string $method
   *   The HTTP method (GET, POST, PATCH, DELETE).
   *
   * @return string
   *   The error string.
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    $resource_plugin_id = str_replace('.', ':', static::$resourceConfigId);
    $permission = 'restful ' . strtolower($method) . ' ' . $resource_plugin_id;
    return "The '$permission' permission is required.";
  }

  /**
   * Sets up the necessary authorization.
   *
   * In case of a test verifying publicly accessible REST resources: grant
   * permissions to the anonymous user role.
   *
   * In case of a test verifying behavior when using a particular authentication
   * provider: create a user with a particular set of permissions.
   *
   * Because of the $method parameter, it's possible to first set up
   * authentication for only GET, then add POST, et cetera. This then also
   * allows for verifying a 403 in case of missing authorization.
   *
   * @param string $method
   *   The HTTP method for which to set up authentication.
   *
   * @see ::grantPermissionsToAnonymousRole()
   * @see ::grantPermissionsToAuthenticatedRole()
   */
  abstract protected function setUpAuthorization($method);

  /**
   * Verifies the error response in case of missing authentication.
   *
   * @param string $method
   *   HTTP method.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response to assert.
   */
  abstract protected function assertResponseWhenMissingAuthentication($method, ResponseInterface $response);

  /**
   * Asserts normalization-specific edge cases.
   *
   * (Should be called before sending a well-formed request.)
   *
   * @see \GuzzleHttp\ClientInterface::request()
   *
   * @param string $method
   *   HTTP method.
   * @param \Drupal\Core\Url $url
   *   URL to request.
   * @param array $request_options
   *   Request options to apply.
   */
  abstract protected function assertNormalizationEdgeCases($method, Url $url, array $request_options);

  /**
   * Asserts authentication provider-specific edge cases.
   *
   * (Should be called before sending a well-formed request.)
   *
   * @see \GuzzleHttp\ClientInterface::request()
   *
   * @param string $method
   *   HTTP method.
   * @param \Drupal\Core\Url $url
   *   URL to request.
   * @param array $request_options
   *   Request options to apply.
   */
  abstract protected function assertAuthenticationEdgeCases($method, Url $url, array $request_options);

  /**
   * Returns the expected cacheability of an unauthorized access response.
   *
   * @return \Drupal\Core\Cache\RefinableCacheableDependencyInterface
   *   The expected cacheability.
   */
  abstract protected function getExpectedUnauthorizedAccessCacheability();

  /**
   * Initializes authentication.
   *
   * E.g. for cookie authentication, we first need to get a cookie.
   */
  protected function initAuthentication() {}

  /**
   * Returns Guzzle request options for authentication.
   *
   * @param string $method
   *   The HTTP method for this authenticated request.
   *
   * @return array
   *   Guzzle request options to use for authentication.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function getAuthenticationRequestOptions($method) {
    return [];
  }

  /**
   * Grants permissions to the anonymous role.
   *
   * @param string[] $permissions
   *   Permissions to grant.
   */
  protected function grantPermissionsToAnonymousRole(array $permissions) {
    $this->grantPermissions(Role::load(RoleInterface::ANONYMOUS_ID), $permissions);
  }

  /**
   * Grants permissions to the authenticated role.
   *
   * @param string[] $permissions
   *   Permissions to grant.
   */
  protected function grantPermissionsToAuthenticatedRole(array $permissions) {
    $this->grantPermissions(Role::load(RoleInterface::AUTHENTICATED_ID), $permissions);
  }

  /**
   * Grants permissions to the tested role: anonymous or authenticated.
   *
   * @param string[] $permissions
   *   Permissions to grant.
   *
   * @see ::grantPermissionsToAuthenticatedRole()
   * @see ::grantPermissionsToAnonymousRole()
   */
  protected function grantPermissionsToTestedRole(array $permissions) {
    if (static::$auth) {
      $this->grantPermissionsToAuthenticatedRole($permissions);
    }
    else {
      $this->grantPermissionsToAnonymousRole($permissions);
    }
  }

  /**
   * Performs a HTTP request. Wraps the Guzzle HTTP client.
   *
   * Why wrap the Guzzle HTTP client? Because we want to keep the actual test
   * code as simple as possible, and hence not require them to specify the
   * 'http_errors = FALSE' request option, nor do we want them to have to
   * convert Drupal Url objects to strings.
   *
   * We also don't want to follow redirects automatically, to ensure these tests
   * are able to detect when redirects are added or removed.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   *
   * @param string $method
   *   HTTP method.
   * @param \Drupal\Core\Url $url
   *   URL to request.
   * @param array $request_options
   *   Request options to apply.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  protected function request($method, Url $url, array $request_options) {
    $request_options[RequestOptions::HTTP_ERRORS] = FALSE;
    $request_options[RequestOptions::ALLOW_REDIRECTS] = FALSE;
    $request_options = $this->decorateWithXdebugCookie($request_options);
    $client = $this->getHttpClient();
    return $client->request($method, $url->setAbsolute(TRUE)->toString(), $request_options);
  }

  /**
   * Asserts that a resource response has the given status code and body.
   *
   * @param int $expected_status_code
   *   The expected response status.
   * @param string|false $expected_body
   *   The expected response body. FALSE in case this should not be asserted.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response to assert.
   * @param string[]|false $expected_cache_tags
   *   (optional) The expected cache tags in the X-Drupal-Cache-Tags response
   *   header, or FALSE if that header should be absent. Defaults to FALSE.
   * @param string[]|false $expected_cache_contexts
   *   (optional) The expected cache contexts in the X-Drupal-Cache-Contexts
   *   response header, or FALSE if that header should be absent. Defaults to
   *   FALSE.
   * @param string|false $expected_page_cache_header_value
   *   (optional) The expected X-Drupal-Cache response header value, or FALSE if
   *   that header should be absent. Possible strings: 'MISS', 'HIT'. Defaults
   *   to FALSE.
   * @param string|false $expected_dynamic_page_cache_header_value
   *   (optional) The expected X-Drupal-Dynamic-Cache response header value, or
   *   FALSE if that header should be absent. Possible strings: 'MISS', 'HIT'.
   *   Defaults to FALSE.
   */
  protected function assertResourceResponse($expected_status_code, $expected_body, ResponseInterface $response, $expected_cache_tags = FALSE, $expected_cache_contexts = FALSE, $expected_page_cache_header_value = FALSE, $expected_dynamic_page_cache_header_value = FALSE) {
    $this->assertSame($expected_status_code, $response->getStatusCode());
    if ($expected_status_code === 204) {
      // DELETE responses should not include a Content-Type header. But Apache
      // sets it to 'text/html' by default. We also cannot detect the presence
      // of Apache either here in the CLI. For now having this documented here
      // is all we can do.
      // $this->assertFalse($response->hasHeader('Content-Type'));
      $this->assertSame('', (string) $response->getBody());
    }
    else {
      $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
      if ($expected_body !== FALSE) {
        $this->assertSame($expected_body, (string) $response->getBody());
      }
    }

    // Expected cache tags: X-Drupal-Cache-Tags header.
    $this->assertSame($expected_cache_tags !== FALSE, $response->hasHeader('X-Drupal-Cache-Tags'));
    if (is_array($expected_cache_tags)) {
      $this->assertEqualsCanonicalizing($expected_cache_tags, explode(' ', $response->getHeader('X-Drupal-Cache-Tags')[0]));
    }

    // Expected cache contexts: X-Drupal-Cache-Contexts header.
    $this->assertSame($expected_cache_contexts !== FALSE, $response->hasHeader('X-Drupal-Cache-Contexts'));
    if (is_array($expected_cache_contexts)) {
      $this->assertEqualsCanonicalizing($expected_cache_contexts, explode(' ', $response->getHeader('X-Drupal-Cache-Contexts')[0]));
    }

    // Expected Page Cache header value: X-Drupal-Cache header.
    if ($expected_page_cache_header_value !== FALSE) {
      $this->assertTrue($response->hasHeader('X-Drupal-Cache'));
      $this->assertSame($expected_page_cache_header_value, $response->getHeader('X-Drupal-Cache')[0]);
    }
    else {
      $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    }

    // Expected Dynamic Page Cache header value: X-Drupal-Dynamic-Cache header.
    if ($expected_dynamic_page_cache_header_value !== FALSE) {
      $this->assertTrue($response->hasHeader('X-Drupal-Dynamic-Cache'));
      $this->assertSame($expected_dynamic_page_cache_header_value, $response->getHeader('X-Drupal-Dynamic-Cache')[0]);
    }
    else {
      $this->assertFalse($response->hasHeader('X-Drupal-Dynamic-Cache'));
    }
  }

  /**
   * Asserts that a resource error response has the given message.
   *
   * @param int $expected_status_code
   *   The expected response status.
   * @param string $expected_message
   *   The expected error message.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The error response to assert.
   * @param string[]|false $expected_cache_tags
   *   (optional) The expected cache tags in the X-Drupal-Cache-Tags response
   *   header, or FALSE if that header should be absent. Defaults to FALSE.
   * @param string[]|false $expected_cache_contexts
   *   (optional) The expected cache contexts in the X-Drupal-Cache-Contexts
   *   response header, or FALSE if that header should be absent. Defaults to
   *   FALSE.
   * @param string|false $expected_page_cache_header_value
   *   (optional) The expected X-Drupal-Cache response header value, or FALSE if
   *   that header should be absent. Possible strings: 'MISS', 'HIT'. Defaults
   *   to FALSE.
   * @param string|false $expected_dynamic_page_cache_header_value
   *   (optional) The expected X-Drupal-Dynamic-Cache response header value, or
   *   FALSE if that header should be absent. Possible strings: 'MISS', 'HIT'.
   *   Defaults to FALSE.
   */
  protected function assertResourceErrorResponse($expected_status_code, $expected_message, ResponseInterface $response, $expected_cache_tags = FALSE, $expected_cache_contexts = FALSE, $expected_page_cache_header_value = FALSE, $expected_dynamic_page_cache_header_value = FALSE) {
    $expected_body = ($expected_message !== FALSE) ? $this->serializer->encode(['message' => $expected_message], static::$format) : FALSE;
    $this->assertResourceResponse($expected_status_code, $expected_body, $response, $expected_cache_tags, $expected_cache_contexts, $expected_page_cache_header_value, $expected_dynamic_page_cache_header_value);
  }

  /**
   * Adds the Xdebug cookie to the request options.
   *
   * @param array $request_options
   *   The request options.
   *
   * @return array
   *   Request options updated with the Xdebug cookie if present.
   */
  protected function decorateWithXdebugCookie(array $request_options) {
    $session = $this->getSession();
    $driver = $session->getDriver();
    if ($driver instanceof BrowserKitDriver) {
      $client = $driver->getClient();
      foreach ($client->getCookieJar()->all() as $cookie) {
        if (isset($request_options[RequestOptions::HEADERS]['Cookie'])) {
          $request_options[RequestOptions::HEADERS]['Cookie'] .= '; ' . $cookie->getName() . '=' . $cookie->getValue();
        }
        else {
          $request_options[RequestOptions::HEADERS]['Cookie'] = $cookie->getName() . '=' . $cookie->getValue();
        }
      }
    }
    return $request_options;
  }

  /**
   * Recursively sorts an array by key.
   *
   * @param array $array
   *   An array to sort.
   *
   * @return array
   *   The sorted array.
   */
  protected static function recursiveKSort(array &$array) {
    // First, sort the main array.
    ksort($array);

    // Then check for child arrays.
    foreach ($array as $key => &$value) {
      if (is_array($value)) {
        static::recursiveKSort($value);
      }
    }
  }

}
