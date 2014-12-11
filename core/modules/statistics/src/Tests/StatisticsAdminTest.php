<?php

/**
 * @file
 * Definition of Drupal\statistics\Tests\StatisticsAdminTest.
 */

namespace Drupal\statistics\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the statistics admin.
 *
 * @group statistics
 */
class StatisticsAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'statistics');

  /**
   * A user that has permission to administer statistics.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser;

  /**
   * A page node for which to check content statistics.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testNode;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface;
   */
  protected $client;

  protected function setUp() {
    parent::setUp();

    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    }
    $this->privilegedUser = $this->drupalCreateUser(array('administer statistics', 'view post access counter', 'create page content'));
    $this->drupalLogin($this->privilegedUser);
    $this->testNode = $this->drupalCreateNode(array('type' => 'page', 'uid' => $this->privilegedUser->id()));
    $this->client = \Drupal::httpClient();
    $this->client->setDefaultOption('config/curl', array(CURLOPT_TIMEOUT => 10));
  }

  /**
   * Verifies that the statistics settings page works.
   */
  function testStatisticsSettings() {
    $config = \Drupal::config('statistics.settings');
    $this->assertFalse($config->get('count_content_views'), 'Count content view log is disabled by default.');

    // Enable counter on content view.
    $edit['statistics_count_content_views'] = 1;
    $this->drupalPostForm('admin/config/system/statistics', $edit, t('Save configuration'));
    $config = \Drupal::config('statistics.settings');
    $this->assertTrue($config->get('count_content_views'), 'Count content view log is enabled.');

    // Hit the node.
    $this->drupalGet('node/' . $this->testNode->id());
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $this->testNode->id();
    $post = array('nid' => $nid);
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics'). '/statistics.php';
    $this->client->post($stats_path, array('body' => $post));

    // Hit the node again (the counter is incremented after the hit, so
    // "1 view" will actually be shown when the node is hit the second time).
    $this->drupalGet('node/' . $this->testNode->id());
    $this->client->post($stats_path, array('body' => $post));
    $this->assertText('1 view', 'Node is viewed once.');

    $this->drupalGet('node/' . $this->testNode->id());
    $this->client->post($stats_path, array('body' => $post));
    $this->assertText('2 views', 'Node is viewed 2 times.');
  }

  /**
   * Tests that when a node is deleted, the node counter is deleted too.
   */
  function testDeleteNode() {
    \Drupal::config('statistics.settings')->set('count_content_views', 1)->save();

    $this->drupalGet('node/' . $this->testNode->id());
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $this->testNode->id();
    $post = array('nid' => $nid);
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics'). '/statistics.php';
    $this->client->post($stats_path, array('body' => $post));

    $result = db_select('node_counter', 'n')
      ->fields('n', array('nid'))
      ->condition('n.nid', $this->testNode->id())
      ->execute()
      ->fetchAssoc();
    $this->assertEqual($result['nid'], $this->testNode->id(), 'Verifying that the node counter is incremented.');

    $this->testNode->delete();

    $result = db_select('node_counter', 'n')
      ->fields('n', array('nid'))
      ->condition('n.nid', $this->testNode->id())
      ->execute()
      ->fetchAssoc();
    $this->assertFalse($result, 'Verifying that the node counter is deleted.');
  }

  /**
   * Tests that cron clears day counts and expired access logs.
   */
  function testExpiredLogs() {
    \Drupal::config('statistics.settings')
      ->set('count_content_views', 1)
      ->save();
    \Drupal::state()->set('statistics.day_timestamp', 8640000);

    $this->drupalGet('node/' . $this->testNode->id());
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $this->testNode->id();
    $post = array('nid' => $nid);
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics'). '/statistics.php';
    $this->client->post($stats_path, array('body' => $post));
    $this->drupalGet('node/' . $this->testNode->id());
    $this->client->post($stats_path, array('body' => $post));
    $this->assertText('1 view', 'Node is viewed once.');

    // statistics_cron() will subtract
    // statistics.settings:accesslog.max_lifetime config from REQUEST_TIME in
    // the delete query, so wait two secs here to make sure the access log will
    // be flushed for the node just hit.
    sleep(2);
    $this->cronRun();

    $this->drupalGet('admin/reports/pages');
    $this->assertNoText('node/' . $this->testNode->id(), 'No hit URL found.');

    $result = db_select('node_counter', 'nc')
      ->fields('nc', array('daycount'))
      ->condition('nid', $this->testNode->id(), '=')
      ->execute()
      ->fetchField();
    $this->assertFalse($result, 'Daycounter is zero.');
  }
}
