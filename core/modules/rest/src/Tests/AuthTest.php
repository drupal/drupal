<?php

/**
 * @file
 * Definition of Drupal\rest\test\AuthTest.
 */

namespace Drupal\rest\Tests;

use Drupal\Core\Url;
use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests authentication provider restrictions.
 *
 * @group rest
 */
class AuthTest extends RESTTestBase {

  /**
   * Modules to install.
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
    $this->httpRequest($entity->urlInfo(), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse('401', 'HTTP response code is 401 when the request is not authenticated and the user is anonymous.');
    $this->assertRaw(json_encode(['message' => 'A fatal error occurred: No authentication credentials provided.']));

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
    $this->httpRequest($entity->urlInfo(), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse('403', 'HTTP response code is 403 when the request was authenticated by the wrong authentication provider.');

    // Ensure that cURL settings/headers aren't carried over to next request.
    unset($this->curlHandle);

    // Now read it with the Basic authentication which is enabled and should
    // work.
    $this->basicAuthGet($entity->urlInfo(), $account->getUsername(), $account->pass_raw, $this->defaultMimeType);
    $this->assertResponse('200', 'HTTP response code is 200 for successfully authenticated requests.');
    $this->curlClose();
  }

  /**
   * Performs a HTTP request with Basic authentication.
   *
   * We do not use \Drupal\simpletest\WebTestBase::drupalGet because we need to
   * set curl settings for basic authentication.
   *
   * @param \Drupal\Core\Url $url
   *   An Url object.
   * @param string $username
   *   The user name to authenticate with.
   * @param string $password
   *   The password.
   * @param string $mime_type
   *   The MIME type for the Accept header.
   *
   * @return string
   *   Curl output.
   */
  protected function basicAuthGet(Url $url, $username, $password, $mime_type = NULL) {
    if (!isset($mime_type)) {
      $mime_type = $this->defaultMimeType;
    }
    $out = $this->curlExec(
      array(
        CURLOPT_HTTPGET => TRUE,
        CURLOPT_URL => $url->setAbsolute()->toString(),
        CURLOPT_NOBODY => FALSE,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $username . ':' . $password,
        CURLOPT_HTTPHEADER => array('Accept: ' . $mime_type),
      )
    );

    $this->verbose('GET request to: ' . $url->toString() .
      '<hr />' . $out);

    return $out;
  }

}
