<?php

declare(strict_types=1);

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
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'history'];

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'access content',
    ]);
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
  public function testHistory(): void {
    $nid = $this->testNode->id();

    // Verify that previews of new entities do not create the history.
    $this->drupalGet("node/add/page");
    $this->submitForm(['title[0][value]' => 'Unsaved page'], 'Preview');
    $this->assertArrayNotHasKey('ajaxPageState', $this->getDrupalSettings());

    // Retrieve "last read" timestamp for test node, for the current user.
    $response = $this->getNodeReadTimestamps([$nid]);
    $this->assertEquals(200, $response->getStatusCode());
    $json = Json::decode($response->getBody());
    $this->assertSame([1 => 0], $json, 'The node has not yet been read.');

    // View the node.
    $this->drupalGet('node/' . $nid);
    $this->assertCacheContext('user.roles:authenticated');
    // JavaScript present to record the node read.
    $settings = $this->getDrupalSettings();
    $libraries = explode(',', $settings['ajaxPageState']['libraries']);
    $this->assertContains('history/mark-as-read', $libraries, 'history/mark-as-read library is present.');
    $this->assertEquals([$nid => TRUE], $settings['history']['nodesToMarkAsRead'], 'drupalSettings to mark node as read are present.');

    // Simulate JavaScript: perform HTTP request to mark node as read.
    $response = $this->markNodeAsRead($nid);
    $this->assertEquals(200, $response->getStatusCode());
    $timestamp = Json::decode($response->getBody());
    $this->assertIsNumeric($timestamp);

    // Retrieve "last read" timestamp for test node, for the current user.
    $response = $this->getNodeReadTimestamps([$nid]);
    $this->assertEquals(200, $response->getStatusCode());
    $json = Json::decode($response->getBody());
    $this->assertSame([1 => $timestamp], $json, 'The node has been read.');

    // Failing to specify node IDs for the first endpoint should return a 404.
    $response = $this->getNodeReadTimestamps([]);
    $this->assertEquals(404, $response->getStatusCode());

    // Verify that previews of existing entities do not update the history.
    $this->drupalGet("node/$nid/edit");
    $this->submitForm([], 'Preview');
    $this->assertArrayNotHasKey('ajaxPageState', $this->getDrupalSettings());

    // Accessing either endpoint as the anonymous user should return a 403.
    $this->drupalLogout();
    $response = $this->getNodeReadTimestamps([$nid]);
    $this->assertEquals(403, $response->getStatusCode());
    $response = $this->getNodeReadTimestamps([]);
    $this->assertEquals(403, $response->getStatusCode());
    $response = $this->markNodeAsRead($nid);
    $this->assertEquals(403, $response->getStatusCode());

    // Additional check to ensure that we did not forget to verify anything.
    $rows = \Drupal::database()->query('SELECT * FROM {history}')->fetchAll();
    $this->assertCount(1, $rows);
    $this->assertSame($this->user->id(), $rows[0]->uid);
    $this->assertSame($this->testNode->id(), $rows[0]->nid);
    $this->assertSame($timestamp, (int) $rows[0]->timestamp);
  }

}
