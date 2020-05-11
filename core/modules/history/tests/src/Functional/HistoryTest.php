<?php

namespace Drupal\Tests\history\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->user = $this->drupalCreateUser(['create page content', 'access content']);
    $this->drupalLogin($this->user);
    $this->testNode = $this->drupalCreateNode(['type' => 'page', 'uid' => $this->user->id()]);
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
    $http_client = $this->getHttpClient();
    $url = Url::fromRoute('history.get_last_node_view')
      ->setAbsolute()
      ->toString();

    return $http_client->request('POST', $url, [
      'form_params' => ['node_ids' => $node_ids],
      'cookies' => $this->getSessionCookies(),
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
    $http_client = $this->getHttpClient();
    $url = Url::fromRoute('history.read_node', ['node' => $node_id], ['absolute' => TRUE])->toString();

    return $http_client->request('POST', $url, [
      'cookies' => $this->getSessionCookies(),
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
    $this->assertContains('history/mark-as-read', $libraries, 'history/mark-as-read library is present.');
    $this->assertEqual([$nid => TRUE], $settings['history']['nodesToMarkAsRead'], 'drupalSettings to mark node as read are present.');

    // Simulate JavaScript: perform HTTP request to mark node as read.
    $response = $this->markNodeAsRead($nid);
    $this->assertEquals(200, $response->getStatusCode());
    $timestamp = Json::decode($response->getBody());
    $this->assertIsNumeric($timestamp);

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

}
