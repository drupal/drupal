<?php

/**
 * @file
 * Definition of Drupal\rest\test\AuthTest.
 */

namespace Drupal\rest\Tests;

use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests authentication provider restrictions.
 *
 * @group rest
 */
class AuthTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('basic_auth', 'hal', 'rest', 'entity_test', 'comment');

  /**
   * Tests reading from an authenticated resource.
   */
  public function testRead() {
    $entity_type = 'entity_test';

    // Enable a test resource through GET method and basic HTTP authentication.
    $this->enableService('entity:' . $entity_type, 'GET', NULL, array('basic_auth'));

    // Create an entity programmatically.
    $entity = $this->entityCreate($entity_type);
    $entity->save();

    // Try to read the resource as an anonymous user, which should not work.
    $this->httpRequest($entity->getSystemPath(), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse('401', 'HTTP response code is 401 when the request is not authenticated and the user is anonymous.');
    $this->assertRaw(json_encode(['error' => 'A fatal error occurred: No authentication credentials provided.']));

    // Ensure that cURL settings/headers aren't carried over to next request.
    unset($this->curlHandle);

    // Create a user account that has the required permissions to read
    // resources via the REST API, but the request is authenticated
    // with session cookies.
    $permissions = $this->entityPermissions($entity_type, 'view');
    $permissions[] = 'restful get entity:' . $entity_type;
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Try to read the resource with session cookie authentication, which is
    // not enabled and should not work.
    $this->httpRequest($entity->getSystemPath(), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse('401', 'HTTP response code is 401 when the request is authenticated but not authorized.');

    // Ensure that cURL settings/headers aren't carried over to next request.
    unset($this->curlHandle);

    // Now read it with the Basic authentication which is enabled and should
    // work.
    $this->basicAuthGet($entity->getSystemPath(), $account->getUsername(), $account->pass_raw);
    $this->assertResponse('200', 'HTTP response code is 200 for successfully authorized requests.');
    $this->curlClose();
  }

  /**
   * Performs a HTTP request with Basic authentication.
   *
   * We do not use \Drupal\simpletest\WebTestBase::drupalGet because we need to
   * set curl settings for basic authentication.
   *
   * @param string $path
   *   The request path.
   * @param string $username
   *   The user name to authenticate with.
   * @param string $password
   *   The password.
   *
   * @return string
   *   Curl output.
   */
  protected function basicAuthGet($path, $username, $password) {
    $out = $this->curlExec(
      array(
        CURLOPT_HTTPGET => TRUE,
        CURLOPT_URL => _url($path, array('absolute' => TRUE)),
        CURLOPT_NOBODY => FALSE,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $username . ':' . $password,
      )
    );

    $this->verbose('GET request to: ' . $path .
      '<hr />' . $out);

    return $out;
  }

}
