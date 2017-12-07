<?php

namespace Drupal\Tests\dblog\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\ResourceTestBase;

/**
 * Tests the watchdog database log resource.
 *
 * @group dblog
 */
class DbLogResourceTest extends ResourceTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'dblog';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal', 'dblog'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $auth = isset(static::$auth) ? [static::$auth] : [];
    $this->provisionResource([static::$format], $auth);
  }

  /**
   * Writes a log messages and retrieves it via the REST API.
   */
  public function testWatchdog() {
    // Write a log message to the DB.
    $this->container->get('logger.channel.rest')->notice('Test message');
    // Get the ID of the written message.
    $id = db_query_range("SELECT wid FROM {watchdog} WHERE type = :type ORDER BY wid DESC", 0, 1, [':type' => 'rest'])
      ->fetchField();

    $this->initAuthentication();
    $url = Url::fromRoute('rest.dblog.GET.' . static::$format, ['id' => $id, '_format' => static::$format]);
    $request_options = $this->getAuthenticationRequestOptions('GET');

    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(403, "The 'restful get dblog' permission is required.", $response);

    // Create a user account that has the required permissions to read
    // the watchdog resource via the REST API.
    $this->setUpAuthorization('GET');

    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response, ['config:rest.resource.dblog', 'config:rest.settings', 'http_response'], ['user.permissions'], FALSE, 'MISS');
    $log = Json::decode((string) $response->getBody());
    $this->assertEqual($log['wid'], $id, 'Log ID is correct.');
    $this->assertEqual($log['type'], 'rest', 'Type of log message is correct.');
    $this->assertEqual($log['message'], 'Test message', 'Log message text is correct.');

    // Request an unknown log entry.
    $url->setRouteParameter('id', 9999);
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(404, 'Log entry with ID 9999 was not found', $response);

    // Make a bad request (a true malformed request would never be a route match).
    $url->setRouteParameter('id', 0);
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'No log entry ID was provided', $response);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['restful get dblog']);
        break;

      default:
        throw new \UnexpectedValueException();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNormalizationEdgeCases($method, Url $url, array $request_options) {}

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {}

  /**
   * {@inheritdoc}
   */
  protected function getExpectedBcUnauthorizedAccessMessage($method) {}

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {}

}
