<?php

namespace Drupal\Tests\history\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\Cookie\CookieJar;

/**
 * Tests the History endpoints.
 *
 * @group history
 */
class HistoryTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'history'];

  /**
   * The main user for testing.
   *
   * @var object
   */
  protected $user;

  /**
   * A page node for which to check content statistics.
   *
   * @var object
   */
  protected $testNode;

  /**
   * The cookie jar holding the testing session cookies for Guzzle requests.
   *
   * @var \GuzzleHttp\Client;
   */
  protected $client;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\Cookie\CookieJar;
   */
  protected $cookies;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->user = $this->drupalCreateUser(['create page content', 'access content']);
    $this->drupalLogin($this->user);
    $this->testNode = $this->drupalCreateNode(['type' => 'page', 'uid' => $this->user->id()]);

    $this->client = $this->getHttpClient();
  }

  /**
   * Get node read timestamps from the server for the current user.
   *
   * @param array $node_ids
   *   An array of node IDs.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response object.
   */
  protected function getNodeReadTimestamps(array $node_ids) {
    // Perform HTTP request.
    $url = Url::fromRoute('history.get_last_node_view')
      ->setAbsolute()
      ->toString();
    return $this->client->post($url, [
      'body' => http_build_query(['node_ids' => $node_ids]),
      'cookies' => $this->cookies,
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
      'http_errors' => FALSE,
    ]);
  }

  /**
   * Mark a node as read for the current user.
   *
   * @param int $node_id
   *   A node ID.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response body.
   */
  protected function markNodeAsRead($node_id) {
    $url = Url::fromRoute('history.read_node', ['node' => $node_id], ['absolute' => TRUE])->toString();
    return $this->client->post($url, [
      'cookies' => $this->cookies,
      'headers' => [
        'Accept' => 'application/json',
      ],
      'http_errors' => FALSE,
    ]);
  }

  /**
   * Verifies that the history endpoints work.
   */
  public function testHistory() {
    $nid = $this->testNode->id();

    // Retrieve "last read" timestamp for test node, for the current user.
    $response = $this->getNodeReadTimestamps([$nid]);
    $this->assertEquals(200, $response->getStatusCode());
    $json = Json::decode($response->getBody());
    $this->assertIdentical([1 => 0], $json, 'The node has not yet been read.');

    // View the node.
    $this->drupalGet('node/' . $nid);
    $this->assertCacheContext('user.roles:authenticated');
    // JavaScript present to record the node read.
    $settings = $this->getDrupalSettings();
    $libraries = explode(',', $settings['ajaxPageState']['libraries']);
    $this->assertTrue(in_array('history/mark-as-read', $libraries), 'history/mark-as-read library is present.');
    $this->assertEqual([$nid => TRUE], $settings['history']['nodesToMarkAsRead'], 'drupalSettings to mark node as read are present.');

    // Simulate JavaScript: perform HTTP request to mark node as read.
    $response = $this->markNodeAsRead($nid);
    $this->assertEquals(200, $response->getStatusCode());
    $timestamp = Json::decode($response->getBody());
    $this->assertTrue(is_numeric($timestamp), 'Node has been marked as read. Timestamp received.');

    // Retrieve "last read" timestamp for test node, for the current user.
    $response = $this->getNodeReadTimestamps([$nid]);
    $this->assertEquals(200, $response->getStatusCode());
    $json = Json::decode($response->getBody());
    $this->assertIdentical([1 => $timestamp], $json, 'The node has been read.');

    // Failing to specify node IDs for the first endpoint should return a 404.
    $response = $this->getNodeReadTimestamps([]);
    $this->assertEquals(404, $response->getStatusCode());

    // Accessing either endpoint as the anonymous user should return a 403.
    $this->drupalLogout();
    $response = $this->getNodeReadTimestamps([$nid]);
    $this->assertEquals(403, $response->getStatusCode());
    $response = $this->getNodeReadTimestamps([]);
    $this->assertEquals(403, $response->getStatusCode());
    $response = $this->markNodeAsRead($nid);
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * Obtain the HTTP client and set the cookies.
   *
   * @return \GuzzleHttp\Client
   *   The client with BrowserTestBase configuration.
   */
  protected function getHttpClient() {
    // Similar code is also employed to test CSRF tokens.
    // @see \Drupal\Tests\system\Functional\CsrfRequestHeaderTest::testRouteAccess()
    $domain = parse_url($this->getUrl(), PHP_URL_HOST);
    $session_id = $this->getSession()->getCookie($this->getSessionName());
    $this->cookies = CookieJar::fromArray([$this->getSessionName() => $session_id], $domain);
    return $this->getSession()->getDriver()->getClient()->getClient();
  }

}
